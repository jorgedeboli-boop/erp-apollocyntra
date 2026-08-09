<?php
/**
 * Opciones de filtros del listado de clientes.
 */

function clientes_listar_opciones_estado() {
    return [
        'Habilitado' => 'Habilitado',
        'Deshabilitado' => 'Deshabilitado',
    ];
}

function clientes_listar_imprimir_opciones_filtro(array $opciones, $placeholder = '') {
    if ($placeholder !== '') {
        echo '<option value="">' . htmlspecialchars($placeholder, ENT_QUOTES, 'UTF-8') . '</option>';
    }

    foreach ($opciones as $value => $label) {
        $valueEsc = htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
        $labelEsc = htmlspecialchars((string) $label, ENT_QUOTES, 'UTF-8');
        echo '<option value="' . $valueEsc . '">' . $labelEsc . '</option>';
    }
}

function clientes_listar_imprimir_opciones_estado() {
    clientes_listar_imprimir_opciones_filtro(clientes_listar_opciones_estado(), 'Seleccionar Estado');
}

function clientes_listar_imprimir_opciones_tipo_identificacion($countryId) {
    echo '<option value="">Seleccionar tipo de identificación</option>';

    $countryId = (int) $countryId;
    if ($countryId <= 0) {
        return;
    }

    $conexion = conectar_bd();
    if (!$conexion) {
        return;
    }

    $query = "SELECT nombre_identificacion, texto_identificacion
              FROM tipo_identificacion
              WHERE country_id = ? AND state_tipo_identificacion = 'true'
              ORDER BY nombre_identificacion ASC";
    $stmt = mysqli_prepare($conexion, $query);
    if (!$stmt) {
        mysqli_close($conexion);
        return;
    }

    mysqli_stmt_bind_param($stmt, 'i', $countryId);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);

    while ($row = mysqli_fetch_assoc($result)) {
        $value = strtoupper($row['nombre_identificacion']);
        $label = $row['texto_identificacion'];
        $valueEsc = htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
        $labelEsc = htmlspecialchars($label, ENT_QUOTES, 'UTF-8');
        echo '<option value="' . $valueEsc . '">' . $labelEsc . '</option>';
    }

    mysqli_stmt_close($stmt);
    mysqli_close($conexion);
}

function clientes_listar_imprimir_opciones_provincia($countryId) {
    echo '<option value="">Seleccionar provincia</option>';

    $countryId = (int) $countryId;
    if ($countryId <= 0) {
        return;
    }

    $conexion = conectar_bd();
    if (!$conexion) {
        return;
    }

    $query = "SELECT id_province, nombreProvince FROM provincias WHERE id_rel_country = ? ORDER BY nombreProvince ASC";
    $stmt = mysqli_prepare($conexion, $query);
    if (!$stmt) {
        mysqli_close($conexion);
        return;
    }

    mysqli_stmt_bind_param($stmt, 'i', $countryId);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);

    while ($row = mysqli_fetch_assoc($result)) {
        $valueEsc = htmlspecialchars((string) $row['id_province'], ENT_QUOTES, 'UTF-8');
        $labelEsc = htmlspecialchars($row['nombreProvince'], ENT_QUOTES, 'UTF-8');
        echo '<option value="' . $valueEsc . '">' . $labelEsc . '</option>';
    }

    mysqli_stmt_close($stmt);
    mysqli_close($conexion);
}
