<?php

/**
 * Paso 5: sincronizar precio de venta de la app a PrestaShop si cambio hoy.
 */

$conexion = cron_obtener_conexion();
$conexionWeb = cron_obtener_conexion_web();
if (!$conexion || !$conexionWeb) {
    cron_linea('ERROR verificar_productos_web_5: sin conexion a base de datos.');
    return;
}

$idUsuarioCron = 1;

cron_linea('>> Paso: verificar_productos_web_5');

$sqlArticulos = "SELECT id, id_prestashop, id_sucursal_destino, precio
                 FROM articulos_venta
                 WHERE articulo_web = 'true'
                   AND update_register = CURDATE()
                   AND estado = 'enventa'";
$resultadoArticulos = mysqli_query($conexion, $sqlArticulos);
if (!$resultadoArticulos) {
    cron_linea('ERROR verificar_productos_web_5 consultando articulos: ' . mysqli_error($conexion));
    return;
}

$sqlSpecificPrice = 'SELECT id_specific_price FROM ps_specific_price WHERE id_product = ? LIMIT 1';
$stmtSpecificPrice = mysqli_prepare($conexionWeb, $sqlSpecificPrice);

$sqlPriceProduct = 'SELECT price FROM ps_product WHERE id_product = ? LIMIT 1';
$stmtPriceProduct = mysqli_prepare($conexionWeb, $sqlPriceProduct);

$sqlUpdateProductPrice = "UPDATE ps_product SET price = ? WHERE id_product = ? AND active = '1' LIMIT 1";
$stmtUpdateProductPrice = mysqli_prepare($conexionWeb, $sqlUpdateProductPrice);

$sqlUpdateProductShopPrice = "UPDATE ps_product_shop SET price = ? WHERE id_product = ? AND active = '1' LIMIT 1";
$stmtUpdateProductShopPrice = mysqli_prepare($conexionWeb, $sqlUpdateProductShopPrice);

$sqlUpdateSpecificPrice = "UPDATE ps_specific_price SET price = '-1.000000', reduction = ? WHERE id_specific_price = ? LIMIT 1";
$stmtUpdateSpecificPrice = mysqli_prepare($conexionWeb, $sqlUpdateSpecificPrice);

if (
    !$stmtSpecificPrice || !$stmtPriceProduct || !$stmtUpdateProductPrice
    || !$stmtUpdateProductShopPrice || !$stmtUpdateSpecificPrice
) {
    cron_linea('ERROR verificar_productos_web_5 preparando consultas web.');
    mysqli_free_result($resultadoArticulos);
    return;
}

$totalActualizados = 0;
$totalSinCambio = 0;

while ($articulo = mysqli_fetch_assoc($resultadoArticulos)) {
    $idPrestashop = isset($articulo['id_prestashop']) ? (int) $articulo['id_prestashop'] : 0;
    $sku = isset($articulo['id']) ? (string) $articulo['id'] : '';
    $sucursalCron = isset($articulo['id_sucursal_destino']) ? (int) $articulo['id_sucursal_destino'] : 0;
    $precio = isset($articulo['precio']) ? (float) $articulo['precio'] : 0;

    if ($idPrestashop <= 0 || $sku === '') {
        continue;
    }

    mysqli_stmt_bind_param($stmtSpecificPrice, 'i', $idPrestashop);
    mysqli_stmt_execute($stmtSpecificPrice);
    $filaSpecific = mysqli_fetch_assoc(mysqli_stmt_get_result($stmtSpecificPrice));
    $idSpecificPrice = $filaSpecific && isset($filaSpecific['id_specific_price'])
        ? (int) $filaSpecific['id_specific_price']
        : 0;

    mysqli_stmt_bind_param($stmtPriceProduct, 'i', $idPrestashop);
    mysqli_stmt_execute($stmtPriceProduct);
    $filaPrecio = mysqli_fetch_assoc(mysqli_stmt_get_result($stmtPriceProduct));
    $precioOld = $filaPrecio && isset($filaPrecio['price']) ? (float) $filaPrecio['price'] : 0;

    $actualizar = false;

    if ($idSpecificPrice <= 0) {
        if ($precioOld != $precio) {
            $actualizar = true;
        } else {
            $totalSinCambio++;
            continue;
        }
    } else {
        $actualizar = true;
        if ($precioOld != 0) {
            $resta = $precioOld - $precio;
            $porcentaje = ($resta / $precioOld * 100) / 100;

            mysqli_stmt_bind_param($stmtUpdateSpecificPrice, 'di', $porcentaje, $idSpecificPrice);
            mysqli_stmt_execute($stmtUpdateSpecificPrice);
        }
    }

    if (!$actualizar) {
        continue;
    }

    $descripcionCron = 'articulo SKU ' . $sku . ' cambia de precio en tienda y atualizado en al web id presatshop Nº ' . $idPrestashop;
    insert_global_cron($descripcionCron, $sucursalCron, 'Actualizaprecioweb', (string) $idUsuarioCron);

    mysqli_stmt_bind_param($stmtUpdateProductPrice, 'di', $precio, $idPrestashop);
    mysqli_stmt_execute($stmtUpdateProductPrice);

    mysqli_stmt_bind_param($stmtUpdateProductShopPrice, 'di', $precio, $idPrestashop);
    mysqli_stmt_execute($stmtUpdateProductShopPrice);

    $comentariosAccion = 'El articulo SKU ' . $sku . ' cambia de precio en tienda y atualizado en al web id presatshop Nº ' . $idPrestashop;
    trazabilidad_articulos_venta(
        0,
        (string) $idUsuarioCron,
        'actualiza_precio_web',
        $comentariosAccion,
        $sucursalCron,
        $sku,
        '0'
    );

    $totalActualizados++;
    cron_linea(
        '  - SKU ' . $sku . ' | id_prestashop ' . $idPrestashop
        . ' | precio ' . $precioOld . ' -> ' . $precio
        . ($idSpecificPrice > 0 ? ' | oferta' : '')
    );
}

mysqli_free_result($resultadoArticulos);
mysqli_stmt_close($stmtSpecificPrice);
mysqli_stmt_close($stmtPriceProduct);
mysqli_stmt_close($stmtUpdateProductPrice);
mysqli_stmt_close($stmtUpdateProductShopPrice);
mysqli_stmt_close($stmtUpdateSpecificPrice);

cron_linea('  - Precios actualizados: ' . $totalActualizados . ' | sin cambio: ' . $totalSinCambio);
