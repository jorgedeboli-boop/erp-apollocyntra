<?php
require_once '../../../include/session.php';
require_once '../../../include/functions.php';
require_once __DIR__ . '/semanas_precio_helper.php';

header('Content-Type: application/json; charset=utf-8');

if (!isset($_SESSION['usuario_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'No autorizado']);
    exit;
}

$id_numero_semana = isset($_POST['id_numero_semana']) ? (int) $_POST['id_numero_semana'] : 0;
$precio_24_raw = isset($_POST['precio_24_mercado']) ? str_replace(',', '.', trim((string) $_POST['precio_24_mercado'])) : '';
$precio_oro_raw = isset($_POST['precio_gramo_oro']) ? str_replace(',', '.', trim((string) $_POST['precio_gramo_oro'])) : '';

if ($id_numero_semana <= 0) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Semana no válida']);
    exit;
}

if ($precio_24_raw === '' || !is_numeric($precio_24_raw)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Precio mercado no válido']);
    exit;
}

if ($precio_oro_raw === '' || !is_numeric($precio_oro_raw)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Precio fundición no válido']);
    exit;
}

$precio_24_mercado = (float) $precio_24_raw;
$precio_gramo_oro = (float) $precio_oro_raw;

try {
    $conexion = conectar_bd();
    if (!$conexion) {
        throw new Exception('Error de conexión a la base de datos');
    }

    $resultado = semanas_guardar_precios_semana_manual(
        $conexion,
        $id_numero_semana,
        $precio_24_mercado,
        $precio_gramo_oro
    );

    mysqli_close($conexion);

    echo json_encode([
        'success' => true,
        'message' => 'Precios actualizados correctamente',
        'data' => $resultado,
    ]);
} catch (Exception $e) {
    if (isset($conexion) && $conexion) {
        mysqli_close($conexion);
    }
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage(),
    ]);
}
