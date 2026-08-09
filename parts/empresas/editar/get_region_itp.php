<?php
/**
 * Obtiene la región ITP asociada a una provincia (provincias_system_itp → region_itp).
 */

require_once '../../../include/session.php';
require_once '../../../include/functions.php';

header('Content-Type: application/json; charset=utf-8');

try {
    if ($_SERVER['REQUEST_METHOD'] !== 'GET' && $_SERVER['REQUEST_METHOD'] !== 'POST') {
        http_response_code(405);
        echo json_encode(['success' => false, 'error' => 'Método no permitido']);
        exit;
    }

    $id_provincia = isset($_REQUEST['id_provincia']) ? (int) $_REQUEST['id_provincia'] : 0;

    $conexion = conectar_bd();
    if (!$conexion) {
        throw new Exception('Error de conexión a la base de datos');
    }

    $datos = obtener_region_itp_datos($conexion, $id_provincia, '');
    mysqli_close($conexion);

    echo json_encode([
        'success' => true,
        'texto' => $datos['texto'],
        'nombreRegion' => $datos['nombre_region_itp'],
        'impuesto_itp_compras' => $datos['impuesto_itp_compras'],
    ]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage(),
        'texto' => '',
    ]);
}
