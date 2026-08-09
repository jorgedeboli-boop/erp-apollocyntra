<?php

/**
 * Informe actual: ventas web del dia.
 */

$ctx = cron_informe_actual_contexto('informes-actual-ventas-web');
if (!$ctx) {
    return;
}

$conexion = $ctx['conexion'];
$fechaInformeToday = $ctx['fecha'];
$estadoVenta = 'vendido';
$ventaWeb = 'true';

$stmtInformes = cron_informe_actual_stmt_abiertos($conexion, $fechaInformeToday);
if (!$stmtInformes) {
    cron_linea('ERROR informes-actual-ventas-web preparando consulta de informes.');
    return;
}

$resultadoInformes = mysqli_stmt_get_result($stmtInformes);

while ($informe = $resultadoInformes ? mysqli_fetch_assoc($resultadoInformes) : false) {
    $idInforme = (int) $informe['id_informe'];
    $sucursalInforme = (int) $informe['sucursal_informe'];
    $fechaInforme = (string) $informe['fecha_informe'];

    $sqlVentas = "SELECT SUM(precio) AS TOTALEUROSVENTAS, COUNT(id) AS TOTALVENTAS
                  FROM ventas
                  WHERE estado = ? AND venta_web = ? AND DATE(fecha) = ?";
    $stmtVentas = mysqli_prepare($conexion, $sqlVentas);
    if (!$stmtVentas) {
        continue;
    }

    mysqli_stmt_bind_param($stmtVentas, 'sss', $estadoVenta, $ventaWeb, $fechaInforme);
    mysqli_stmt_execute($stmtVentas);
    $ventas = mysqli_fetch_assoc(mysqli_stmt_get_result($stmtVentas));
    mysqli_stmt_close($stmtVentas);

    $totalVentasWeb = (int) (isset($ventas['TOTALVENTAS']) ? $ventas['TOTALVENTAS'] : 0);
    if ($totalVentasWeb > 0) {
        $totalEuros = (float) (isset($ventas['TOTALEUROSVENTAS']) ? $ventas['TOTALEUROSVENTAS'] : 0);
        $mediaVenta = round($totalEuros / $totalVentasWeb, 2);
        $totalEuros = round($totalEuros, 2);

        $sqlUpdate = "UPDATE informe_actual
                      SET ventas_web = ?,
                          total_euros_ventas_web = ?,
                          total_media_ventas_web = ?
                      WHERE id_informe = ?";
        $stmtUpdate = mysqli_prepare($conexion, $sqlUpdate);
        if ($stmtUpdate) {
            mysqli_stmt_bind_param($stmtUpdate, 'iddi', $totalVentasWeb, $totalEuros, $mediaVenta, $idInforme);
            mysqli_stmt_execute($stmtUpdate);
            mysqli_stmt_close($stmtUpdate);
        }

        cron_linea('  - Informe ' . $idInforme . ' | ventas web=' . $totalVentasWeb);
    }

    cron_informe_actual_tarea_generado($idInforme, $sucursalInforme);
}

mysqli_stmt_close($stmtInformes);
