<?php
require_once '../../../include/session.php';
require_once '../../../include/functions.php';

header('Content-Type: application/json');

try {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        http_response_code(405);
        echo json_encode(['success' => false, 'error' => 'Método no permitido']);
        exit;
    }

    $id = isset($_POST['id_servicio']) ? (int)$_POST['id_servicio'] : 0;
    if ($id <= 0) {
        throw new Exception('ID no válido');
    }

    $uid = isset($_SESSION['usuario_id']) ? (int)$_SESSION['usuario_id'] : 0;
    if (!$uid) {
        throw new Exception('Sesión no válida');
    }

    $conexion = conectar_bd();
    $stmt = mysqli_prepare($conexion, 'UPDATE servicios SET activo = 0, id_usuario_modificador = ? WHERE id = ?');
    mysqli_stmt_bind_param($stmt, 'ii', $uid, $id);
    if (!mysqli_stmt_execute($stmt)) {
        throw new Exception(mysqli_stmt_error($stmt));
    }
    mysqli_stmt_close($stmt);
    mysqli_close($conexion);

    echo json_encode(['success' => true, 'message' => 'Servicio desactivado']);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
