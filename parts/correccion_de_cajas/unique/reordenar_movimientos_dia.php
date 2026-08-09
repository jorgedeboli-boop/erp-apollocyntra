<?php
require_once '../../../include/session.php';
require_once '../../../include/functions.php';
require_once __DIR__ . '/correccion_cajas_helper.php';

header('Content-Type: application/json; charset=utf-8');

if (empty($_SESSION['usuario_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'No autorizado'], JSON_UNESCAPED_UNICODE);
    exit;
}

$idSucursal = isset($_POST['id_sucursal']) ? (int) $_POST['id_sucursal'] : 0;
$fecha = isset($_POST['fecha']) ? trim($_POST['fecha']) : '';
$ordenRaw = isset($_POST['orden']) ? $_POST['orden'] : [];

if ($idSucursal <= 0 || $fecha === '' || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $fecha)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Parámetros inválidos'], JSON_UNESCAPED_UNICODE);
    exit;
}

if (!is_array($ordenRaw) || empty($ordenRaw)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Debe enviar el orden de movimientos'], JSON_UNESCAPED_UNICODE);
    exit;
}

try {
    $conexion = conectar_bd();
    if (!$conexion) {
        throw new Exception('Error de conexión a la base de datos');
    }

    $tabla = correccion_cajas_tabla_movimientos($idSucursal);
    if (!correccion_cajas_tabla_existe($conexion, $tabla)) {
        throw new Exception('No existe tabla de movimientos para esta sucursal');
    }

    mysqli_begin_transaction($conexion);
    correccion_cajas_reordenar_movimientos_dia($conexion, $tabla, $fecha, $ordenRaw);
    mysqli_commit($conexion);
    mysqli_close($conexion);

    echo json_encode([
        'success' => true,
        'message' => 'Orden de movimientos actualizado correctamente',
    ], JSON_UNESCAPED_UNICODE);
} catch (Exception $e) {
    if (isset($conexion) && mysqli_ping($conexion)) {
        mysqli_rollback($conexion);
        mysqli_close($conexion);
    }

    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage(),
    ], JSON_UNESCAPED_UNICODE);
}
