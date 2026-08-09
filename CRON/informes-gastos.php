<?php

/**
 * Informe diario: gastos del día por sucursal.
 * Suma total_gasto de gastos (fecha_gasto = hoy, sucursal) → informe_diario.total_gastos.
 */

$ctx = cron_informe_contexto('informes-gastos');
if (!$ctx) {
    return;
}

$conexion = $ctx['conexion'];
$fechaInformeToday = $ctx['fecha'];

$stmtInformes = cron_informe_stmt_abiertos($conexion, $fechaInformeToday);
if (!$stmtInformes) {
    cron_linea('ERROR informes-gastos preparando consulta de informes.');
    return;
}

$resultadoInformes = mysqli_stmt_get_result($stmtInformes);

while ($informe = $resultadoInformes ? mysqli_fetch_assoc($resultadoInformes) : false) {
    $idInforme = (int) $informe['id_informe'];
    $sucursalInforme = (int) $informe['sucursal_informe'];
    $fechaInforme = (string) $informe['fecha_informe'];

    $sqlTotales = 'SELECT COALESCE(SUM(total_gasto), 0) AS TOTAL_GASTOS
                   FROM gastos
                   WHERE sucursal_gasto = ?
                     AND fecha_gasto = ?';
    $stmtTotales = mysqli_prepare($conexion, $sqlTotales);
    if (!$stmtTotales) {
        continue;
    }

    mysqli_stmt_bind_param($stmtTotales, 'is', $sucursalInforme, $fechaInforme);
    mysqli_stmt_execute($stmtTotales);
    $totales = mysqli_fetch_assoc(mysqli_stmt_get_result($stmtTotales));
    mysqli_stmt_close($stmtTotales);

    $totalGastos = round((float) (isset($totales['TOTAL_GASTOS']) ? $totales['TOTAL_GASTOS'] : 0), 2);

    $sqlUpdate = 'UPDATE informe_diario
                  SET total_gastos = ?
                  WHERE id_informe = ?';
    $stmtUpdate = mysqli_prepare($conexion, $sqlUpdate);
    if ($stmtUpdate) {
        mysqli_stmt_bind_param($stmtUpdate, 'di', $totalGastos, $idInforme);
        mysqli_stmt_execute($stmtUpdate);
        mysqli_stmt_close($stmtUpdate);
    }

    cron_linea('  - Informe ' . $idInforme . ' | sucursal ' . $sucursalInforme . ' | total_gastos=' . $totalGastos);
    cron_informe_tarea_generado($idInforme, $sucursalInforme);
}

mysqli_stmt_close($stmtInformes);
