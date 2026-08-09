<?php
/**
 * Elimina registro en articulos_venta_imagenes y archivo en photos/.
 */
require_once __DIR__ . '/../../../include/session.php';
require_once __DIR__ . '/../../../include/functions.php';

header('Content-Type: application/json; charset=utf-8');

try {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        http_response_code(405);
        echo json_encode(array('error' => 'Método no permitido'));
        exit;
    }

    $id_foto = isset($_POST['id_foto']) ? (int) $_POST['id_foto'] : 0;
    $nombre_foto = isset($_POST['nombre_foto']) ? trim($_POST['nombre_foto']) : '';
    $id_articulo = isset($_POST['id_articulo']) ? (int) $_POST['id_articulo'] : 0;

    if ($id_foto <= 0) {
        throw new Exception('ID de documento no válido');
    }
    if ($nombre_foto === '') {
        throw new Exception('Nombre de archivo no válido');
    }
    if ($id_articulo <= 0) {
        throw new Exception('ID de artículo no válido');
    }

    $conexion = conectar_bd();

    $st = mysqli_prepare(
        $conexion,
        'SELECT id, id_articulo_venta, src FROM articulos_venta_imagenes WHERE id = ? LIMIT 1'
    );
    if (!$st) {
        throw new Exception(mysqli_error($conexion));
    }
    mysqli_stmt_bind_param($st, 'i', $id_foto);
    mysqli_stmt_execute($st);
    $res = mysqli_stmt_get_result($st);
    $row = $res ? mysqli_fetch_assoc($res) : null;
    mysqli_stmt_close($st);

    if (!$row) {
        mysqli_close($conexion);
        throw new Exception('Documento no encontrado');
    }
    if ((int) $row['id_articulo_venta'] !== $id_articulo) {
        mysqli_close($conexion);
        throw new Exception('El documento no pertenece a este artículo');
    }
    if ($row['src'] !== $nombre_foto) {
        mysqli_close($conexion);
        throw new Exception('El nombre del archivo no coincide');
    }

    $del = mysqli_prepare($conexion, 'DELETE FROM articulos_venta_imagenes WHERE id = ? LIMIT 1');
    mysqli_stmt_bind_param($del, 'i', $id_foto);
    if (!mysqli_stmt_execute($del)) {
        mysqli_stmt_close($del);
        mysqli_close($conexion);
        throw new Exception('Error al eliminar: ' . mysqli_error($conexion));
    }
    mysqli_stmt_close($del);
    mysqli_close($conexion);

    $ruta_archivo = __DIR__ . '/../../../photos/' . $nombre_foto;
    if (file_exists($ruta_archivo) && !unlink($ruta_archivo)) {
        error_log('No se pudo eliminar el archivo físico: ' . $ruta_archivo);
    }

    if (function_exists('registrar_accion_usuario')) {
        $texto_action_user = 'Documento eliminado del artículo venta #' . $id_articulo;
        $id_action_user = '27';
        $url_completa = APP_URL . '/articulo.php?id=' . $id_articulo;
        $relItemAction = isset($id_type_Item) ? $id_type_Item : '0';
        registrar_accion_usuario($usuario_id, $id_action_user, $texto_action_user, $usuario_sucursal, $relItemAction, $url_completa);
    }

    echo json_encode(array(
        'success' => true,
        'message' => 'Documento eliminado correctamente',
        'id_foto' => $id_foto,
        'nombre_foto' => $nombre_foto,
    ));
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(array(
        'success' => false,
        'error' => $e->getMessage(),
    ));
}
