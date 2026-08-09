<?php
require_once '../include/functions.php';

header('Content-Type: application/json');

try {
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
    
    // Obtener todas las variables requeridas
    $factura_id_fiskaly = isset($data['factura_id_fiskaly']) ? trim($data['factura_id_fiskaly']) : '';
    $accessToken = isset($data['accessToken']) ? trim($data['accessToken']) : '';
    $refreshToken = isset($data['refreshToken']) ? trim($data['refreshToken']) : '';
    $expiresAt = isset($data['expiresAt']) ? trim($data['expiresAt']) : '';
    $environment = isset($data['environment']) ? trim($data['environment']) : '';
    $orgId = isset($data['orgId']) ? trim($data['orgId']) : '';
    $idClient = isset($data['idClient']) ? trim($data['idClient']) : '';
    $contribuyente = isset($data['contribuyente']) ? trim($data['contribuyente']) : '';
    // Obtener id_empresa
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

    
    // Validar que todas las variables existan
    $variables_requeridas = [
        'factura_id_fiskaly' => $factura_id_fiskaly,
        'accessToken' => $accessToken,
        'refreshToken' => $refreshToken,
        'expiresAt' => $expiresAt,
        'environment' => $environment,
        'orgId' => $orgId,
        'idClient' => $idClient,
        'contribuyente' => $contribuyente,
        'id_empresa' => $id_empresa
    ];
    
    $variables_faltantes = [];
    foreach ($variables_requeridas as $nombre => $valor) {
        if (empty($valor)) {
            $variables_faltantes[] = $nombre;
        }
    }
    
    if (!empty($variables_faltantes)) {
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'message' => 'Faltan las siguientes variables requeridas: ' . implode(', ', $variables_faltantes)
        ]);
        exit;
    }

    // Verificar que existe la conexión a la base de datos Fiskaly
    if (!isset($mysqli_fiskalyapp)) {
        throw new Exception('Error de conexión a la base de datos Fiskaly: Variable $mysqli_fiskalyapp no está definida');
    }
    
    // Verificar que la conexión esté activa
    if ($mysqli_fiskalyapp->connect_errno) {
        throw new Exception('Error de conexión a la base de datos Fiskaly: ' . $mysqli_fiskalyapp->connect_error);
    }
    
    // AQUI OBTENDRAS LOS DATOS DE LA FACTURA EN LA BASE DE DATOS FISKALY
    $query_factura = "SELECT * FROM facturas_fiskaly_cache WHERE id_factura = ?";
    $stmt_factura = $mysqli_fiskalyapp->prepare($query_factura);
    if (!$stmt_factura) {
        $error_msg = $mysqli_fiskalyapp->error;
        throw new Exception('Error al preparar la consulta: ' . ($error_msg ? $error_msg : 'Error desconocido'));
    }
    
    $stmt_factura->bind_param('i', $factura_id_fiskaly);
    $stmt_factura->execute();
    $result_factura = $stmt_factura->get_result();
    $factura = $result_factura->fetch_assoc();
    $stmt_factura->close();

    // Verificar si existe la factura
    if (!$factura) {
        http_response_code(404);
        echo json_encode([
            'success' => false,
            'message' => 'Factura no encontrada en la tabla facturas_fiskaly_cache'
        ]);
        exit;
    }
    
    // AQUI OBTEN TODOS LOS VARIABLES DE LA FACTURA EN LA BASE DE DATOS FISKALY
    $numero_factura = $factura['numero_factura'] ?? null;
    $cliente_factura = $factura['cliente_factura'] ?? null;
    $total_factura = $factura['total_factura'] ?? null;
    $fecha_factura = $factura['fecha_factura'] ?? null;
    $prefijo_factura = $factura['prefijo_factura'] ?? null;
    $nombre_cliente = $factura['nombre_cliente'] ?? null;
    $tipo_identificacion_cliente = $factura['tipo_identificacion_cliente'] ?? null;
    $identificacion_fiscal_cliente = $factura['identificacion_fiscal_cliente'] ?? null;
    $direccion_cliente = $factura['direccion_cliente'] ?? null;
    $codigo_postal_cliente = $factura['codigo_postal_cliente'] ?? null;
    $tipo_factura = $factura['tipo_factura'] ?? null;
    $texto_facturas = $factura['texto_facturas'] ?? null;
    $formato_factura = $factura['formato_factura'] ?? null;
    $country_code = $factura['country_code'] ?? null;
    $invoice_id_fiskaly = $factura['invoice_id_fiskaly'] ?? null;
    // Comprobar que todas las variables de la factura existen y tienen contenido
    $variables_factura = [
        'numero_factura' => $numero_factura,
        'cliente_factura' => $cliente_factura,
        'total_factura' => $total_factura,
        'fecha_factura' => $fecha_factura,
        'prefijo_factura' => $prefijo_factura,
        'nombre_cliente' => $nombre_cliente,
        'tipo_identificacion_cliente' => $tipo_identificacion_cliente,
        'identificacion_fiscal_cliente' => $identificacion_fiscal_cliente,
        'country_code' => $country_code,
        'direccion_cliente' => $direccion_cliente,
        'codigo_postal_cliente' => $codigo_postal_cliente,
        'tipo_factura' => $tipo_factura,
        'texto_facturas' => $texto_facturas,
        'formato_factura' => $formato_factura
    ];
    
    $variables_factura_faltantes = [];
    foreach ($variables_factura as $nombre => $valor) {
        if ($valor === null || $valor === '' || (is_numeric($valor) && $valor == 0 && $nombre !== 'cliente_factura')) {
            $variables_factura_faltantes[] = $nombre;
        }
    }
    
    if (!empty($variables_factura_faltantes)) {
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'message' => 'Faltan datos en las siguientes variables de la factura: ' . implode(', ', $variables_factura_faltantes),
            'redirect' => true
        ]);
        exit;
    }

    // ARTICULOS DE LA FACTURA
    $query_articulos = "SELECT 
        id_rel_fac_art,
        id_rel_sucursal,
        id_rel_factura,
        id_rel_articulo,
        fecha_factura,
        precio_rel_articulo,
        precio_coste_articulo,
        descripcion_articulo_rel,
        rel_factura_id_fiskaly,
        tipo_iva_articulo,
        system_codigo_regimen,
        beneficio_articulo,
        tax_base,
        precio_venta_sin_iva
    FROM facturas_fiskaly_rel_articulos_cache 
    WHERE rel_factura_id_fiskaly = ?";
    
    $stmt_articulos = $mysqli_fiskalyapp->prepare($query_articulos);
    if (!$stmt_articulos) {
        $error_msg = $mysqli_fiskalyapp->error;
        throw new Exception('Error al preparar la consulta de artículos: ' . ($error_msg ? $error_msg : 'Error desconocido'));
    }
    
    $stmt_articulos->bind_param('i', $factura_id_fiskaly);
    $stmt_articulos->execute();
    $result_articulos = $stmt_articulos->get_result();
    
    $articulos = [];
    while ($row_articulo = $result_articulos->fetch_assoc()) {
        // Convertir tipo_iva_articulo a vat_type
        $tipo_iva_articulo = $row_articulo['tipo_iva_articulo'] ?? null;
        $vat_type = $tipo_iva_articulo; // Asignar directamente, o convertir según necesidad

        $system_codigo_regimen = $row_articulo['system_codigo_regimen'] ?? null;
        $type = $system_codigo_regimen;
        if($type == 'REBU'){
            $item_system_type = 'ANTIQUES';
        }elseif($type == 'INVERSION'){
            $item_system_type = 'INVESTMENT_GOLD';
        }elseif($type == 'GENERAL'){
            $item_system_type = 'REGULAR';
        }
        $quantity = 1;
        $item_category_type = 'VAT';
        $articulos[] = [
            'text' => $row_articulo['descripcion_articulo_rel'] ?? null,
            'quantity' => $quantity,
            'unit_amount' => $row_articulo['precio_venta_sin_iva'] ?? null,
            'full_amount' => $row_articulo['precio_rel_articulo'] ?? null,
            'item_system_type' => $item_system_type,
            'tax_base' => $row_articulo['tax_base'] ?? null,
            'item_category_type' => $item_category_type            
        ];
    }
    $stmt_articulos->close();

    if($formato_factura == 'articulos'){
        require_once 'json_invoice_REMEDY.php';
    }elseif($formato_factura == 'oro_inversion'){
        require_once 'json_invoice_REMEDY_oro_inversion.php';
    }


    // Verificar que $json_string esté definida
    if (!isset($json_string)) {
        throw new Exception('Error: $json_string no está definida después de incluir json_invoice_REMEDY.php o json_invoice_REMEDY_oro_inversion.php');
    }
    
    // Todas las variables están presentes, continuar con el proceso
    echo json_encode([
        'success' => true,
        'message' => 'Todas las variables recibidas correctamente',
        'json_fiskaly' => $json_string,
        'invoice_id_fiskaly' => $invoice_id_fiskaly
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
