<?php

/**
 * Informe actual: ventas a plazos del dia.
 */

$ctx = cron_informe_actual_contexto('informes-actual-ventas-plazos');
if (!$ctx) {
    return;
}

$conexion = $ctx['conexion'];
$fechaInformeToday = $ctx['fecha'];
$ventaPlazos = 'si';

$stmtInformes = cron_informe_actual_stmt_abiertos($conexion, $fechaInformeToday);
if (!$stmtInformes) {
    cron_linea('ERROR informes-actual-ventas-plazos preparando consulta de informes.');
    return;
}

$resultadoInformes = mysqli_stmt_get_result($stmtInformes);

while ($informe = $resultadoInformes ? mysqli_fetch_assoc($resultadoInformes) : false) {
    $idInforme = (int) $informe['id_informe'];
    $sucursalInforme = (int) $informe['sucursal_informe'];
    $fechaInforme = (string) $informe['fecha_informe'];

    $sqlTotales = "SELECT SUM(precio) AS TOTALEUROSVENTAS, COUNT(id) AS TOTALVENTAS
                   FROM ventas
                   WHERE id_sucursal = ? AND venta_plazos = ? AND DATE(fecha) = ?";
    $stmtTotales = mysqli_prepare($conexion, $sqlTotales);
    if (!$stmtTotales) {
        continue;
    }

    mysqli_stmt_bind_param($stmtTotales, 'iss', $sucursalInforme, $ventaPlazos, $fechaInforme);
    mysqli_stmt_execute($stmtTotales);
    $totales = mysqli_fetch_assoc(mysqli_stmt_get_result($stmtTotales));
    mysqli_stmt_close($stmtTotales);

    $totalVentas = (int) (isset($totales['TOTALVENTAS']) ? $totales['TOTALVENTAS'] : 0);
    if ($totalVentas > 0) {
        $totalEuros = round((float) (isset($totales['TOTALEUROSVENTAS']) ? $totales['TOTALEUROSVENTAS'] : 0), 2);

        $sqlUpdate = "UPDATE informe_actual
                      SET total_ventas_plazo = ?,
                          total_ventas_plazo_euro = ?
                      WHERE id_informe = ?";
        $stmtUpdate = mysqli_prepare($conexion, $sqlUpdate);
        if ($stmtUpdate) {
            mysqli_stmt_bind_param($stmtUpdate, 'idi', $totalVentas, $totalEuros, $idInforme);
            mysqli_stmt_execute($stmtUpdate);
            mysqli_stmt_close($stmtUpdate);
        }

        cron_linea('  - Informe ' . $idInforme . ' | ventas plazo=' . $totalVentas);
    }

    cron_informe_actual_tarea_generado($idInforme, $sucursalInforme);
}

mysqli_stmt_close($stmtInformes);
