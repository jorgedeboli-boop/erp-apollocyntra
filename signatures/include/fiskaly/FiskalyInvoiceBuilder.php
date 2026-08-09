<?php

require_once __DIR__ . '/fiskaly_helpers.php';

class FiskalyInvoiceBuilder
{
    /**
     * @param array $factura  Fila de facturas_fiskaly_cache
     * @param array $articulos  Líneas preparadas (fiskaly_articulos_cache_a_payload)
     * @return array
     */
    public static function build(array $factura, array $articulos)
    {
        $tipoMaster = isset($factura['tipo_factura']) ? strtoupper(trim((string) $factura['tipo_factura'])) : 'COMPLETE';
        $formato = isset($factura['formato_factura']) ? strtolower(trim((string) $factura['formato_factura'])) : 'articulos';

        if ($tipoMaster === 'CORRECTING') {
            return self::buildCorrecting($factura, $articulos);
        }

        if ($tipoMaster === 'SIMPLIFIED') {
            if ($formato === 'oro_inversion') {
                return self::buildSimplificadaOroInversion($factura, $articulos);
            }

            // articulos y renovaciones comparten el mismo JSON SIMPLIFIED (REGULAR + IVA).
            return self::buildSimplificadaArticulos($factura, $articulos);
        }

        if ($formato === 'oro_inversion') {
            return self::buildOroInversion($factura, $articulos);
        }

        return self::buildArticulos($factura, $articulos);
    }

    /**
     * Factura rectificativa SIGN ES (CORRECTING) sobre una SIMPLIFIED/COMPLETE.
     * Método por defecto: DIFFERENCES (importes negativos para abono/devolución).
     *
     * @param array $factura
     * @param array $articulos
     * @return array
     */
    private static function buildCorrecting(array $factura, array $articulos)
    {
        $correctedId = trim((string) (isset($factura['_fiskaly_corrected_invoice_id']) ? $factura['_fiskaly_corrected_invoice_id'] : ''));
        if ($correctedId === '') {
            throw new Exception('FiskalyInvoiceBuilder: CORRECTING requiere _fiskaly_corrected_invoice_id (UUID factura original)');
        }

        $method = strtoupper(trim((string) (isset($factura['_fiskaly_correction_method']) ? $factura['_fiskaly_correction_method'] : 'DIFFERENCES')));
        if ($method !== 'DIFFERENCES' && $method !== 'SUBSTITUTION') {
            $method = 'DIFFERENCES';
        }

        $code = strtoupper(trim((string) (isset($factura['_fiskaly_correction_code']) ? $factura['_fiskaly_correction_code'] : 'CORRECTION_1')));
        if (!in_array($code, array('CORRECTION_1', 'CORRECTION_2', 'CORRECTION_3', 'CORRECTION_4', 'CORRECTION_AJ'), true)) {
            $code = 'CORRECTION_1';
        }

        $dataType = strtoupper(trim((string) (isset($factura['_fiskaly_correction_data_type']) ? $factura['_fiskaly_correction_data_type'] : 'SIMPLIFIED')));
        if ($dataType !== 'SIMPLIFIED' && $dataType !== 'COMPLETE') {
            $dataType = 'SIMPLIFIED';
        }

        $meta = self::metaFactura($factura);
        $items = array();

        foreach ($articulos as $articulo) {
            $qty = (float) (isset($articulo['quantity']) ? $articulo['quantity'] : 1);
            if ($qty == 0.0) {
                $qty = 1.0;
            }
            $unit = (float) (isset($articulo['unit_amount']) ? $articulo['unit_amount'] : 0);
            $full = (float) (isset($articulo['full_amount']) ? $articulo['full_amount'] : 0);
            if ($method === 'DIFFERENCES') {
                $qty = -abs($qty);
                $full = -abs($full);
                // unit_amount se mantiene positivo (precio unitario); full/quantity llevan el signo.
            }

            $items[] = array(
                'text' => isset($articulo['text']) ? $articulo['text'] : '',
                'quantity' => (string) $qty,
                'unit_amount' => fiskaly_format_decimal(abs($unit)),
                'full_amount' => fiskaly_format_decimal($full),
                'system' => fiskaly_build_item_system_simplificada($articulo),
            );
        }

        $fullAmount = (float) (isset($factura['total_factura']) ? $factura['total_factura'] : 0);
        if ($method === 'DIFFERENCES') {
            $fullAmount = -abs($fullAmount);
        }

        // SIGN ES CorrectingInvoice: required [type, invoice]. invoice = SimplifiedInvoice | CompleteInvoice.
        $invoiceBody = array(
            'type' => 'SIMPLIFIED',
            'number' => $meta['invoice_number_only'],
            'series' => $meta['invoice_series'],
            'issued_at' => $meta['issued_at'],
            'text' => $meta['invoice_text'],
            'full_amount' => fiskaly_format_decimal($fullAmount),
            'items' => $items,
        );

        if ($dataType === 'COMPLETE') {
            $invoiceBody = array(
                'type' => 'COMPLETE',
                'data' => $invoiceBody,
                'recipients' => array(
                    array(
                        'id' => array(
                            'tax_number' => $meta['recipient_tax_number'],
                            'legal_name' => $meta['recipient_legal_name'],
                        ),
                        'address_line' => $meta['recipient_address_line'],
                        'postal_code' => $meta['recipient_postal_code'],
                    ),
                ),
            );
        }

        $content = array(
            'type' => 'CORRECTING',
            'id' => $correctedId,
            'method' => $method,
            'invoice' => $invoiceBody,
        );
        // code es obligatorio en COMPLETE; en SIMPLIFIED Fiskaly asigna R5, pero lo enviamos igual.
        $content['code'] = $code;

        return array('content' => $content);
    }

