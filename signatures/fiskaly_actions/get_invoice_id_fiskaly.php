<?php
require_once '../include/functions.php';

header('Content-Type: application/json');

try {
    // Obtener factura_id_fiskaly por GET
    $factura_id_fiskaly = isset($_GET['factura_id_fiskaly']) ? trim($_GET['factura_id_fiskaly']) : '';

    // Validar que se haya proporcionado factura_id_fiskaly
    if (empty($factura_id_fiskaly)) {
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'invoice_id_fiskaly' => null,
            'message' => 'factura_id_fiskaly es requerido'
        ]);
        exit;
    }

    $id_empresa = isset($_GET['id_empresa']) ? (int)$_GET['id_empresa'] : 0;
    
    // Validar que se haya proporcionado id_empresa
    if (empty($id_empresa)) {
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'invoice_id_fiskaly' => null,
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
    
    // Consultar invoice_id_fiskaly de la tabla facturas_fiskaly_cache
    $query_check = "SELECT invoice_id_fiskaly, estado_cache, SignedInvoiceRegistrationState, SignedInvoiceCancellationState FROM facturas_fiskaly_cache WHERE id_factura = ? LIMIT 1";
    $stmt_check = $mysqli_fiskalyapp->prepare($query_check);
    if (!$stmt_check) {
        $error_msg = $mysqli_fiskalyapp->error;
        throw new Exception('Error al preparar la consulta: ' . ($error_msg ? $error_msg : 'Error desconocido'));
    }
    
    $stmt_check->bind_param('s', $factura_id_fiskaly);
    $stmt_check->execute();
    $result_check = $stmt_check->get_result();
    $row_check = $result_check->fetch_assoc();
    $stmt_check->close();
    
    $invoice_id_fiskaly = null;
    if ($row_check && !empty($row_check['invoice_id_fiskaly'])) {
        $invoice_id_fiskaly = $row_check['invoice_id_fiskaly'];
        $estado_cache = $row_check['estado_cache'];
        $SignedInvoiceRegistrationState = $row_check['SignedInvoiceRegistrationState']; // REGISTERED, PENDING, INVALID, REQUIRES_CORRECTION
        $SignedInvoiceCancellationState = $row_check['SignedInvoiceCancellationState']; // NOT_CANCELLED, CANCELLED, INVALID, REQUIRES_CORRECTION
    }
    
    echo json_encode([
        'success' => true,
        'invoice_id_fiskaly' => $invoice_id_fiskaly,
        'estado_cache' => $estado_cache,
        'SignedInvoiceRegistrationState' => $SignedInvoiceRegistrationState,    
        'SignedInvoiceCancellationState' => $SignedInvoiceCancellationState
    ]);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'invoice_id_fiskaly' => null,
        'message' => $e->getMessage()
    ]);
}
?>
