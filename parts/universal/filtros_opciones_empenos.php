<?php
/**
 * Opciones de filtros compartidas para listados de empeños.
 */
require_once __DIR__ . '/../lotes/listar/filtros_opciones.php';

function empenos_listar_opciones_estado() {
    return [
        'vencido' => 'Empeños vencidos',
        'fundido' => 'Lotes fundidos',
        'retirado' => 'Empeños retirados',
        'liberado' => 'Lotes liberados',
        'enfecha' => 'Empeños en fecha',
        'intervenido' => 'Lotes intervenidos',
        'perdido' => 'Empeños perdidos',
        'enviado' => 'Empeños enviados',
        'anulado' => 'Lotes anulados',
    ];
}

function empenos_listar_opciones_perdible() {
    return [
        'true' => 'Sí',
        'false' => 'No',
    ];
}

function empenos_listar_imprimir_opciones_estado() {
    lotes_listar_imprimir_opciones_filtro(empenos_listar_opciones_estado(), 'Estado');
}

function empenos_listar_imprimir_opciones_perdible() {
    lotes_listar_imprimir_opciones_filtro(empenos_listar_opciones_perdible(), 'Perdible');
}
