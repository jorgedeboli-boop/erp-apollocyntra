<?php
// Archivo de prueba para verificar JSON
header('Content-Type: application/json');

$response = array(
    'success' => true,
    'test' => 'JSON funcionando correctamente',
    'timestamp' => date('Y-m-d H:i:s')
);

echo json_encode($response);
?>
