<?php
/**
 * Opciones de filtros del listado de recepción de lotes.
 */

function recepcion_lotes_listar_imprimir_opciones_estado()
{
    echo '<option value="">Todos los estados</option>';
    $opciones = [
        'false' => 'Pendiente',
        'enproceso' => 'En proceso',
        'finalizado' => 'Finalizado',
        'cancelado' => 'Cancelado',
    ];
    foreach ($opciones as $value => $label) {
        $valueEsc = htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
        $labelEsc = htmlspecialchars($label, ENT_QUOTES, 'UTF-8');
        echo '<option value="' . $valueEsc . '">' . $labelEsc . '</option>';
    }
}

function recepcion_lotes_listar_imprimir_opciones_resultado()
{
    echo '<option value="">Todos los resultados</option>';
    $opciones = [
        'favorable' => 'Favorable',
        'recahzado' => 'Rechazado',
        'faltante' => 'Faltante',
        'cancelado' => 'Cancelado',
    ];
    foreach ($opciones as $value => $label) {
        $valueEsc = htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
        $labelEsc = htmlspecialchars($label, ENT_QUOTES, 'UTF-8');
        echo '<option value="' . $valueEsc . '">' . $labelEsc . '</option>';
    }
}

function recepcion_lotes_listar_imprimir_opciones_tipo()
{
    echo '<option value="">Todos los tipos</option>';
    $opciones = [
        'oro' => 'Oro',
        'plata' => 'Plata',
    ];
    foreach ($opciones as $value => $label) {
        $valueEsc = htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
        $labelEsc = htmlspecialchars($label, ENT_QUOTES, 'UTF-8');
        echo '<option value="' . $valueEsc . '">' . $labelEsc . '</option>';
    }
}
