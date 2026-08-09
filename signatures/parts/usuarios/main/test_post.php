<?php
// Archivo de prueba POST para verificar comunicación
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id_usuario = isset($_POST['id_usuario']) ? (int)$_POST['id_usuario'] : 0;
    
    $response = array(
        'success' => true,
        'test' => 'POST funcionando correctamente',
        'id_recibido' => $id_usuario,
        'timestamp' => date('Y-m-d H:i:s'),
        'method' => 'POST'
    );
} else {
    $response = array(
        'success' => false,
        'error' => 'Método no permitido',
        'method' => $_SERVER['REQUEST_METHOD']
    );
}

echo json_encode($response);
?>
