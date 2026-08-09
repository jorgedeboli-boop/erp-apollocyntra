<?php
/**
 * Archivo para obtener el estado de SMS empeño de una sucursal
 */

require_once '../../../include/session.php';
require_once '../../../include/functions.php';

header('Content-Type: application/json');

// Verificar que se haya enviado el ID de sucursal
if (!isset($_POST['id_sucursal']) || empty($_POST['id_sucursal'])) {
    echo json_encode([
        'success' => false,
        'error' => 'No se recibió el ID de sucursal'
    ]);
    exit();
}

$id_sucursal = intval($_POST['id_sucursal']);

try {
    // Consultar el estado de SMS empeño de la sucursal usando la función checkSMSsendEmpenyo
    $sms_state_empenyo = checkSMSsendEmpenyo($id_sucursal);
    
    if ($sms_state_empenyo === false) {
        echo json_encode([
            'success' => false,
            'error' => 'No se pudo obtener el estado de SMS empeño de la sucursal'
        ]);
        exit();
    }
    
    // Respuesta exitosa
    echo json_encode([
        'success' => true,
        'sms_state_empenyo' => $sms_state_empenyo
    ]);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
?>

