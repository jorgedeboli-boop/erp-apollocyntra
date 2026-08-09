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
    $total = 0;
    $defecto = 0;

    $r1 = mysqli_query($conexion, 'SELECT COUNT(*) AS total FROM cuentas_banco_empresas');
    if ($r1) {
        $total = (int) (mysqli_fetch_assoc($r1)['total'] ?? 0);
    }
    $r2 = mysqli_query($conexion, "SELECT COUNT(*) AS total FROM cuentas_banco_empresas WHERE por_defecto = 'true'");
    if ($r2) {
        $defecto = (int) (mysqli_fetch_assoc($r2)['total'] ?? 0);
    }
    mysqli_close($conexion);

    echo json_encode([
        'success' => true,
        'total_cuentas' => $total,
        'total_defecto' => $defecto,
    ], JSON_UNESCAPED_UNICODE);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => $e->getMessage()], JSON_UNESCAPED_UNICODE);
}
