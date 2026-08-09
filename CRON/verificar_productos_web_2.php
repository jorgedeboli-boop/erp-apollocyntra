<?php

/**
 * Paso 2: verificar pedidos de PrestaShop de hoy y generar venta en aplicacion con fecha de hoy.
 */

$conexion = cron_obtener_conexion();
$conexionWeb = cron_obtener_conexion_web();
if (!$conexion || !$conexionWeb) {
    cron_linea('ERROR verificar_productos_web_2: sin conexion a base de datos.');
    return;
}

cron_linea('>> Paso: verificar_productos_web_2');

$sqlSucursalWeb = "SELECT id_sucursal, empresa_id FROM sucursal WHERE sucursal_web = 'true' LIMIT 1";
$resultadoSucursal = mysqli_query($conexion, $sqlSucursalWeb);
if (!$resultadoSucursal) {
    cron_linea('ERROR verificar_productos_web_2 consultando sucursal web: ' . mysqli_error($conexion));
    return;
}

$sucursalWeb = mysqli_fetch_assoc($resultadoSucursal);
mysqli_free_result($resultadoSucursal);

if (!$sucursalWeb || empty($sucursalWeb['id_sucursal'])) {
    cron_linea('ERROR verificar_productos_web_2: no hay sucursal web configurada.');
    return;
}

$idSucursalWeb = (int) $sucursalWeb['id_sucursal'];

$sqlUsuarioWeb = 'SELECT id_usuario FROM usuarios WHERE sucursal_usuario = ? LIMIT 1';
$stmtUsuarioWeb = mysqli_prepare($conexion, $sqlUsuarioWeb);
if (!$stmtUsuarioWeb) {
    cron_linea('ERROR verificar_productos_web_2 preparando consulta usuario web.');
    return;
}

mysqli_stmt_bind_param($stmtUsuarioWeb, 'i', $idSucursalWeb);
mysqli_stmt_execute($stmtUsuarioWeb);
$filaUsuario = mysqli_fetch_assoc(mysqli_stmt_get_result($stmtUsuarioWeb));
mysqli_stmt_close($stmtUsuarioWeb);

$idUsuarioWeb = $filaUsuario && isset($filaUsuario['id_usuario']) ? (int) $filaUsuario['id_usuario'] : 0;
if ($idUsuarioWeb <= 0) {
    cron_linea('ERROR verificar_productos_web_2: no hay usuario para la sucursal web.');
    return;
}

$sqlPedidos = "SELECT id_order AS idOrder, total_paid AS total_paidOrder, date_add AS fechaOrder
               FROM ps_orders
               WHERE DATE(date_add) = CURDATE()
                 AND valid = 1";
$resultadoPedidos = mysqli_query($conexionWeb, $sqlPedidos);
if (!$resultadoPedidos) {
    cron_linea('ERROR verificar_productos_web_2 consultando ps_orders: ' . mysqli_error($conexionWeb));
    return;
}

$sqlExisteVenta = 'SELECT id FROM ventas WHERE id_order_web = ? LIMIT 1';
$stmtExisteVenta = mysqli_prepare($conexion, $sqlExisteVenta);

$sqlCountArticulos = 'SELECT COUNT(id_order_detail) AS total_articulos FROM ps_order_detail WHERE id_order = ?';
$stmtCountArticulos = mysqli_prepare($conexionWeb, $sqlCountArticulos);

$sqlMaxIdVenta = "SELECT MAX(id_venta_sucursal) AS id_venta_max
                  FROM ventas
                  WHERE id_sucursal = ?
                    AND YEAR(CURRENT_DATE) = YEAR(fecha)";
$stmtMaxIdVenta = mysqli_prepare($conexion, $sqlMaxIdVenta);

