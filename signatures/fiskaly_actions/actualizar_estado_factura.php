<?php
require_once '../include/functions.php';

header('Content-Type: application/json');

try {
    // Verificar que sea una petición POST
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        http_response_code(405);
        echo json_encode([
            'success' => false,
            'message' => 'Método no permitido'
        ]);
        exit;
    }
    
    // Obtener datos del body JSON
    $json = file_get_contents('php://input');
    $data = json_decode($json, true);
    
    if (!$data) {
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'message' => 'Error al decodificar JSON'
        ]);
        exit;
    }
    
    // Validar que factura_id_fiskaly esté presente
    if (!isset($data['factura_id_fiskaly']) || empty($data['factura_id_fiskaly'])) {
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'message' => 'factura_id_fiskaly es requerido'
        ]);
        exit;
    }

    $id_empresa = isset($data['id_empresa']) ? (int)$data['id_empresa'] : 0;
    
    // Validar que se haya proporcionado id_empresa
    if (empty($id_empresa)) {
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'existe' => false,
            'message' => 'id_empresa es requerido'
        ]);
        exit;
    }

    $mysqli_fiskalyapp = obtenerConexionFiskalyPorEmpresa($id_empresa);

    
    // Verificar que existe la conexión a la base de datos Fiskaly
    if (!isset($mysqli_fiskalyapp)) {
        throw new Exception('Error de conexión a la base de datos Fiskaly: Variable $mysqli_fiskalyapp no está definida');
    }
    
    // Verificar que la conexión esté activa
    if ($mysqli_fiskalyapp->connect_errno) {
        throw new Exception('Error de conexión a la base de datos Fiskaly: ' . $mysqli_fiskalyapp->connect_error);
    }
    
    // Preparar datos para el UPDATE
    $factura_id_fiskaly = (int)$data['factura_id_fiskaly'];
    $invoice_id_fiskaly = isset($data['invoice_id_fiskaly']) ? $data['invoice_id_fiskaly'] : null;
    $InvoiceState = isset($data['InvoiceState']) ? $data['InvoiceState'] : null;
    $SignedInvoiceRegistrationState = isset($data['SignedInvoiceRegistrationState']) ? $data['SignedInvoiceRegistrationState'] : null;
    $registration_csv = isset($data['registration_csv']) ? $data['registration_csv'] : null;
    $SignedInvoiceCancellationState = isset($data['SignedInvoiceCancellationState']) ? $data['SignedInvoiceCancellationState'] : null;
    $tbai = isset($data['tbai']) ? $data['tbai'] : null;
    $url_validacion = isset($data['url_validacion']) ? $data['url_validacion'] : null;
    $imagen_codigo_qr = isset($data['imagen_codigo_qr']) ? $data['imagen_codigo_qr'] : null;
    $tipo_factura_master = isset($data['tipo_factura_master']) ? $data['tipo_factura_master'] : null;

    if($InvoiceState === 'ISSUED' && $SignedInvoiceRegistrationState === 'REGISTERED') {
        $estado_cache = "aceptada";
    } else if($InvoiceState === 'ISSUED' && $SignedInvoiceRegistrationState === 'PENDING') {
        $estado_cache = "pendiente";
    } else if($InvoiceState === 'ISSUED' && $SignedInvoiceRegistrationState === 'INVALID') {
        $estado_cache = "rechazada";
    } else if($InvoiceState === 'ISSUED' && $SignedInvoiceRegistrationState === 'REQUIRES_CORRECTION') {
        $estado_cache = "rechazada";
        $tipo_factura_master = "REMEDY";
    } else if($InvoiceState === 'ISSUED' && $SignedInvoiceRegistrationState === 'REQUIRES_INSPECTION') {
        $estado_cache = "rechazada";
        $tipo_factura_master = "REMEDY";
    } else if($InvoiceState === 'ISSUED' && $SignedInvoiceRegistrationState === 'CANCELLED') {
        $estado_cache = "rechazada";
    } else {
        $estado_cache = "rechazada";
    }
    
    // Preparar consulta UPDATE
    $query_update = "
        UPDATE facturas_fiskaly_cache SET
            invoice_id_fiskaly = ?,
            InvoiceState = ?,
            SignedInvoiceRegistrationState = ?,
            registration_csv = ?,
            SignedInvoiceCancellationState = ?,
            tbai = ?,
            url_validacion = ?,
            imagen_codigo_qr = ?,
            estado_cache = ?,
            tipo_factura = ?
        WHERE id_factura = ?
    ";
    
    $stmt_update = $mysqli_fiskalyapp->prepare($query_update);
    if (!$stmt_update) {
        $error_msg = $mysqli_fiskalyapp->error;
        throw new Exception('Error al preparar la consulta UPDATE: ' . ($error_msg ? $error_msg : 'Error desconocido'));
    }
    
    // Bind parameters (todos son strings excepto factura_id_fiskaly que es integer)
    $stmt_update->bind_param(
        'ssssssssssi',
        $invoice_id_fiskaly,
        $InvoiceState,
        $SignedInvoiceRegistrationState,
        $registration_csv,
        $SignedInvoiceCancellationState,
        $tbai,
        $url_validacion,
        $imagen_codigo_qr,
        $estado_cache,
        $tipo_factura_master,
        $factura_id_fiskaly
    );
    
    if (!$stmt_update->execute()) {
        $error_msg = $stmt_update->error;
        $stmt_update->close();
        throw new Exception('Error al ejecutar la consulta UPDATE: ' . ($error_msg ? $error_msg : 'Error desconocido'));
    }
    
    $stmt_update->close();
    
    echo json_encode([
        'success' => true,
        'message' => 'Estado de factura actualizado correctamente',
        'factura_id_fiskaly' => $factura_id_fiskaly
    ]);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
?>
