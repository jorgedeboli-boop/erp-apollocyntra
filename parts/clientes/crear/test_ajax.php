<?php
require_once '../../../include/session.php';
require_once '../../../include/functions.php';

header('Content-Type: application/json');

// Test simple para verificar que el archivo funciona
echo json_encode([
    'success' => true,
    'message' => 'Archivo AJAX funcionando correctamente',
    'action' => $_GET['action'] ?? 'no_action',
    'search' => $_GET['search'] ?? 'no_search'
]);
?>
