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
    $conLogotipo = 0;
    $fechaUltimo = null;

    $resTotal = mysqli_query($conexion, 'SELECT COUNT(*) AS total FROM sellos');
    if ($resTotal) {
        $row = mysqli_fetch_assoc($resTotal);
        $total = (int) ($row['total'] ?? 0);
    }

    $resLogotipo = mysqli_query($conexion, "SELECT COUNT(*) AS total FROM sellos WHERE sello_logotipo = 'true'");
    if ($resLogotipo) {
        $row = mysqli_fetch_assoc($resLogotipo);
        $conLogotipo = (int) ($row['total'] ?? 0);
    }

    $resFecha = mysqli_query($conexion, 'SELECT fecha_creacion FROM sellos ORDER BY fecha_creacion DESC LIMIT 1');
    if ($resFecha && mysqli_num_rows($resFecha) > 0) {
        $row = mysqli_fetch_assoc($resFecha);
        $fechaUltimo = $row['fecha_creacion'] ?? null;
    }

    mysqli_close($conexion);

    echo json_encode([
        'success' => true,
        'total_sellos' => $total,
        'total_con_logotipo' => $conLogotipo,
        'fecha_ultimo' => $fechaUltimo,
    ], JSON_UNESCAPED_UNICODE);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage(),
    ], JSON_UNESCAPED_UNICODE);
}
