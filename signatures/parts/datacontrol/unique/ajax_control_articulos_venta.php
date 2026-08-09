<?php
require_once '../../../include/session.php';
require_once '../../../include/functions.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Método no permitido']);
    exit;
}

echo json_encode([
    'success' => false,
    'message' => 'Control «artículos venta» aún no está implementado: falta la definición de tablas origen/destino y reglas (responde con el mismo detalle que articulos_lotes).'
]);
exit;
