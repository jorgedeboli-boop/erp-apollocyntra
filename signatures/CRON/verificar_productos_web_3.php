<?php

/**
 * Paso 3: asociar articulos vendidos en la web a la venta generada en la aplicacion.
 */

$conexion = cron_obtener_conexion();
$conexionWeb = cron_obtener_conexion_web();
if (!$conexion || !$conexionWeb) {
    cron_linea('ERROR verificar_productos_web_3: sin conexion a base de datos.');
    return;
}

cron_linea('>> Paso: verificar_productos_web_3');

$sqlSucursalWeb = "SELECT id_sucursal, nombre_sucursal FROM sucursal WHERE sucursal_web = 'true' LIMIT 1";
$resultadoSucursal = mysqli_query($conexion, $sqlSucursalWeb);
$sucursalWeb = $resultadoSucursal ? mysqli_fetch_assoc($resultadoSucursal) : null;
if ($resultadoSucursal) {
    mysqli_free_result($resultadoSucursal);
}

if (!$sucursalWeb || empty($sucursalWeb['id_sucursal'])) {
    cron_linea('ERROR verificar_productos_web_3: no hay sucursal web configurada.');
    return;
}

$idSucursalWeb = (int) $sucursalWeb['id_sucursal'];
$nombreSucursalWeb = isset($sucursalWeb['nombre_sucursal']) ? (string) $sucursalWeb['nombre_sucursal'] : '';

$sqlUsuarioWeb = 'SELECT id_usuario FROM usuarios WHERE sucursal_usuario = ? LIMIT 1';
$stmtUsuarioWeb = mysqli_prepare($conexion, $sqlUsuarioWeb);
if (!$stmtUsuarioWeb) {
    cron_linea('ERROR verificar_productos_web_3 preparando consulta usuario web.');
    return;
}

mysqli_stmt_bind_param($stmtUsuarioWeb, 'i', $idSucursalWeb);
mysqli_stmt_execute($stmtUsuarioWeb);
$filaUsuario = mysqli_fetch_assoc(mysqli_stmt_get_result($stmtUsuarioWeb));
mysqli_stmt_close($stmtUsuarioWeb);

$idUsuarioWeb = $filaUsuario && isset($filaUsuario['id_usuario']) ? (int) $filaUsuario['id_usuario'] : 0;
if ($idUsuarioWeb <= 0) {
    cron_linea('ERROR verificar_productos_web_3: no hay usuario para la sucursal web.');
    return;
}

$numeroSemana = function_exists('numeroSemanaActual') ? numeroSemanaActual() : 0;
if ($numeroSemana === null) {
    $numeroSemana = 0;
}

$sqlProductosWeb = "
SELECT
    PSP.id_product AS idProduct,
    PSP.reference,
    PSOD.id_order,
    PSPS.date_upd AS fecha_venta
FROM ps_product AS PSP
LEFT JOIN ps_product_sale AS PSPS ON PSPS.id_product = PSP.id_product
LEFT JOIN ps_order_detail AS PSOD ON PSOD.product_id = PSP.id_product
LEFT JOIN ps_orders AS ORDR ON ORDR.id_order = PSOD.id_order
WHERE PSP.reference > 0
  AND PSPS.quantity > 0
  AND DATE(PSPS.date_upd) = CURDATE()
  AND ORDR.valid = 1";
$resultadoWeb = mysqli_query($conexionWeb, $sqlProductosWeb);
if (!$resultadoWeb) {
    cron_linea('ERROR verificar_productos_web_3 consultando productos web: ' . mysqli_error($conexionWeb));
    return;
}

$sqlEstado = "SELECT estado FROM articulos_venta WHERE id = ? AND id_prestashop = ? AND articulo_web = 'true' LIMIT 1";
$stmtEstado = mysqli_prepare($conexion, $sqlEstado);

$sqlArticulo = "SELECT descripcion, precio, id_sucursal_destino, peso, articulo_web, tipo, precio_coste
                FROM articulos_venta WHERE id = ? AND id_prestashop = ? LIMIT 1";
$stmtArticulo = mysqli_prepare($conexion, $sqlArticulo);

$sqlVenta = 'SELECT id, id_venta_sucursal FROM ventas WHERE id_order_web = ? LIMIT 1';
$stmtVenta = mysqli_prepare($conexion, $sqlVenta);

$sqlUpdateArticulo = "UPDATE articulos_venta SET
    id_sucursal_origen = ?,
    id_sucursal_destino = ?,
    estado = 'vendido_web',
    id_order_web = ?,
    fecha_vendido = ?,
    nombre_sucursal_venta = ?,
    last_id_venta = ?,
    id_venta_sucursal = ?
    WHERE id = ?";
$stmtUpdateArticulo = mysqli_prepare($conexion, $sqlUpdateArticulo);

