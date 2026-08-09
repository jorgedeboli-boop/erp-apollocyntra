<?php
/**
 * Opciones de filtros compartidas en listados de artículos.
 */

function articulos_filtro_imprimir_opciones(array $opciones, $placeholder = '') {
    if ($placeholder !== '') {
        echo '<option value="">' . htmlspecialchars($placeholder, ENT_QUOTES, 'UTF-8') . '</option>';
    }

    foreach ($opciones as $value => $label) {
        $valueEsc = htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
        $labelEsc = htmlspecialchars((string) $label, ENT_QUOTES, 'UTF-8');
        echo '<option value="' . $valueEsc . '">' . $labelEsc . '</option>';
    }
}

function articulos_filtro_opciones_tipo_venta() {
    return [
        'oro' => 'Oro',
        'plata' => 'Plata',
        'acero' => 'Acero',
        'otros' => 'Otros',
    ];
}

function articulos_filtro_imprimir_opciones_tipo_venta() {
    articulos_filtro_imprimir_opciones(articulos_filtro_opciones_tipo_venta(), 'Tipo');
}

function articulos_filtro_opciones_tipo_lote() {
    return [
        'oro' => 'Oro',
        'plata' => 'Plata',
    ];
}

function articulos_filtro_imprimir_opciones_tipo_lote() {
    articulos_filtro_imprimir_opciones(articulos_filtro_opciones_tipo_lote(), 'Tipo');
}

function articulos_filtro_opciones_estado_venta() {
    return [
        'enventa' => 'En venta',
        'vendido' => 'Vendido',
        'vendido_web' => 'Vendido web',
        'reservado' => 'Reservado',
        'enviado' => 'Enviado',
        'retirado' => 'Retirado',
        'mermado' => 'Mermado',
        'enreparacion' => 'En reparación',
        'noetiquetado_c' => 'No etiquetado C',
        'noetiquetado_u' => 'No etiquetado U',
    ];
}

function articulos_filtro_imprimir_opciones_estado_venta() {
    articulos_filtro_imprimir_opciones(articulos_filtro_opciones_estado_venta(), 'Estado');
}

function articulos_filtro_opciones_estado_lote() {
    return [
        'enviado_a_central' => 'Enviado a central',
        'Faltante' => 'Faltante',
        'Fundir' => 'Fundir',
        'Mermado' => 'Mermado',
        'Rechazado' => 'Rechazado',
        'Stock' => 'Stock',
    ];
}

function articulos_filtro_imprimir_opciones_estado_lote() {
    articulos_filtro_imprimir_opciones(articulos_filtro_opciones_estado_lote(), 'Estado');
}

function articulos_filtro_opciones_auditado() {
    return [
        'true' => 'Sí',
        'false' => 'No',
    ];
}

function articulos_filtro_imprimir_opciones_auditado() {
    articulos_filtro_imprimir_opciones(articulos_filtro_opciones_auditado(), 'Auditado');
}

function articulos_filtro_opciones_origen() {
    return [
        'central' => 'Central',
        'sucursal' => 'Sucursal',
    ];
}

function articulos_filtro_imprimir_opciones_origen() {
    articulos_filtro_imprimir_opciones(articulos_filtro_opciones_origen(), 'Origen');
}
