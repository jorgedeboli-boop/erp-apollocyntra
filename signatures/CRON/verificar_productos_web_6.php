<?php

/**
 * Paso 6: desactivar en PrestaShop articulos reservados o retirados hoy en tienda (articulo_web).
 */

$conexion = cron_obtener_conexion();
$conexionWeb = cron_obtener_conexion_web();
if (!$conexion || !$conexionWeb) {
    cron_linea('ERROR verificar_productos_web_6: sin conexion a base de datos.');
    return;
}

$idUsuarioCron = 1;

cron_linea('>> Paso: verificar_productos_web_6');

$sqlProductoWeb = 'SELECT id_product FROM ps_product WHERE id_product = ? AND active = 1 LIMIT 1';
$stmtProductoWeb = mysqli_prepare($conexionWeb, $sqlProductoWeb);

$sqlUpdateProducto = "UPDATE ps_product SET active = '0', on_sale = '0' WHERE id_product = ? AND active = '1' LIMIT 1";
$stmtUpdateProducto = mysqli_prepare($conexionWeb, $sqlUpdateProducto);

$sqlUpdateProductoShop = "UPDATE ps_product_shop SET active = '0', on_sale = '0' WHERE id_product = ? AND active = '1' LIMIT 1";
$stmtUpdateProductoShop = mysqli_prepare($conexionWeb, $sqlUpdateProductoShop);

if (!$stmtProductoWeb || !$stmtUpdateProducto || !$stmtUpdateProductoShop) {
    cron_linea('ERROR verificar_productos_web_6 preparando consultas web.');
    return;
}

/**
 * @param mysqli $conexion
 * @param mysqli_stmt $stmtProductoWeb
 * @param mysqli_stmt $stmtUpdateProducto
 * @param mysqli_stmt $stmtUpdateProductoShop
 * @param string $sqlArticulos
 * @param string $descripcionCronTpl
 * @param string $comentariosTpl
 * @param string $accionTrazabilidad
 * @param int $idUsuarioCron
 * @return int
 */
function cron_web6_procesar_articulos(
    $conexion,
    $stmtProductoWeb,
    $stmtUpdateProducto,
    $stmtUpdateProductoShop,
    $sqlArticulos,
    $descripcionCronTpl,
    $comentariosTpl,
    $accionTrazabilidad,
    $idUsuarioCron
) {
    $total = 0;
    $resultado = mysqli_query($conexion, $sqlArticulos);
    if (!$resultado) {
        cron_linea('ERROR verificar_productos_web_6 consultando articulos: ' . mysqli_error($conexion));
        return 0;
    }

    while ($articulo = mysqli_fetch_assoc($resultado)) {
        $idPrestashop = isset($articulo['id_prestashop']) ? (int) $articulo['id_prestashop'] : 0;
        $sku = isset($articulo['id']) ? (string) $articulo['id'] : '';
        $sucursalCron = isset($articulo['id_sucursal_destino']) ? (int) $articulo['id_sucursal_destino'] : 0;

        if ($idPrestashop <= 0 || $sku === '') {
            continue;
        }

        mysqli_stmt_bind_param($stmtProductoWeb, 'i', $idPrestashop);
        mysqli_stmt_execute($stmtProductoWeb);
        $filaWeb = mysqli_fetch_assoc(mysqli_stmt_get_result($stmtProductoWeb));

        if (!$filaWeb || empty($filaWeb['id_product'])) {
            continue;
        }

        $descripcionCron = str_replace(
            array('%sku%', '%id_prestashop%'),
            array($sku, (string) $idPrestashop),
            $descripcionCronTpl
        );

        insert_global_cron($descripcionCron, $sucursalCron, 'Actualizadoweb', (string) $idUsuarioCron);

        mysqli_stmt_bind_param($stmtUpdateProducto, 'i', $idPrestashop);
        mysqli_stmt_execute($stmtUpdateProducto);

        mysqli_stmt_bind_param($stmtUpdateProductoShop, 'i', $idPrestashop);
        mysqli_stmt_execute($stmtUpdateProductoShop);

        $comentariosAccion = str_replace(
            array('%sku%', '%id_prestashop%'),
            array($sku, (string) $idPrestashop),
            $comentariosTpl
        );

        trazabilidad_articulos_venta(
            0,
            (string) $idUsuarioCron,
            $accionTrazabilidad,
            $comentariosAccion,
            $sucursalCron,
            $sku,
            '0'
        );

        $total++;
        cron_linea('  - SKU ' . $sku . ' | id_prestashop ' . $idPrestashop . ' | ' . $accionTrazabilidad);
    }

    mysqli_free_result($resultado);

    return $total;
}

$totalReservados = cron_web6_procesar_articulos(
    $conexion,
    $stmtProductoWeb,
    $stmtUpdateProducto,
    $stmtUpdateProductoShop,
    "SELECT id, id_prestashop, id_sucursal_destino
     FROM articulos_venta
     WHERE DATE(fecha_vendido) = CURDATE()
       AND estado = 'reservado'
       AND articulo_web = 'true'",
    'articulo reservado en tienda y atualizado en al web como fuera de stock SKU: %sku% id presatshop Nº %id_prestashop%',
    'El articulo reservado en tienda y atualizado en al web como fuera de stock SKU: %sku% id presatshop Nº %id_prestashop%',
    'actualiza_web_vendido_tienda',
    $idUsuarioCron
);

$totalRetirados = cron_web6_procesar_articulos(
    $conexion,
    $stmtProductoWeb,
    $stmtUpdateProducto,
    $stmtUpdateProductoShop,
    "SELECT id, id_prestashop, id_sucursal_destino
     FROM articulos_venta
     WHERE DATE(fecha_retirado) = CURDATE()
       AND estado = 'retirado'
       AND articulo_web = 'true'",
    'articulo mermado en tienda (antiguo retirado) en tienda y atualizado en al web como fuera de stock SKU: %sku% id presatshop Nº %id_prestashop%',
    'El articulo mermado en tienda (antiguo retirado) en tienda y atualizado en al web como fuera de stock SKU: %sku% id presatshop Nº %id_prestashop%',
    'actualiza_web_retirado_tienda',
    $idUsuarioCron
);

mysqli_stmt_close($stmtProductoWeb);
mysqli_stmt_close($stmtUpdateProducto);
mysqli_stmt_close($stmtUpdateProductoShop);

cron_linea('  - Actualizados reservados en tienda: ' . $totalReservados);
cron_linea('  - Actualizados retirados en tienda: ' . $totalRetirados);