    /**
     * Factura simplificada SIGN ES (sin destinatario).
     *
     * @param array $factura
     * @param array $articulos
     * @return array
     */
    private static function buildSimplificadaArticulos(array $factura, array $articulos)
    {
        $meta = self::metaFactura($factura);
        $items = array();

        foreach ($articulos as $articulo) {
            $items[] = array(
                'text' => isset($articulo['text']) ? $articulo['text'] : '',
                'quantity' => (string) (isset($articulo['quantity']) ? $articulo['quantity'] : 1),
                'unit_amount' => fiskaly_format_decimal(isset($articulo['unit_amount']) ? $articulo['unit_amount'] : 0),
                'full_amount' => fiskaly_format_decimal(isset($articulo['full_amount']) ? $articulo['full_amount'] : 0),
                'system' => fiskaly_build_item_system_simplificada($articulo),
            );
        }

        return array(
            'content' => array(
                'type' => 'SIMPLIFIED',
                'number' => $meta['invoice_number_only'],
                'series' => $meta['invoice_series'],
                'issued_at' => $meta['issued_at'],
                'text' => $meta['invoice_text'],
                'full_amount' => $meta['full_amount'],
                'items' => $items,
            ),
        );
    }

    /**
     * @param array $factura
     * @param array $articulos
     * @return array
     */
    private static function buildSimplificadaOroInversion(array $factura, array $articulos)
    {
        $meta = self::metaFactura($factura);
        $items = array();

        foreach ($articulos as $articulo) {
            $items[] = array(
                'text' => isset($articulo['text']) ? $articulo['text'] : '',
                'quantity' => (string) (isset($articulo['quantity']) ? $articulo['quantity'] : 1),
                'unit_amount' => fiskaly_format_decimal(isset($articulo['unit_amount']) ? $articulo['unit_amount'] : 0),
                'full_amount' => fiskaly_format_decimal(isset($articulo['full_amount']) ? $articulo['full_amount'] : 0),
                'system' => array(
                    'type' => 'INVESTMENT_GOLD',
                    'category' => array(
                        'type' => 'NO_VAT',
                        'cause' => 'TAXABLE_EXEMPT_6',
                    ),
                ),
            );
        }

        return array(
            'content' => array(
                'type' => 'SIMPLIFIED',
                'number' => $meta['invoice_number_only'],
                'series' => $meta['invoice_series'],
                'issued_at' => $meta['issued_at'],
                'text' => $meta['invoice_text'],
                'full_amount' => $meta['full_amount'],
                'items' => $items,
            ),
        );
    }

    /**
     * @param array $factura
     * @param array $articulos
     * @return array
     */
    private static function buildArticulos(array $factura, array $articulos)
    {
        $meta = self::metaFactura($factura);
        $tipoId = fiskaly_normalizar_tipo_identificacion(isset($factura['tipo_identificacion_cliente']) ? $factura['tipo_identificacion_cliente'] : '');
        $nacional = fiskaly_es_cliente_nacional($tipoId);

        $items = array();
        foreach ($articulos as $articulo) {
            $items[] = array(
                'text' => isset($articulo['text']) ? $articulo['text'] : '',
                'quantity' => (string) (isset($articulo['quantity']) ? $articulo['quantity'] : 1),
                'unit_amount' => fiskaly_format_decimal(isset($articulo['unit_amount']) ? $articulo['unit_amount'] : 0),
                'full_amount' => fiskaly_format_decimal(isset($articulo['full_amount']) ? $articulo['full_amount'] : 0),
                'system' => array(
                    'type' => isset($articulo['item_system_type']) ? $articulo['item_system_type'] : 'REGULAR',
                    'tax_base' => fiskaly_format_decimal(isset($articulo['tax_base']) ? $articulo['tax_base'] : 0),
                    'category' => array(
                        'type' => isset($articulo['item_category_type']) ? $articulo['item_category_type'] : 'VAT',
                    ),
                ),
            );
        }

        $recipientId = array(
            'tax_number' => $meta['recipient_tax_number'],
            'legal_name' => $meta['recipient_legal_name'],
        );
        if (!$nacional) {
            $recipientId['type'] = $tipoId;
            $recipientId['number'] = $meta['recipient_tax_number'];
            $recipientId['country_code'] = isset($factura['country_code']) ? $factura['country_code'] : 'ES';
        }

        return array(
            'content' => array(
                'type' => 'COMPLETE',
                'data' => array(
                    'type' => 'SIMPLIFIED',
                    'number' => $meta['invoice_number'],
                    'series' => $meta['invoice_series'],
                    'issued_at' => $meta['issued_at'],
                    'text' => $meta['invoice_text'],
                    'full_amount' => $meta['full_amount'],
                    'items' => $items,
                ),
                'recipients' => array(
                    array(
                        'id' => $recipientId,
                        'address_line' => $meta['recipient_address_line'],
                        'postal_code' => $meta['recipient_postal_code'],
                    ),
                ),
            ),
        );
    }

