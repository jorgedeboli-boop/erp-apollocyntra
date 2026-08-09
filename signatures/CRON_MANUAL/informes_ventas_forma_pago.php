<?php

/**
 * Informe diario: ventas por forma de pago (contado, tarjeta, transferencia, bizum).
 * Incluye importes de ventas combinadas distribuidos en cada método.
 */

$ctx = cron_informe_contexto('informes_ventas_forma_pago');
if (!$ctx) {
    return;
}

$conexion = $ctx['conexion'];
$fechaInformeToday = $ctx['fecha'];
$estadoVenta = 'vendido';

$stmtInformes = cron_informe_stmt_abiertos($conexion, $fechaInformeToday);
if (!$stmtInformes) {
    cron_linea('ERROR informes_ventas_forma_pago preparando consulta de informes.');
    return;
}

$resultadoInformes = mysqli_stmt_get_result($stmtInformes);

while ($informe = $resultadoInformes ? mysqli_fetch_assoc($resultadoInformes) : false) {
    $idInforme = (int) $informe['id_informe'];
    $sucursalInforme = (int) $informe['sucursal_informe'];
    $fechaInforme = (string) $informe['fecha_informe'];

    $sqlVentas = "SELECT
                    SUM(CASE
                          WHEN tipo_pago = 'contado'
                            OR (tipo_pago = 'combinado' AND cantidad_contado > 0)
                          THEN 1 ELSE 0
                        END) AS ventas_contado,
                    SUM(COALESCE(cantidad_contado, 0)) AS ventas_contado_euros,
                    SUM(CASE
                          WHEN tipo_pago = 'tarjeta'
                            OR (tipo_pago = 'combinado' AND cantidad_tarjeta > 0)
                          THEN 1 ELSE 0
                        END) AS ventas_tarjeta,
                    SUM(COALESCE(cantidad_tarjeta, 0)) AS ventas_tarjeta_euros,
                    SUM(CASE
                          WHEN tipo_pago = 'transferencia'
                            OR (tipo_pago = 'combinado' AND cantidad_transferencia > 0)
                          THEN 1 ELSE 0
                        END) AS ventas_transferencia,
                    SUM(COALESCE(cantidad_transferencia, 0)) AS ventas_transferencia_euros,
                    SUM(CASE
                          WHEN tipo_pago = 'bizum'
                            OR (tipo_pago = 'combinado' AND cantidad_bizum > 0)
                          THEN 1 ELSE 0
                        END) AS ventas_bizum,
                    SUM(COALESCE(cantidad_bizum, 0)) AS ventas_bizum_euros
                  FROM ventas
                  WHERE id_sucursal = ?
                    AND estado = ?
                    AND DATE(fecha) = ?";
    $stmtVentas = mysqli_prepare($conexion, $sqlVentas);
    if (!$stmtVentas) {
        continue;
    }

    mysqli_stmt_bind_param($stmtVentas, 'iss', $sucursalInforme, $estadoVenta, $fechaInforme);
    mysqli_stmt_execute($stmtVentas);
    $ventas = mysqli_fetch_assoc(mysqli_stmt_get_result($stmtVentas));
    mysqli_stmt_close($stmtVentas);

    $ventasContado = (int) (isset($ventas['ventas_contado']) ? $ventas['ventas_contado'] : 0);
    $ventasContadoEuros = round((float) (isset($ventas['ventas_contado_euros']) ? $ventas['ventas_contado_euros'] : 0), 2);
    $ventasTarjeta = (int) (isset($ventas['ventas_tarjeta']) ? $ventas['ventas_tarjeta'] : 0);
    $ventasTarjetaEuros = round((float) (isset($ventas['ventas_tarjeta_euros']) ? $ventas['ventas_tarjeta_euros'] : 0), 2);
    $ventasTransferencia = (int) (isset($ventas['ventas_transferencia']) ? $ventas['ventas_transferencia'] : 0);
    $ventasTransferenciaEuros = round((float) (isset($ventas['ventas_transferencia_euros']) ? $ventas['ventas_transferencia_euros'] : 0), 2);
    $ventasBizum = (int) (isset($ventas['ventas_bizum']) ? $ventas['ventas_bizum'] : 0);
    $ventasBizumEuros = round((float) (isset($ventas['ventas_bizum_euros']) ? $ventas['ventas_bizum_euros'] : 0), 2);

    $sqlUpdate = "UPDATE informe_diario
                  SET ventas_contado = ?,
                      ventas_contado_euros = ?,
                      ventas_tarjeta = ?,
                      ventas_tarjeta_euros = ?,
                      ventas_transferencia = ?,
                      ventas_transferencia_euros = ?,
                      ventas_bizum = ?,
                      ventas_bizum_euros = ?
                  WHERE id_informe = ?";
    $stmtUpdate = mysqli_prepare($conexion, $sqlUpdate);
    if ($stmtUpdate) {
        mysqli_stmt_bind_param(
            $stmtUpdate,
            'ididididi',
            $ventasContado,
            $ventasContadoEuros,
            $ventasTarjeta,
            $ventasTarjetaEuros,
            $ventasTransferencia,
            $ventasTransferenciaEuros,
            $ventasBizum,
            $ventasBizumEuros,
            $idInforme
        );
        mysqli_stmt_execute($stmtUpdate);
        mysqli_stmt_close($stmtUpdate);
    }

    cron_linea(
        '  - Informe ' . $idInforme
        . ' | contado=' . $ventasContado . '/' . $ventasContadoEuros
        . ' | tarjeta=' . $ventasTarjeta . '/' . $ventasTarjetaEuros
        . ' | transferencia=' . $ventasTransferencia . '/' . $ventasTransferenciaEuros
        . ' | bizum=' . $ventasBizum . '/' . $ventasBizumEuros
    );
    cron_informe_tarea_generado($idInforme, $sucursalInforme);
}

mysqli_stmt_close($stmtInformes);
