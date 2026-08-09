<?php
require_once '../../../include/session.php';

header('Content-Type: application/json');

try {
    $sucursalId = isset($_GET['sucursal_id']) ? (int)$_GET['sucursal_id'] : 0;
    if ($sucursalId <= 0) {
        echo json_encode(['success' => true, 'empresa_id' => 0]);
        exit;
    }

    $conexion = conectar_bd();
    if (!$conexion) {
        throw new Exception('Error de conexión');
    }

    $sql = "SELECT empresa_id FROM sucursal WHERE id_sucursal = ? LIMIT 1";
    $stmt = mysqli_prepare($conexion, $sql);
    mysqli_stmt_bind_param($stmt, 'i', $sucursalId);
    mysqli_stmt_execute($stmt);
    $res = mysqli_stmt_get_result($stmt);
    $row = $res ? mysqli_fetch_assoc($res) : null;

    $empresaId = $row ? (int)$row['empresa_id'] : 0;

    mysqli_stmt_close($stmt);
    mysqli_close($conexion);

    echo json_encode(['success' => true, 'empresa_id' => $empresaId]);
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}