$sqlUpdateRel = "UPDATE rel_articulos_estados SET
    estado_articulo = 'vendido_web',
    fecha_venta = ?,
    rel_id_sucursal_venta = ?,
    id_order_web = ?,
    rel_id_venta = ?,
    rel_numero_semana = ?
    WHERE rel_id_articulo_venta = ?";
$stmtUpdateRel = mysqli_prepare($conexion, $sqlUpdateRel);

$sqlInsertRelVenta = "INSERT INTO rel_articulos_venta (
    sku_articulo,
    sucursal_venta,
    descripcion_articulo_rel,
    id_venta_rel,
    rel_id_venta,
    precio_venta,
    fecha_venta,
    vendido_por,
    venta_web,
    coste_articulo_venta
) VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'true', ?)";
$stmtInsertRelVenta = mysqli_prepare($conexion, $sqlInsertRelVenta);

$sqlInsertReporte = "INSERT INTO reporte_ventas (
    id_articulo,
    id_sucursal_venta,
    nombre_sucursal_venta,
    descripcion_articulo,
    id_venta_rel,
    identificador_venta,
    precio_articulo,
    peso_articulo,
    articulo_web,
    tipo_metal_articulo,
    venta_plazos,
    numero_plazos,
    tipo_pago,
    cantidad_contado,
    cantidad_tarjeta,
    cantidad_transferencia,
    cantidad_bizum,
    fecha_venta,
    usuario_venta,
    coste_articulo_venta
) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'no', '0', 'tarjeta', 0, ?, 0, 0, ?, ?, ?)";
$stmtInsertReporte = mysqli_prepare($conexion, $sqlInsertReporte);

$sqlInsertTraspaso = "INSERT INTO traspasos (
    sucursal_traspaso,
    sucursal_destino,
    estado_traspaso,
    fecha_traspaso,
    creado_por,
    total_articulos_traspaso,
    skus_traspaso,
    hora_creado,
    codigo_cron_random,
    observaciones_traspaso,
    traspaso_web,
    id_order_prestashop,
    enviado_por
) VALUES (?, ?, 'PENDIENTEENVIO', NOW(), '1', '1', ?, NOW(), ?, ?, 'true', ?, '1')";
$stmtInsertTraspaso = mysqli_prepare($conexion, $sqlInsertTraspaso);

$sqlInsertRelTraspaso = "INSERT INTO rel_articulos_traspaso (
    id_articulo_rel,
    id_traspaso_rel,
    sucursal_origen_rel,
    sucursal_destino_rel,
    fecha_creacion_rel,
    codigo_cron_random
) VALUES (?, ?, ?, ?, NOW(), ?)";
$stmtInsertRelTraspaso = mysqli_prepare($conexion, $sqlInsertRelTraspaso);

if (
    !$stmtEstado || !$stmtArticulo || !$stmtVenta || !$stmtUpdateArticulo
    || !$stmtUpdateRel || !$stmtInsertRelVenta || !$stmtInsertReporte
    || !$stmtInsertTraspaso || !$stmtInsertRelTraspaso
) {
    cron_linea('ERROR verificar_productos_web_3 preparando consultas TPV.');
    mysqli_free_result($resultadoWeb);
    return;
}

$totalProcesados = 0;

