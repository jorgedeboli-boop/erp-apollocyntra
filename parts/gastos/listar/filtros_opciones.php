<?php
/**
 * Opciones de filtros del listado de gastos.
 */
require_once __DIR__ . '/../../../include/functions.php';

function gastos_listar_datos_filtros() {
    static $cache = null;
    if ($cache !== null) {
        return $cache;
    }

    $cache = [
        'empresas' => [],
        'sucursales' => [],
        'proveedores' => [],
        'tipos_gasto' => [],
        'formas_pago' => [],
        'estados' => [
            ['id' => 'pendiente', 'nombre' => 'Pendiente'],
            ['id' => 'pagado', 'nombre' => 'Pagado'],
            ['id' => 'cancelado', 'nombre' => 'Cancelado'],
        ],
    ];

    $conexion = conectar_bd();
    if (!$conexion) {
        return $cache;
    }

    $queries = [
        'empresas' => 'SELECT id_empresa AS id, nombre_empresa AS nombre FROM empresas ORDER BY nombre_empresa',
        'sucursales' => 'SELECT id_sucursal AS id, nombre_sucursal AS nombre FROM sucursal ORDER BY nombre_sucursal',
        'proveedores' => 'SELECT id_proveedor AS id, nombre_proveedor AS nombre FROM proveedores ORDER BY nombre_proveedor',
        'tipos_gasto' => 'SELECT id_tipo_gasto AS id, nombre_tipo_gasto AS nombre FROM tipo_de_gasto ORDER BY nombre_tipo_gasto',
        'formas_pago' => 'SELECT id_forma_de_pago AS id, nombre_forma_de_pago AS nombre FROM formas_de_pago ORDER BY nombre_forma_de_pago',
    ];

    foreach ($queries as $key => $sql) {
        $result = mysqli_query($conexion, $sql);
        if (!$result) {
            continue;
        }
        while ($row = mysqli_fetch_assoc($result)) {
            $cache[$key][] = $row;
        }
    }

    mysqli_close($conexion);
    return $cache;
}

function gastos_listar_imprimir_opciones_filtro(array $items, $placeholder) {
    echo '<option value="">' . htmlspecialchars((string) $placeholder, ENT_QUOTES, 'UTF-8') . '</option>';
    foreach ($items as $item) {
        $id = isset($item['id']) ? (string) $item['id'] : '';
        $nombre = isset($item['nombre']) ? (string) $item['nombre'] : '';
        echo '<option value="' . htmlspecialchars($id, ENT_QUOTES, 'UTF-8') . '">'
            . htmlspecialchars($nombre, ENT_QUOTES, 'UTF-8') . '</option>';
    }
}

function gastos_listar_imprimir_opciones_empresas() {
    gastos_listar_imprimir_opciones_filtro(gastos_listar_datos_filtros()['empresas'], 'Empresas');
}

function gastos_listar_imprimir_opciones_sucursales() {
    gastos_listar_imprimir_opciones_filtro(gastos_listar_datos_filtros()['sucursales'], 'Sucursales');
}

function gastos_listar_imprimir_opciones_proveedores() {
    gastos_listar_imprimir_opciones_filtro(gastos_listar_datos_filtros()['proveedores'], 'Proveedores');
}

function gastos_listar_imprimir_opciones_estados() {
    gastos_listar_imprimir_opciones_filtro(gastos_listar_datos_filtros()['estados'], 'Estados');
}

function gastos_listar_imprimir_opciones_tipos_gasto() {
    gastos_listar_imprimir_opciones_filtro(gastos_listar_datos_filtros()['tipos_gasto'], 'Tipos de gasto');
}

function gastos_listar_imprimir_opciones_formas_pago() {
    gastos_listar_imprimir_opciones_filtro(gastos_listar_datos_filtros()['formas_pago'], 'Formas de pago');
}
