<?php
/**
 * Plantilla de exportación total para listados de movimientos.
 * Copiar como export_all.php en cada módulo listar.
 */
require_once '../../../include/session.php';

header('Content-Type: application/json');

try {
    define('MOVIMIENTOS_EXPORT_ALL', true);
    $_POST['start'] = 0;
    $_POST['length'] = 500000;
    $searchPlain = isset($_POST['search']) && !is_array($_POST['search'])
        ? trim((string) $_POST['search'])
        : (isset($_POST['search']['value']) ? trim((string) $_POST['search']['value']) : '');
    $_POST['search'] = ['value' => $searchPlain];
    $_POST['draw'] = isset($_POST['draw']) ? (int) $_POST['draw'] : 1;

    ob_start();
    include __DIR__ . '/load_list.php';
    $raw = ob_get_clean();

    $decoded = json_decode($raw, true);
    if (!is_array($decoded)) {
        throw new Exception('Respuesta inválida del listado');
    }
    if (isset($decoded['error'])) {
        throw new Exception((string) $decoded['error']);
    }

    echo json_encode([
        'success' => true,
        'data' => $decoded['data'] ?? [],
        'recordsFiltered' => $decoded['recordsFiltered'] ?? count($decoded['data'] ?? []),
    ]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage(),
    ]);
}
