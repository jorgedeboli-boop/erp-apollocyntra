<?php
/**
 * Verificar teléfono o identificación (usa funciones ya definidas en include/functions.php).
 */
ob_start();
require_once __DIR__ . '/../../../include/session.php';
require_once __DIR__ . '/../../../include/functions.php';
ob_end_clean();

header('Content-Type: application/json');

$action = $_GET['action'] ?? '';
$valor = $_GET['valor'] ?? '';

try {
    switch ($action) {
        case 'verificar_telefono':
            $resultado = verificarTelefono($valor);
            break;
        case 'verificar_identificacion':
            $resultado = verificarIdentificacion($valor);
            break;
        default:
            throw new Exception('Acción no válida: ' . $action);
    }

    echo json_encode($resultado, JSON_UNESCAPED_UNICODE);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'error' => true,
        'message' => $e->getMessage()
    ], JSON_UNESCAPED_UNICODE);
}