$sqlInsertVenta = "INSERT INTO ventas (
    id_sucursal,
    id_venta_sucursal,
    cliente,
    comprado_por,
    venta_plazos,
    porcentaje_plazos,
    numero_plazos,
    tipo_pago,
    precio,
    cantidad_contado,
    cantidad_tarjeta,
    fecha,
    cantidad_transferencia,
    estado,
    cantidad_bizum,
    venta_web,
    id_order_web,
    cantidad_articulos
) VALUES (?, ?, 0, ?, 'no', 0, 0, 'tarjeta', ?, 0, ?, ?, 0, 'vendido', 0, 'true', ?, ?)";
$stmtInsertVenta = mysqli_prepare($conexion, $sqlInsertVenta);

if (!$stmtExisteVenta || !$stmtCountArticulos || !$stmtMaxIdVenta || !$stmtInsertVenta) {
    cron_linea('ERROR verificar_productos_web_2 preparando consultas.');
    mysqli_free_result($resultadoPedidos);
    return;
}

$totalCreadas = 0;
$totalOmitidas = 0;

while ($pedido = mysqli_fetch_assoc($resultadoPedidos)) {
    $idOrder = isset($pedido['idOrder']) ? (int) $pedido['idOrder'] : 0;
    $totalPaidOrder = isset($pedido['total_paidOrder']) ? (float) $pedido['total_paidOrder'] : 0;
    $fechaOrder = isset($pedido['fechaOrder']) ? (string) $pedido['fechaOrder'] : '';

    if ($idOrder <= 0) {
        continue;
    }

    mysqli_stmt_bind_param($stmtExisteVenta, 'i', $idOrder);
    mysqli_stmt_execute($stmtExisteVenta);
    $filaVenta = mysqli_fetch_assoc(mysqli_stmt_get_result($stmtExisteVenta));

    if ($filaVenta && !empty($filaVenta['id'])) {
        $totalOmitidas++;
        continue;
    }

    mysqli_stmt_bind_param($stmtCountArticulos, 'i', $idOrder);
    mysqli_stmt_execute($stmtCountArticulos);
    $filaArticulos = mysqli_fetch_assoc(mysqli_stmt_get_result($stmtCountArticulos));
    $totalArticulos = (int) (isset($filaArticulos['total_articulos']) ? $filaArticulos['total_articulos'] : 0);

    mysqli_stmt_bind_param($stmtMaxIdVenta, 'i', $idSucursalWeb);
    mysqli_stmt_execute($stmtMaxIdVenta);
    $filaMax = mysqli_fetch_assoc(mysqli_stmt_get_result($stmtMaxIdVenta));
    $idVentaSucursal = (int) (isset($filaMax['id_venta_max']) ? $filaMax['id_venta_max'] : 0) + 1;

    mysqli_stmt_bind_param(
        $stmtInsertVenta,
        'iiiddsii',
        $idSucursalWeb,
        $idVentaSucursal,
        $idUsuarioWeb,
        $totalPaidOrder,
        $totalPaidOrder,
        $fechaOrder,
        $idOrder,
        $totalArticulos
    );

    if (!mysqli_stmt_execute($stmtInsertVenta)) {
        cron_linea('    ERROR insertando venta pedido web ' . $idOrder . ': ' . mysqli_stmt_error($stmtInsertVenta));
        continue;
    }

    $totalCreadas++;
    cron_linea(
        '  - Venta creada id_venta_sucursal=' . $idVentaSucursal
        . ' | id_order_web=' . $idOrder
        . ' | articulos=' . $totalArticulos
        . ' | total=' . $totalPaidOrder
    );
}

mysqli_free_result($resultadoPedidos);
mysqli_stmt_close($stmtExisteVenta);
mysqli_stmt_close($stmtCountArticulos);
mysqli_stmt_close($stmtMaxIdVenta);
mysqli_stmt_close($stmtInsertVenta);

cron_linea('  - Ventas creadas: ' . $totalCreadas . ' | omitidas (ya existian): ' . $totalOmitidas);
