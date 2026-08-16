<?php
/**
 * Opciones de filtros del control de etiquetado.
 */

function control_etiquetado_opciones_tipo() {
    return [
        'articulo' => 'Artículo',
        'envio' => 'Envío',
        'sucursal' => 'Tienda',
        'todo' => 'Todo',
        'false' => '----',
    ];
}

function control_etiquetado_imprimir_opciones_tipo() {
    echo '<option value="">Tipo</option>';

    foreach (control_etiquetado_opciones_tipo() as $value => $label) {
        $valueEsc = htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
        $labelEsc = htmlspecialchars((string) $label, ENT_QUOTES, 'UTF-8');
        echo '<option value="' . $valueEsc . '">' . $labelEsc . '</option>';
    }
}
