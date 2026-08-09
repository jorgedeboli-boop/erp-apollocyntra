<?php
require_once '../../include/session.php';
require_once '../../include/functions.php';
/**
 * Archivo para cargar poblaciones, provincias y países via AJAX
 * Versión optimizada que funciona correctamente
 * Compatible con PHP 7.0+
 */

// Verificar versión de PHP
if (version_compare(PHP_VERSION, '7.0.0', '<')) {
    http_response_code(500);
    echo json_encode(['error' => 'Se requiere PHP 7.0 o superior']);
    exit;
}

// Asegurar que no haya salida antes del JSON
ob_clean();

header('Content-Type: application/json');

// Obtener parámetros
$action = $_GET['action'] ?? '';
$search = $_GET['search'] ?? '';
$page = $_GET['page'] ?? 1;
$idprovincia = $_GET['idprovincia'] ?? '';
$idpais = $_GET['idpais'] ?? '';

// Debug: mostrar errores
error_reporting(E_ALL);
ini_set('display_errors', 1);

try {
    switch ($action) {
        case 'paises':
            $result = obtenerPaises($search, $page);
            break;
            
        case 'provincias':
            $result = obtenerProvincias($search, $page, $idpais);
            break;
            
        case 'poblaciones':
            $result = obtenerPoblaciones($search, $page, $idprovincia);
            break;
            
        case 'poblacion_detalle':
            $idpoblacion = $_GET['idpoblacion'] ?? '';
            $result = obtenerDetallePoblacion($idpoblacion);
            break;
            
        default:
            throw new Exception('Acción no válida: ' . $action);
    }
    
    echo json_encode($result);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'error' => true,
        'message' => $e->getMessage(),
        'file' => $e->getFile(),
        'line' => $e->getLine()
    ]);
}
?>