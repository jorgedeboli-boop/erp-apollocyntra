<?php

/**
 * Informe actual: renovaciones de empeños.
 * Lee historico_renovaciones_gobal (tabla global por sucursal_id).
 */

$ctx = cron_informe_actual_contexto('informes-actual-empenyos-renovaciones');
if (!$ctx) {
    return;
}

$conexion = $ctx['conexion'];
$fechaInformeToday = $ctx['fecha'];
$estadoHistorico = 'Renovado';

$stmtInformes = cron_informe_actual_stmt_abiertos($conexion, $fechaInformeToday);
if (!$stmtInformes) {
    cron_linea('ERROR informes-actual-empenyos-renovaciones preparando consulta de informes.');
    return;
}

$resultadoInformes = mysqli_stmt_get_result($stmtInformes);

while ($informe = $resultadoInformes ? mysqli_fetch_assoc($resultadoInformes) : false) {
    $idInforme = (int) $informe['id_informe'];
    $sucursalInforme = (int) $informe['sucursal_informe'];
    $fechaInforme = (string) $informe['fecha_informe'];

    $sqlTotales = "SELECT COUNT(id_renovaciones) AS TOTAL_RENOVACIONES,
                          SUM(importe_renovacion) AS TOTALRENOVACION
                   FROM historico_renovaciones_gobal
                   WHERE estado_historico = ?
                     AND fecha_renovacion = ?
                     AND sucursal_id = ?";
    $stmtTotales = mysqli_prepare($conexion, $sqlTotales);
    if (!$stmtTotales) {
        continue;
    }

    mysqli_stmt_bind_param($stmtTotales, 'ssi', $estadoHistorico, $fechaInforme, $sucursalInforme);
    mysqli_stmt_execute($stmtTotales);
    $totales = mysqli_fetch_assoc(mysqli_stmt_get_result($stmtTotales));
    mysqli_stmt_close($stmtTotales);

    $totalRenovaciones = isset($totales['TOTAL_RENOVACIONES']) ? (int) $totales['TOTAL_RENOVACIONES'] : 0;
    if ($totalRenovaciones > 0) {
        $totalEuros = round((float) $totales['TOTALRENOVACION'], 2);

        $sqlUpdate = "UPDATE informe_actual
                      SET total_renovaciones = ?,
                          total_euros_renovaciones = ?
                      WHERE id_informe = ?";
        $stmtUpdate = mysqli_prepare($conexion, $sqlUpdate);
        if ($stmtUpdate) {
            mysqli_stmt_bind_param($stmtUpdate, 'idi', $totalRenovaciones, $totalEuros, $idInforme);
            mysqli_stmt_execute($stmtUpdate);
            mysqli_stmt_close($stmtUpdate);
        }

        cron_linea('  - Informe ' . $idInforme . ' | renovaciones=' . $totalRenovaciones);
    }

    cron_informe_actual_tarea_generado($idInforme, $sucursalInforme);
}

mysqli_stmt_close($stmtInformes);
