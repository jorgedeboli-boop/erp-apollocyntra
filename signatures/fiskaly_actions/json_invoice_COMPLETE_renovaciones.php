<?php
// ============================================
// VARIABLES DE LA FACTURA
// ============================================

$numero_factura = $factura['numero_factura'] ?? null;
$cliente_factura = $factura['cliente_factura'] ?? null;
$total_factura = $factura['total_factura'] ?? null;
$fecha_factura = $factura['fecha_factura'] ?? null;
$prefijo_factura = $factura['prefijo_factura'] ?? null;
$nombre_cliente = $factura['nombre_cliente'] ?? null;
$identificacion_fiscal_cliente = $factura['identificacion_fiscal_cliente'] ?? null;
$direccion_cliente = $factura['direccion_cliente'] ?? null;
$codigo_postal_cliente = $factura['codigo_postal_cliente'] ?? null;
$texto_facturas = $factura['texto_facturas'] ?? null;
$anyo_actual = date('Y');

$dt = new DateTime($fecha_factura, new DateTimeZone('Europe/Madrid'));
$fecha_factura_formatted = $dt->format(DateTime::RFC3339);

// Datos generales de la factura
$invoice_number = $prefijo_factura."-".$numero_factura;
$invoice_series = $prefijo_factura;
$invoice_text = $texto_facturas;
$invoice_full_amount = number_format($total_factura, 2, '.', '');

// ============================================
// DATOS DEL DESTINATARIO
// ============================================

$recipient_tax_number = $identificacion_fiscal_cliente;
$recipient_legal_name = $nombre_cliente;
$recipient_address_line = $direccion_cliente;
$recipient_postal_code = $codigo_postal_cliente;

// ============================================
// ITEMS DE LA FACTURA
// ============================================
// Los items vienen del array $articulos definido en crear_factura.php
// Si $articulos no está definido, usar valores por defecto para evitar errores
if (!isset($articulos) || !is_array($articulos) || empty($articulos)) {
    $articulos = [];
}



// ============================================
// CONSTRUIR JSON
// ============================================
$content_type = "COMPLETE";
$data_type = "SIMPLIFIED";
$json_fiskaly = array(
    "content" => array(
        "type" => $content_type,
        "data" => array(
            "type" => $data_type,
            "number" => $invoice_number,
            "series" => $invoice_series,
            "issued_at" => $fecha_factura_formatted,
            "text" => $invoice_text,
            "full_amount" => $invoice_full_amount,
            "items" => array_map(function($articulo) {
                // Formatear valores decimales con 2 dígitos decimales
                $unit_amount = isset($articulo['unit_amount']) ? (float)$articulo['unit_amount'] : 0.00;
                $unit_amount_formatted = number_format($unit_amount, 2, '.', '');
                
                $full_amount = isset($articulo['full_amount']) ? (float)$articulo['full_amount'] : 0.00;
                $full_amount_formatted = number_format($full_amount, 2, '.', '');
                
                $tax_base = isset($articulo['tax_base']) ? (float)$articulo['tax_base'] : 0.00;
                $tax_base_formatted = number_format($tax_base, 2, '.', '');
                
                return array(
                    "text" => $articulo['text'] ?? '',
                    "quantity" => (string)($articulo['quantity'] ?? 1),
                    "unit_amount" => $unit_amount_formatted,
                    "full_amount" => $full_amount_formatted,
                    "system" => array(
                        "type" => $articulo['item_system_type'] ?? 'REGULAR',
                        "category" => array(
                            "type" => $articulo['item_category_type'] ?? 'VAT'
                        )
                    )
                );
            }, $articulos)
        ),
        "recipients" => array(
            array(
                "id" => array(
                    "tax_number" => $recipient_tax_number,
                    "legal_name" => $recipient_legal_name
                ),
                "address_line" => $recipient_address_line,
                "postal_code" => $recipient_postal_code
            )
        )
    )
);

// ============================================
// CONVERTIR A JSON
// ============================================

$json_string = json_encode($json_fiskaly, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);

// ============================================
// MOSTRAR O USAR
// ============================================

// Opción 1: Mostrar en pantalla
/*
echo "<pre>";
echo $json_string;
echo "</pre>";
*/
// Opción 2: Devolver como respuesta AJAX
// header('Content-Type: application/json');
// echo $json_string;

// Opción 3: Enviar a Fiskaly
/*
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, 'https://api.fiskaly.com/api/v1/sign-es/...');
curl_setopt($ch, CURLOPT_HTTPHEADER, array(
    'Authorization: Bearer ' . $bearer_token,
    'Content-Type: application/json'
));
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, $json_string);
$response = curl_exec($ch);
curl_close($ch);
*/

?>