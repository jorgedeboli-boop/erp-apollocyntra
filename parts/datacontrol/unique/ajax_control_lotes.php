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
    'message' => 'Control «lotes» aún no está implementado: indica si la fuente es lotes_<sucursal>, tabla central lotes, y criterios de coincidencia para registrar en control_articulos_tablas.'
]);
exit;
