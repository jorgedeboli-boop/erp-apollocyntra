<?php
require_once '../../../include/session.php';
require_once '../../../include/functions.php';

header('Content-Type: application/json');

// Test básico
$action = $_GET['action'] ?? '';

switch ($action) {
    case 'paises':
        echo json_encode([
            'results' => [
                ['id' => '1', 'text' => 'España'],
                ['id' => '2', 'text' => 'Francia'],
                ['id' => '3', 'text' => 'Portugal']
            ],
            'pagination' => ['more' => false]
        ]);
        break;
        
    case 'provincias':
        echo json_encode([
            'results' => [
                ['id' => '1', 'text' => 'Madrid'],
                ['id' => '2', 'text' => 'Barcelona'],
                ['id' => '3', 'text' => 'Valencia']
            ],
            'pagination' => ['more' => false]
        ]);
        break;
        
    case 'poblaciones':
        echo json_encode([
            'results' => [
                ['id' => '1', 'text' => 'Madrid'],
                ['id' => '2', 'text' => 'Barcelona'],
                ['id' => '3', 'text' => 'Valencia']
            ],
            'pagination' => ['more' => false]
        ]);
        break;
        
    default:
        echo json_encode([
            'results' => [],
            'pagination' => ['more' => false]
        ]);
}
?>
