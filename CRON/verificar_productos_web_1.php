<?php

/**
 * Paso 1: verificar productos referenciados con el numero de referencia en la web.
 */

$conexion = cron_obtener_conexion();
$conexionWeb = cron_obtener_conexion_web();
if (!$conexion || !$conexionWeb) {
    cron_linea('ERROR verificar_productos_web_1: sin conexion a base de datos.');
    return;
}

$idUsuarioCron = 1;

cron_linea('>> Paso: verificar_productos_web_1');

$sqlProductos = "SELECT id_product, reference
                 FROM ps_product
                 WHERE reference > 0
                   AND DATE(date_upd) = CURDATE()";
$resultadoProductos = mysqli_query($conexionWeb, $sqlProductos);
if (!$resultadoProductos) {
    cron_linea('ERROR verificar_productos_web_1 consultando ps_product: ' . mysqli_error($conexionWeb));
    return;
}

$sqlArticulo = 'SELECT id_prestashop, id_sucursal_destino
                FROM articulos_venta
                WHERE id = ?
                LIMIT 1';
$stmtArticulo = mysqli_prepare($conexion, $sqlArticulo);

$sqlUpdateArticulo = "UPDATE articulos_venta
                      SET id_prestashop = ?, articulo_web = 'true'
                      WHERE id = ?
                        AND id_prestashop = '0'
                        AND articulo_web = 'false'
                      LIMIT 1";
$stmtUpdateArticulo = mysqli_prepare($conexion, $sqlUpdateArticulo);

$sqlUpdateRel = "UPDATE rel_articulos_estados
                 SET id_prestashop = ?, articulo_web = 'true'
                 WHERE rel_id_articulo_venta = ?";
$stmtUpdateRel = mysqli_prepare($conexion, $sqlUpdateRel);

if (!$stmtArticulo || !$stmtUpdateArticulo || !$stmtUpdateRel) {
    cron_linea('ERROR verificar_productos_web_1 preparando consultas TPV: ' . mysqli_error($conexion));
    mysqli_free_result($resultadoProductos);
    return;
}

$totalProcesados = 0;

while ($producto = mysqli_fetch_assoc($resultadoProductos)) {
    $idProduct = isset($producto['id_product']) ? (int) $producto['id_product'] : 0;
    $referenceSku = isset($producto['reference']) ? (string) $producto['reference'] : '';

    if ($idProduct <= 0 || $referenceSku === '' || $referenceSku === '0') {
        continue;
    }

    cron_linea('  - idProduct=' . $idProduct . ' | reference_sku=' . $referenceSku);

    mysqli_stmt_bind_param($stmtArticulo, 's', $referenceSku);
    if (!mysqli_stmt_execute($stmtArticulo)) {
        cron_linea('    ERROR consultando articulo SKU ' . $referenceSku);
        continue;
    }

    $filaArticulo = mysqli_fetch_assoc(mysqli_stmt_get_result($stmtArticulo));
    if (!$filaArticulo) {
        continue;
    }

    $idPrestashop = isset($filaArticulo['id_prestashop']) ? (string) $filaArticulo['id_prestashop'] : '';
    $idSucursalDestino = isset($filaArticulo['id_sucursal_destino']) ? (int) $filaArticulo['id_sucursal_destino'] : 0;

    if ($idPrestashop !== '' && $idPrestashop !== '0') {
        continue;
    }

    $descripcionCron = 'articulo publicado en la web SKU: ' . $referenceSku . ' id presatshop Nº ' . $idProduct;
    insert_global_cron($descripcionCron, $idSucursalDestino, 'Publicadoweb', (string) $idUsuarioCron);

    mysqli_stmt_bind_param($stmtUpdateArticulo, 'is', $idProduct, $referenceSku);
    mysqli_stmt_execute($stmtUpdateArticulo);

    mysqli_stmt_bind_param($stmtUpdateRel, 'is', $idProduct, $referenceSku);
    mysqli_stmt_execute($stmtUpdateRel);

    $comentariosAccion = 'Artículo publicado en la web SKU ' . $referenceSku . ' relacionado con el producto web Nº ' . $idProduct;
    trazabilidad_articulos_venta(
        0,
        (string) $idUsuarioCron,
        'publicadoweb',
        $comentariosAccion,
        $idSucursalDestino,
        $referenceSku,
        '0'
    );

    $totalProcesados++;
    cron_linea('    Publicado SKU ' . $referenceSku . ' -> id_prestashop ' . $idProduct);
}

mysqli_free_result($resultadoProductos);
mysqli_stmt_close($stmtArticulo);
mysqli_stmt_close($stmtUpdateArticulo);
mysqli_stmt_close($stmtUpdateRel);

cron_linea('  - Total publicados: ' . $totalProcesados);
