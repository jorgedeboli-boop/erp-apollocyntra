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
            'existe' => false,
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
    
    // Consultar si existe la factura en la tabla facturas_fiskaly_cache y obtener todos los datos
    $query_check = "SELECT 
        numero_factura,
        cliente_factura,
        facturado_por,
        estado_factura,
        tipo_pago_factura,
        total_factura,
        fecha_factura,
        hora_factura,
        rel_id_venta,
        fecha_anulacion,
        prefijo_factura,
        rel_cliente_id,
        nombre_cliente,
        identificacion_fiscal_cliente,
        direccion_cliente,
        codigo_postal_cliente,
        formato_factura,
        invoice_id_fiskaly
    FROM facturas_fiskaly_cache 
    WHERE id_factura = ?
    LIMIT 1";
    $stmt_check = $mysqli_fiskalyapp->prepare($query_check);
    if (!$stmt_check) {
        $error_msg = $mysqli_fiskalyapp->error;
        throw new Exception('Error al preparar la consulta: ' . ($error_msg ? $error_msg : 'Error desconocido'));
    }
    // AND estado_cache = 'pendiente' 
    $stmt_check->bind_param('s', $factura_id_fiskaly);
    $stmt_check->execute();
    $result_check = $stmt_check->get_result();
    $row_check = $result_check->fetch_assoc();
    $stmt_check->close();
    
    $existe = ($row_check !== null);
    $datos_factura = null;
    
    // Si existe la factura, preparar todos los datos
    if ($existe && $row_check) {
        // Preparar los datos de la factura en variables
        $datos_factura = [
            'numero_factura' => $row_check['numero_factura'] ?? null,
            'cliente_factura' => $row_check['cliente_factura'] ?? null,
            'facturado_por' => $row_check['facturado_por'] ?? null,
            'estado_factura' => $row_check['estado_factura'] ?? null,
            'tipo_pago_factura' => $row_check['tipo_pago_factura'] ?? null,
            'total_factura' => $row_check['total_factura'] ?? null,
            'fecha_factura' => $row_check['fecha_factura'] ?? null,
            'hora_factura' => $row_check['hora_factura'] ?? null,
            'rel_id_venta' => $row_check['rel_id_venta'] ?? null,
            'fecha_anulacion' => $row_check['fecha_anulacion'] ?? null,
            'prefijo_factura' => $row_check['prefijo_factura'] ?? null,
            'rel_cliente_id' => $row_check['rel_cliente_id'] ?? null,
            'nombre_cliente' => $row_check['nombre_cliente'] ?? null,
            'identificacion_fiscal_cliente' => $row_check['identificacion_fiscal_cliente'] ?? null,
            'direccion_cliente' => $row_check['direccion_cliente'] ?? null,
            'codigo_postal_cliente' => $row_check['codigo_postal_cliente'] ?? null,
            'formato_factura' => $row_check['formato_factura'] ?? null,
            'invoice_id_fiskaly' => $row_check['invoice_id_fiskaly'] ?? null
        ];
    }
    
    echo json_encode([
        'success' => true,
        'existe' => $existe,
        'factura_id_fiskaly' => $factura_id_fiskaly,
        'datos_factura' => $datos_factura
    ]);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'existe' => false,
        'message' => $e->getMessage()
    ]);
}
?>
