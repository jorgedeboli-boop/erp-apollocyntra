<?php
require_once '../../../include/session.php';
require_once '../../../include/functions.php';

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-cache, must-revalidate');

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

    $id_banco = isset($_POST['id_banco']) ? (int) $_POST['id_banco'] : 0;
    if ($id_banco <= 0) {
        echo json_encode(['success' => false, 'message' => 'ID de banco no válido']);
        exit;
    }

    $conexion = conectar_bd();
    $stmtVer = mysqli_prepare($conexion, 'SELECT nombre_banco FROM bancos_config WHERE id_banco = ? LIMIT 1');
    if (!$stmtVer) {
        throw new Exception(mysqli_error($conexion));
    }
    mysqli_stmt_bind_param($stmtVer, 'i', $id_banco);
    mysqli_stmt_execute($stmtVer);
    $resVer = mysqli_stmt_get_result($stmtVer);
    $banco = $resVer ? mysqli_fetch_assoc($resVer) : null;
    mysqli_stmt_close($stmtVer);

    if (!$banco) {
        mysqli_close($conexion);
        echo json_encode(['success' => false, 'message' => 'Banco no encontrado']);
        exit;
    }

    mysqli_begin_transaction($conexion);
    try {
        $stmtDel = mysqli_prepare($conexion, 'DELETE FROM bancos_config WHERE id_banco = ? LIMIT 1');
        if (!$stmtDel) {
            throw new Exception(mysqli_error($conexion));
        }
        mysqli_stmt_bind_param($stmtDel, 'i', $id_banco);
        if (!mysqli_stmt_execute($stmtDel)) {
            throw new Exception(mysqli_stmt_error($stmtDel));
        }
        mysqli_stmt_close($stmtDel);
        mysqli_commit($conexion);

        echo json_encode([
            'success' => true,
            'message' => 'Banco "' . $banco['nombre_banco'] . '" eliminado correctamente',
        ], JSON_UNESCAPED_UNICODE);
    } catch (Exception $e) {
        mysqli_rollback($conexion);
        throw $e;
    }
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Error interno del servidor: ' . $e->getMessage(),
    ], JSON_UNESCAPED_UNICODE);
} finally {
    if (isset($conexion) && $conexion instanceof mysqli) {
        mysqli_close($conexion);
    }
}
