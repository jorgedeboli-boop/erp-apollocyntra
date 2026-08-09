<?php
require_once '../../../include/session.php';
require_once '../../../include/functions.php';

header('Content-Type: application/json; charset=utf-8');

if (!isset($_SESSION['usuario_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'No autorizado']);
    exit;
}

try {
    $conexion = conectar_bd();
    if (!$conexion) {
        throw new Exception('Error de conexión a la base de datos');
    }

    $total = 0;
    $activos = 0;

    $resTotal = mysqli_query($conexion, 'SELECT COUNT(*) AS total FROM bancos_config');
    if ($resTotal) {
        $row = mysqli_fetch_assoc($resTotal);
        $total = (int) ($row['total'] ?? 0);
    }

    $resActivos = mysqli_query($conexion, "SELECT COUNT(*) AS total FROM bancos_config WHERE estado_banco = 'true'");
    if ($resActivos) {
        $row = mysqli_fetch_assoc($resActivos);
        $activos = (int) ($row['total'] ?? 0);
    }

    mysqli_close($conexion);

    echo json_encode([
        'success' => true,
        'total_bancos' => $total,
        'total_activos' => $activos,
    ], JSON_UNESCAPED_UNICODE);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage(),
    ], JSON_UNESCAPED_UNICODE);
}
