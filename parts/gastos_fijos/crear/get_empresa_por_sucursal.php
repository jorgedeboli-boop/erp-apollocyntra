<?php
require_once '../../../include/session.php';
require_once '../../../include/functions.php';

header('Content-Type: application/json');

try {
    $empresaId = obtener_rel_id_empresa_sesion();
    echo json_encode(['success' => true, 'empresa_id' => $empresaId]);
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
