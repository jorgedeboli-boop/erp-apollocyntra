<?php
/**
 * Opciones de filtros en listados de autorizaciones.
 */

function autorizaciones_filtro_imprimir_opciones(array $opciones, $placeholder = '') {
    if ($placeholder !== '') {
        echo '<option value="">' . htmlspecialchars($placeholder, ENT_QUOTES, 'UTF-8') . '</option>';
    }

    foreach ($opciones as $value => $label) {
        $valueEsc = htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
        $labelEsc = htmlspecialchars((string) $label, ENT_QUOTES, 'UTF-8');
        echo '<option value="' . $valueEsc . '">' . $labelEsc . '</option>';
    }
}

function autorizaciones_imprimir_opciones_estado_usada() {
    autorizaciones_filtro_imprimir_opciones([
        'pendiente' => 'Pendiente',
        'autorizada' => 'Autorizada',
        'usada' => 'Usada',
        'nousada' => 'No usada',
    ], 'Estado (todos)');
}

function autorizaciones_imprimir_opciones_estado_devolucion() {
    autorizaciones_filtro_imprimir_opciones([
        'pendiente' => 'Pendiente',
        'autorizada' => 'Autorizada',
        'usada' => 'Usada',
        'nousada' => 'No Usada',
    ], 'Seleccionar Estado');
}

function autorizaciones_imprimir_opciones_estado_empeno() {
    autorizaciones_filtro_imprimir_opciones([
        'pendiente' => 'Pendiente',
        'autorizada' => 'Autorizada',
        'cancelada' => 'Cancelada',
    ], 'Seleccionar Estado');
}

function autorizaciones_imprimir_opciones_estado_firma() {
    autorizaciones_filtro_imprimir_opciones([
        'pendiente' => 'Pendiente',
        'autorizada' => 'Autorizada',
    ], 'Seleccionar Estado');
}

function autorizaciones_imprimir_opciones_estado_sms_enviado() {
    autorizaciones_filtro_imprimir_opciones([
        'true' => 'Enviado',
        'false' => 'No enviado',
    ], 'Seleccionar Estado SMS');
}

function autorizaciones_imprimir_opciones_estado_sms_autorizado() {
    autorizaciones_filtro_imprimir_opciones([
        'false' => 'Pendiente',
        'true' => 'Autorizado',
        'cancelada' => 'Cancelada',
    ], 'Seleccionar Estado Autorizado');
}
