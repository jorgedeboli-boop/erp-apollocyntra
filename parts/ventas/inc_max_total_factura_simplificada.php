<?php
/**
 * Tope máximo venta / factura simplificada (configuracion_general, id_config = 1).
 * Usado por crear/javascript.php, main/javascript.php y ventas_sucursal.
 */
if (!function_exists('obtenerMaximoTotalFacturaSimplificada')) {
    require_once __DIR__ . '/../../include/functions.php';
}

$max_total_factura_simplificada = obtenerMaximoTotalFacturaSimplificada();
if ($max_total_factura_simplificada === false || (float) $max_total_factura_simplificada <= 0) {
    $max_total_factura_simplificada = 400.0;
} else {
    $max_total_factura_simplificada = (float) $max_total_factura_simplificada;
}
