<?php
/**
 * Opciones estáticas de filtros de envíos (empresa/sucursal vía AJAX).
 */

if (!function_exists('listado_semanas_imprimir_opciones')) {
    require_once __DIR__ . '/../../include/functions.php';
}

function envios_listar_opciones_estado() {
    return [
        'enviado_central' => 'Enviado a Central',
        'recibido_central' => 'Recibido en Central',
        'envio_cancelado' => 'Envío Cancelado',
        'pendiente_envio' => 'Pendiente de Envío',
        'envio_rechazado' => 'Envío Rechazado',
        'envio_auditado' => 'Envío Auditado',
        'auditando_envio' => 'Auditando Envío',
    ];
}

function envios_listar_imprimir_opciones_estado() {
    echo '<option value="">Todos los estados</option>';

    foreach (envios_listar_opciones_estado() as $value => $label) {
        $valueEsc = htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
        $labelEsc = htmlspecialchars((string) $label, ENT_QUOTES, 'UTF-8');
        echo '<option value="' . $valueEsc . '">' . $labelEsc . '</option>';
    }
}

function envios_listar_imprimir_opciones_semana() {
    listado_semanas_imprimir_opciones(0, 0, 'Todas las semanas');
}
