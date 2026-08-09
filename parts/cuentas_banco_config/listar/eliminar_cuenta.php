<?php
require_once '../../../include/session.php';
require_once '../../../include/functions.php';

header('Content-Type: application/json; charset=utf-8');

try {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        http_response_code(405);
        echo json_encode(['success' => false, 'message' => 'Método no permitido']);
        exit;
    }
    if (!isset($_SESSION['usuario_id'])) {
        http_response_code(401);
        echo json_encode(['success' => false, 'message' => 'No autorizado']);
        exit;
    }

    $id = isset($_POST['id_cuenta_banco']) ? (int) $_POST['id_cuenta_banco'] : 0;
    if ($id <= 0) {
        echo json_encode(['success' => false, 'message' => 'ID no válido']);
        exit;
    }

    $conexion = conectar_bd();
    $stmt = mysqli_prepare($conexion, 'SELECT numerocuenta FROM cuentas_banco_empresas WHERE id_cuenta_banco = ? LIMIT 1');
    mysqli_stmt_bind_param($stmt, 'i', $id);
    mysqli_stmt_execute($stmt);
    $row = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
    mysqli_stmt_close($stmt);
    if (!$row) {
        mysqli_close($conexion);
        echo json_encode(['success' => false, 'message' => 'Cuenta no encontrada']);
        exit;
    }

    $stmtDel = mysqli_prepare($conexion, 'DELETE FROM cuentas_banco_empresas WHERE id_cuenta_banco = ? LIMIT 1');
    mysqli_stmt_bind_param($stmtDel, 'i', $id);
    $ok = mysqli_stmt_execute($stmtDel);
    mysqli_stmt_close($stmtDel);
    mysqli_close($conexion);

    if (!$ok) {
        throw new Exception('No se pudo eliminar');
    }

    echo json_encode([
        'success' => true,
        'message' => 'Cuenta "' . $row['numerocuenta'] . '" eliminada correctamente',
    ], JSON_UNESCAPED_UNICODE);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => $e->getMessage()], JSON_UNESCAPED_UNICODE);
}
