<?php
/**
 * Eliminar documento/foto asociado a un gasto.
 */

require_once '../../../include/session.php';
require_once '../../../include/functions.php';

header('Content-Type: application/json');

try {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        http_response_code(405);
        echo json_encode(array('error' => 'Método no permitido'));
        exit;
    }

    $id_foto = isset($_POST['id_foto']) ? (int) $_POST['id_foto'] : 0;
    $nombre_foto = isset($_POST['nombre_foto']) ? trim($_POST['nombre_foto']) : '';
    $id_gasto = isset($_POST['id_gasto']) ? (int) $_POST['id_gasto'] : 0;

    if (!$id_foto) {
        throw new Exception('ID de foto no válido');
    }

    if (empty($nombre_foto)) {
        throw new Exception('Nombre de foto no válido');
    }

    if (!$id_gasto) {
        throw new Exception('ID de gasto no válido');
    }

    $conexion = conectar_bd();

    $query_info = 'SELECT id_gasto, nombre_foto FROM fotos_gastos WHERE id_foto = ?';
    $stmt_info = mysqli_prepare($conexion, $query_info);
    mysqli_stmt_bind_param($stmt_info, 'i', $id_foto);
    mysqli_stmt_execute($stmt_info);
    $result_info = mysqli_stmt_get_result($stmt_info);

    if (mysqli_num_rows($result_info) === 0) {
        mysqli_stmt_close($stmt_info);
        throw new Exception('Foto no encontrada en la base de datos');
    }

    $foto_info = mysqli_fetch_assoc($result_info);
    mysqli_stmt_close($stmt_info);

    if ((int) $foto_info['id_gasto'] !== $id_gasto) {
        throw new Exception('La foto no pertenece a este gasto');
    }

    if ($foto_info['nombre_foto'] !== $nombre_foto) {
        throw new Exception('El nombre de la foto no coincide');
    }

    $query_delete = 'DELETE FROM fotos_gastos WHERE id_foto = ? AND id_gasto = ?';
    $stmt_delete = mysqli_prepare($conexion, $query_delete);
    mysqli_stmt_bind_param($stmt_delete, 'ii', $id_foto, $id_gasto);

    if (!mysqli_stmt_execute($stmt_delete)) {
        throw new Exception('Error al eliminar de la base de datos: ' . mysqli_stmt_error($stmt_delete));
    }

    mysqli_stmt_close($stmt_delete);

    $ruta_archivo = '../../../photos/' . $nombre_foto;
    if (file_exists($ruta_archivo)) {
        if (!unlink($ruta_archivo)) {
            error_log('No se pudo eliminar el archivo físico: ' . $ruta_archivo);
        }
    }

    if (function_exists('registrar_accion_usuario')) {
        $texto_action_user = 'Documento eliminado del gasto';
        $id_action_user = '27';
        $url_completa = APP_URL . '/gasto.php?id=' . $id_gasto;
        $relItemAction = '45';
        registrar_accion_usuario($usuario_id, $id_action_user, $texto_action_user, $usuario_sucursal, $relItemAction, $url_completa);
    }

    mysqli_close($conexion);

    echo json_encode(array(
        'success' => true,
        'message' => 'Foto eliminada exitosamente',
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
