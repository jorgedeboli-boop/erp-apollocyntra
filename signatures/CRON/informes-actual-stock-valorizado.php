<?php

/**
 * Informe actual: stock valorizado en venta por sucursal.
 */

$ctx = cron_informe_actual_contexto('informes-actual-stock-valorizado');
if (!$ctx) {
    return;
}

$conexion = $ctx['conexion'];
$fechaInformeToday = $ctx['fecha'];
$estadoArticulo = 'enventa';

$stmtInformes = cron_informe_actual_stmt_abiertos($conexion, $fechaInformeToday);
if (!$stmtInformes) {
    cron_linea('ERROR informes-actual-stock-valorizado preparando consulta de informes.');
    return;
}

$resultadoInformes = mysqli_stmt_get_result($stmtInformes);

while ($informe = $resultadoInformes ? mysqli_fetch_assoc($resultadoInformes) : false) {
    $idInforme = (int) $informe['id_informe'];
    $sucursalInforme = (int) $informe['sucursal_informe'];

    $sqlTotales = "SELECT SUM(precio) AS TOTALSTOCKVALORIZADO,
                          SUM(precio_coste) AS TOTALSTOCKCOSTE,
                          COUNT(id) AS TOTALSTOCKUNIDADES
                   FROM articulos_venta
                   WHERE id_sucursal_destino = ? AND estado = ?";
    $stmtTotales = mysqli_prepare($conexion, $sqlTotales);
    if (!$stmtTotales) {
        continue;
    }

    mysqli_stmt_bind_param($stmtTotales, 'is', $sucursalInforme, $estadoArticulo);
    mysqli_stmt_execute($stmtTotales);
    $totales = mysqli_fetch_assoc(mysqli_stmt_get_result($stmtTotales));
    mysqli_stmt_close($stmtTotales);

    $totalValorizado = round((float) (isset($totales['TOTALSTOCKVALORIZADO']) ? $totales['TOTALSTOCKVALORIZADO'] : 0), 2);

    if ($totalValorizado != 0) {
        $totalCoste = round((float) (isset($totales['TOTALSTOCKCOSTE']) ? $totales['TOTALSTOCKCOSTE'] : 0), 2);
        $totalUnidades = (int) (isset($totales['TOTALSTOCKUNIDADES']) ? $totales['TOTALSTOCKUNIDADES'] : 0);

        $sqlUpdate = "UPDATE informe_actual
                      SET coste_stock_valorizado = ?,
                          stock_articulos = ?,
                          stock_valorizado_eruo = ?
                      WHERE id_informe = ?";
        $stmtUpdate = mysqli_prepare($conexion, $sqlUpdate);
        if ($stmtUpdate) {
            mysqli_stmt_bind_param($stmtUpdate, 'didi', $totalCoste, $totalUnidades, $totalValorizado, $idInforme);
            mysqli_stmt_execute($stmtUpdate);
            mysqli_stmt_close($stmtUpdate);
        }

        cron_linea('  - Informe ' . $idInforme . ' | stock valorizado=' . $totalValorizado . ' | unidades=' . $totalUnidades);
    }

    cron_informe_actual_tarea_generado($idInforme, $sucursalInforme);
}

mysqli_stmt_close($stmtInformes);
