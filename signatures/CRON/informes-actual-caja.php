<?php

/**
 * Informe actual: entradas y salidas de caja del dia.
 */

$ctx = cron_informe_actual_contexto('informes-actual-caja');
if (!$ctx) {
    return;
}

$conexion = $ctx['conexion'];
$fechaInformeToday = $ctx['fecha'];

$stmtInformes = cron_informe_actual_stmt_abiertos($conexion, $fechaInformeToday);
if (!$stmtInformes) {
    cron_linea('ERROR informes-actual-caja preparando consulta de informes.');
    return;
}

$resultadoInformes = mysqli_stmt_get_result($stmtInformes);

while ($informe = $resultadoInformes ? mysqli_fetch_assoc($resultadoInformes) : false) {
    $idInforme = (int) $informe['id_informe'];
    $sucursalInforme = (int) $informe['sucursal_informe'];
    $fechaInforme = (string) $informe['fecha_informe'];

    $tablaCaja = cron_tabla_movimientos_caja_sucursal($sucursalInforme);
    if ($tablaCaja === false) {
        continue;
    }

    $sqlTotales = "SELECT SUM(salida) AS TOTALCAJASALIDAS, SUM(entrada) AS TOTALCAJAENTRADAS
                   FROM {$tablaCaja}
                   WHERE fecha_apunte = ?
                     AND cierre_caja = 'false'
                     AND grupos != 'CAJA INICIO'";
    $stmtTotales = mysqli_prepare($conexion, $sqlTotales);
    if (!$stmtTotales) {
        continue;
    }

    mysqli_stmt_bind_param($stmtTotales, 's', $fechaInforme);
    mysqli_stmt_execute($stmtTotales);
    $totales = mysqli_fetch_assoc(mysqli_stmt_get_result($stmtTotales));
    mysqli_stmt_close($stmtTotales);

    $totalSalidas = round((float) (isset($totales['TOTALCAJASALIDAS']) ? $totales['TOTALCAJASALIDAS'] : 0), 2);
    $totalEntradas = round((float) (isset($totales['TOTALCAJAENTRADAS']) ? $totales['TOTALCAJAENTRADAS'] : 0), 2);

    $sqlUpdate = "UPDATE informe_actual
                  SET total_caja_entradas = ?,
                      total_caja_salidas = ?
                  WHERE id_informe = ?";
    $stmtUpdate = mysqli_prepare($conexion, $sqlUpdate);
    if ($stmtUpdate) {
        mysqli_stmt_bind_param($stmtUpdate, 'ddi', $totalEntradas, $totalSalidas, $idInforme);
        mysqli_stmt_execute($stmtUpdate);
        mysqli_stmt_close($stmtUpdate);
    }

    cron_linea('  - Informe ' . $idInforme . ' | caja | entradas=' . $totalEntradas . ' | salidas=' . $totalSalidas);
    cron_informe_actual_tarea_generado($idInforme, $sucursalInforme);
}

mysqli_stmt_close($stmtInformes);
