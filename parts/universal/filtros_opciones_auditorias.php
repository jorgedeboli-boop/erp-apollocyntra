<?php
/**
 * Opciones de filtros del listado de auditorías.
 */

function auditorias_listar_imprimir_opciones_estado() {
    echo '<option value="">Todos los estados</option>';
    $opciones = [
        'Auditando' => 'Auditando',
        'Finalizada' => 'Finalizada',
        'Cancelada' => 'Cancelada',
    ];
    foreach ($opciones as $value => $label) {
        $valueEsc = htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
        $labelEsc = htmlspecialchars($label, ENT_QUOTES, 'UTF-8');
        echo '<option value="' . $valueEsc . '">' . $labelEsc . '</option>';
    }
}