while ($item = mysqli_fetch_assoc($resultadoWeb)) {
    $idProduct = isset($item['idProduct']) ? (int) $item['idProduct'] : 0;
    $idOrderWeb = isset($item['id_order']) ? (int) $item['id_order'] : 0;
    $referenceSku = isset($item['reference']) ? (string) $item['reference'] : '';
    $fechaVenta = isset($item['fecha_venta']) ? (string) $item['fecha_venta'] : '';

    if ($idProduct <= 0 || $idOrderWeb <= 0 || $referenceSku === '') {
        continue;
    }

    mysqli_stmt_bind_param($stmtEstado, 'si', $referenceSku, $idProduct);
    mysqli_stmt_execute($stmtEstado);
    $filaEstado = mysqli_fetch_assoc(mysqli_stmt_get_result($stmtEstado));
    $estadoArticulo = $filaEstado && isset($filaEstado['estado']) ? (string) $filaEstado['estado'] : '';

    if ($estadoArticulo !== 'enventa') {
        continue;
    }

    mysqli_stmt_bind_param($stmtArticulo, 'si', $referenceSku, $idProduct);
    mysqli_stmt_execute($stmtArticulo);
    $articulo = mysqli_fetch_assoc(mysqli_stmt_get_result($stmtArticulo));
    if (!$articulo) {
        continue;
    }

    $idSucursalDestino = (int) $articulo['id_sucursal_destino'];
    $precioVenta = (float) $articulo['precio'];
    $descripcionArticulo = (string) $articulo['descripcion'];
    $pesoArticulo = isset($articulo['peso']) ? (string) $articulo['peso'] : '';
    $articuloWeb = (string) $articulo['articulo_web'];
    $tipoMetal = (string) $articulo['tipo'];
    $costeArticulo = (float) $articulo['precio_coste'];

    mysqli_stmt_bind_param($stmtVenta, 'i', $idOrderWeb);
    mysqli_stmt_execute($stmtVenta);
    $venta = mysqli_fetch_assoc(mysqli_stmt_get_result($stmtVenta));
    if (!$venta || empty($venta['id'])) {
        continue;
    }

    $idVentaPk = (int) $venta['id'];
    $idVentaSucursal = (int) $venta['id_venta_sucursal'];

    mysqli_stmt_bind_param(
        $stmtUpdateArticulo,
        'iiissiis',
        $idSucursalDestino,
        $idSucursalWeb,
        $idOrderWeb,
        $fechaVenta,
        $nombreSucursalWeb,
        $idVentaPk,
        $idVentaSucursal,
        $referenceSku
    );
    mysqli_stmt_execute($stmtUpdateArticulo);

    mysqli_stmt_bind_param(
        $stmtUpdateRel,
        'siiisi',
        $fechaVenta,
        $idSucursalWeb,
        $idOrderWeb,
        $idVentaPk,
        $numeroSemana,
        $referenceSku
    );
    mysqli_stmt_execute($stmtUpdateRel);

    mysqli_stmt_bind_param(
        $stmtInsertRelVenta,
        'iisidsid',
        $referenceSku,
        $idSucursalWeb,
        $descripcionArticulo,
        $idVentaSucursal,
        $idVentaPk,
        $precioVenta,
        $fechaVenta,
        $idUsuarioWeb,
        $costeArticulo
    );
    mysqli_stmt_execute($stmtInsertRelVenta);

    $comentariosAccion = 'Artículo vendido web SKU ' . $referenceSku . ' en la venta Nº ' . $idVentaSucursal . ' del pedido web Nº ' . $idOrderWeb;
    trazabilidad_articulos_venta(
        $idVentaSucursal,
        (string) $idUsuarioWeb,
        'vendidoweb',
        $comentariosAccion,
        $idSucursalWeb,
        $referenceSku,
        (string) $idVentaPk
    );

    mysqli_stmt_bind_param(
        $stmtInsertReporte,
        'iissiidsssdsid',
        $referenceSku,
        $idSucursalWeb,
        $nombreSucursalWeb,
        $descripcionArticulo,
        $idVentaSucursal,
        $idVentaPk,
        $precioVenta,
        $pesoArticulo,
        $articuloWeb,
        $tipoMetal,
        $precioVenta,
        $fechaVenta,
        $idUsuarioWeb,
        $costeArticulo
    );
    mysqli_stmt_execute($stmtInsertReporte);

    insert_global_cron(
        'articulo vendido web SKU: ' . $referenceSku . ' de la venta Nº ' . $idVentaSucursal,
        $idSucursalWeb,
        'Ventaweb',
        (string) $idUsuarioWeb
    );

    $codigoCronRandom = (string) mt_rand(0, 999999999);
    $observacionesTraspaso = 'TRASPASO de articulo vendido web SKU: ' . $referenceSku . ' de la venta Nº ' . $idVentaSucursal;
    $skusTraspaso = ',' . $referenceSku;

    mysqli_stmt_bind_param(
        $stmtInsertTraspaso,
        'iisssi',
        $idSucursalDestino,
        $idSucursalWeb,
        $skusTraspaso,
        $codigoCronRandom,
        $observacionesTraspaso,
        $idOrderWeb
    );
    mysqli_stmt_execute($stmtInsertTraspaso);
    $idTraspaso = (int) mysqli_insert_id($conexion);

    mysqli_stmt_bind_param(
        $stmtInsertRelTraspaso,
        'siiis',
        $referenceSku,
        $idTraspaso,
        $idSucursalDestino,
        $idSucursalWeb,
        $codigoCronRandom
    );
    mysqli_stmt_execute($stmtInsertRelTraspaso);

    insert_global_cron(
        'TRASPASO PENDIENTE Nº ' . $idTraspaso . ' articulo vendido web SKU: ' . $referenceSku . ' de la venta Nº ' . $idVentaSucursal,
        $idSucursalWeb,
        'Traspasoventaweb',
        (string) $idUsuarioWeb
    );

    $totalProcesados++;
    cron_linea(
        '  - SKU ' . $referenceSku . ' vendido_web | pedido ' . $idOrderWeb
        . ' | venta ' . $idVentaSucursal . ' | traspaso ' . $idTraspaso
    );
}

mysqli_free_result($resultadoWeb);
mysqli_stmt_close($stmtEstado);
mysqli_stmt_close($stmtArticulo);
mysqli_stmt_close($stmtVenta);
mysqli_stmt_close($stmtUpdateArticulo);
mysqli_stmt_close($stmtUpdateRel);
mysqli_stmt_close($stmtInsertRelVenta);
mysqli_stmt_close($stmtInsertReporte);
mysqli_stmt_close($stmtInsertTraspaso);
mysqli_stmt_close($stmtInsertRelTraspaso);

cron_linea('  - Total articulos procesados: ' . $totalProcesados);
