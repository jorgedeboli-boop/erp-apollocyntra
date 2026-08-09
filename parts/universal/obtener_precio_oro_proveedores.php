<?php
/**
 * Devuelve los últimos precios oro 24k por proveedor de fundición (navbar).
 */

require_once __DIR__ . '/../../include/session.php';
require_once __DIR__ . '/../../include/functions.php';

header('Content-Type: application/json; charset=utf-8');

if (!isset($_SESSION['usuario_autenticado']) || $_SESSION['usuario_autenticado'] !== true) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'No autorizado']);
    exit;
}

$datos = obtener_datos_precio_oro_proveedores_navbar();

echo json_encode([
    'success' => true,
    'proveedores' => $datos['proveedores'],
    'ids_signature' => $datos['ids_signature'],
], JSON_UNESCAPED_UNICODE);