    /**
     * @param array $factura
     * @param array $articulos
     * @return array
     */
    private static function buildOroInversion(array $factura, array $articulos)
    {
        $meta = self::metaFactura($factura);

        $items = array();
        foreach ($articulos as $articulo) {
            $items[] = array(
                'text' => isset($articulo['text']) ? $articulo['text'] : '',
                'quantity' => (string) (isset($articulo['quantity']) ? $articulo['quantity'] : 1),
                'unit_amount' => fiskaly_format_decimal(isset($articulo['unit_amount']) ? $articulo['unit_amount'] : 0),
                'full_amount' => fiskaly_format_decimal(isset($articulo['full_amount']) ? $articulo['full_amount'] : 0),
                'system' => array(
                    'type' => 'INVESTMENT_GOLD',
                    'category' => array(
                        'type' => 'NO_VAT',
                        'cause' => 'TAXABLE_EXEMPT_6',
                    ),
                ),
            );
        }

        return array(
            'content' => array(
                'type' => 'COMPLETE',
                'data' => array(
                    'type' => 'SIMPLIFIED',
                    'number' => $meta['invoice_number'],
                    'series' => $meta['invoice_series'],
                    'issued_at' => $meta['issued_at'],
                    'text' => $meta['invoice_text'],
                    'full_amount' => $meta['full_amount'],
                    'items' => $items,
                ),
                'recipients' => array(
                    array(
                        'id' => array(
                            'tax_number' => $meta['recipient_tax_number'],
                            'legal_name' => $meta['recipient_legal_name'],
                        ),
                        'address_line' => $meta['recipient_address_line'],
                        'postal_code' => $meta['recipient_postal_code'],
                    ),
                ),
            ),
        );
    }

    /**
     * @param array $factura
     * @return array
     */
    private static function metaFactura(array $factura)
    {
        $fecha = isset($factura['fecha_factura']) ? $factura['fecha_factura'] : date('Y-m-d');
        $hora = isset($factura['hora_factura']) ? trim((string) $factura['hora_factura']) : '';
        if ($hora !== '' && preg_match('/^\d{2}:\d{2}:\d{2}$/', $hora)) {
            $fecha = substr((string) $fecha, 0, 10) . ' ' . $hora;
        }
        $dt = new DateTime($fecha, new DateTimeZone('Europe/Madrid'));
        $prefijo = strtoupper(trim((string) (isset($factura['prefijo_factura']) ? $factura['prefijo_factura'] : '')));
        $numero = isset($factura['numero_factura']) ? trim((string) $factura['numero_factura']) : '0';

        return array(
            'invoice_number' => $prefijo !== '' ? $prefijo . '-' . $numero : $numero,
            'invoice_number_only' => $numero,
            'invoice_series' => $prefijo,
            'issued_at' => $dt->format(DateTime::RFC3339),
            'invoice_text' => fiskaly_texto_factura_api(
                isset($factura['texto_facturas']) ? $factura['texto_facturas'] : '',
                isset($factura['_fiskaly_texto_max_bytes']) ? (int) $factura['_fiskaly_texto_max_bytes'] : 500
            ),
            'full_amount' => fiskaly_format_decimal(isset($factura['total_factura']) ? $factura['total_factura'] : 0),
            'recipient_tax_number' => isset($factura['identificacion_fiscal_cliente']) ? $factura['identificacion_fiscal_cliente'] : '',
            'recipient_legal_name' => isset($factura['nombre_cliente']) ? $factura['nombre_cliente'] : '',
            'recipient_address_line' => isset($factura['direccion_cliente']) ? $factura['direccion_cliente'] : '',
            'recipient_postal_code' => isset($factura['codigo_postal_cliente']) ? $factura['codigo_postal_cliente'] : '',
        );
    }
}
