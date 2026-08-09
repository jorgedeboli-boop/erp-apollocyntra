<?php
/**
 * Archivo para obtener el estado de SMS según tipo de pago de una sucursal
 */

require_once '../../../include/session.php';
require_once '../../../include/functions.php';

header('Content-Type: application/json');

// Verificar que se hayan enviado los datos necesarios
if (!isset($_POST['id_sucursal']) || empty($_POST['id_sucursal'])) {
    echo json_encode([
        'success' => false,
        'error' => 'No se recibió el ID de sucursal'
    ]);
    exit();
}

if (!isset($_POST['tipo_pago']) || empty($_POST['tipo_pago'])) {
    echo json_encode([
        'success' => false,
        'error' => 'No se recibió el tipo de pago'
    ]);
    exit();
}

$id_sucursal = intval($_POST['id_sucursal']);
$tipo_pago = $_POST['tipo_pago'];

// Validar que el tipo de pago sea válido
if ($tipo_pago !== 'sms_contado' && $tipo_pago !== 'sms_otros_metodos_pago') {
    echo json_encode([
        'success' => false,
        'error' => 'Tipo de pago no válido'
    ]);
    exit();
}

try {
    // Consultar el estado de SMS según tipo de pago de la sucursal usando la función checkSMSsendTipoPago
    $sms_state = checkSMSsendTipoPago($tipo_pago, $id_sucursal);
    
    if ($sms_state === false) {
        echo json_encode([
            'success' => false,
            'error' => 'No se pudo obtener el estado de SMS según tipo de pago de la sucursal'
        ]);
        exit();
    }
    
    // Respuesta exitosa
    echo json_encode([
        'success' => true,
        'sms_state' => $sms_state
    ]);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
?>

