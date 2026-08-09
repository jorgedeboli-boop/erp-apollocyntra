<?php
/**
 * Elimina comprobante de ventas_imagenes y el archivo en /photos.
 */
require_once __DIR__ . '/../../../include/session.php';
require_once __DIR__ . '/../../../include/functions.php';

header('Content-Type: application/json; charset=utf-8');

try {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        http_response_code(405);
        echo json_encode(['success' => false, 'error' => 'Método no permitido']);
        exit;
    }

    $id_registro = isset($_POST['id_foto']) ? (int) $_POST['id_foto'] : 0;
    $nombre_foto = isset($_POST['nombre_foto']) ? trim((string) $_POST['nombre_foto']) : '';
    $id_venta = isset($_POST['id_venta']) ? (int) $_POST['id_venta'] : 0;

    if ($id_registro <= 0) {
        throw new Exception('ID de registro no válido');
    }
    if ($nombre_foto === '') {
        throw new Exception('Nombre de archivo no válido');
    }
    if ($id_venta <= 0) {
        throw new Exception('ID de venta no válido');
    }

    if (!preg_match('/^[a-zA-Z0-9_\-\.]+$/', $nombre_foto)) {
        throw new Exception('Nombre de archivo no permitido');
    }

    $conexion = conectar_bd();
    $stmt = mysqli_prepare(
        $conexion,
        'SELECT id, src FROM ventas_imagenes WHERE id = ? AND id_venta = ? LIMIT 1'
    );
    if (!$stmt) {
        throw new Exception(mysqli_error($conexion));
    }
    mysqli_stmt_bind_param($stmt, 'ii', $id_registro, $id_venta);
    mysqli_stmt_execute($stmt);
    $res = mysqli_stmt_get_result($stmt);
    $row = $res ? mysqli_fetch_assoc($res) : null;
    mysqli_stmt_close($stmt);

    if (!$row || (string) $row['src'] !== $nombre_foto) {
        mysqli_close($conexion);
        throw new Exception('Registro no encontrado o no coincide con la venta');
    }

    $stmtD = mysqli_prepare($conexion, 'DELETE FROM ventas_imagenes WHERE id = ? AND id_venta = ?');
    if (!$stmtD) {
        throw new Exception(mysqli_error($conexion));
    }
    mysqli_stmt_bind_param($stmtD, 'ii', $id_registro, $id_venta);
    if (!mysqli_stmt_execute($stmtD)) {
        mysqli_stmt_close($stmtD);
        mysqli_close($conexion);
        throw new Exception(mysqli_stmt_error($stmtD));
    }
    mysqli_stmt_close($stmtD);
    mysqli_close($conexion);

    $ruta = __DIR__ . '/../../../photos/' . $nombre_foto;
    if (is_file($ruta)) {
        @unlink($ruta);
    }

    echo json_encode([
        'success' => true,
        'message' => 'Comprobante eliminado',
        'id_foto' => $id_registro,
        'nombre_foto' => $nombre_foto,
    ]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
