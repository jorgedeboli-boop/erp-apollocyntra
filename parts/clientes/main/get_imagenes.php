<?php
/**
 * Archivo para obtener las imágenes del cliente desde la tabla fotos_app
 * Compatible con PHP 7.0
 */

require_once '../../../include/session.php';
require_once '../../../include/functions.php';
require_once __DIR__ . '/../../../camera/lib/imagenes_catalogo.php';

header('Content-Type: application/json');

try {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        http_response_code(405);
        echo json_encode(array('error' => 'Método no permitido'));
        exit;
    }

    $id_cliente = isset($_POST['id_cliente']) ? (int) $_POST['id_cliente'] : 0;

    if (!$id_cliente) {
        throw new Exception('ID de cliente no válido');
    }

    $imagenes = camera_catalog_imagenes_cliente($id_cliente);

    echo json_encode(array(
        'success' => true,
        'imagenes' => $imagenes,
        'total' => count($imagenes),
    ));
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(array(
        'success' => false,
        'error' => $e->getMessage(),
    ));
}
