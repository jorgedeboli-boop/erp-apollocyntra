<?php

/**
 * Informe diario: devoluciones del dia.
 */

$ctx = cron_informe_contexto('informes-devoluciones');
if (!$ctx) {
    return;
}

$conexion = $ctx['conexion'];
$fechaInformeToday = $ctx['fecha'];
$estadoDevolucion = 'hecha';

$stmtInformes = cron_informe_stmt_abiertos($conexion, $fechaInformeToday);
if (!$stmtInformes) {
    cron_linea('ERROR informes-devoluciones preparando consulta de informes.');
    return;
}

$resultadoInformes = mysqli_stmt_get_result($stmtInformes);

while ($informe = $resultadoInformes ? mysqli_fetch_assoc($resultadoInformes) : false) {
    $idInforme = (int) $informe['id_informe'];
    $sucursalInforme = (int) $informe['sucursal_informe'];
    $fechaInforme = (string) $informe['fecha_informe'];

    $sqlTotales = "SELECT COUNT(id) AS TOTALDEVOLUCIONES,
                          SUM(importe_devolucion) AS TOTALEUROSDEVOLUCIONES
                   FROM devoluciones
                   WHERE sucursal_devolucion = ?
                     AND estado_devolucion = ?
                     AND fecha_devolucion = ?";
    $stmtTotales = mysqli_prepare($conexion, $sqlTotales);
    if (!$stmtTotales) {
        continue;
    }

    mysqli_stmt_bind_param($stmtTotales, 'iss', $sucursalInforme, $estadoDevolucion, $fechaInforme);
    mysqli_stmt_execute($stmtTotales);
    $totales = mysqli_fetch_assoc(mysqli_stmt_get_result($stmtTotales));
    mysqli_stmt_close($stmtTotales);

    $totalDevoluciones = (int) (isset($totales['TOTALDEVOLUCIONES']) ? $totales['TOTALDEVOLUCIONES'] : 0);
    if ($totalDevoluciones > 0) {
        $totalEuros = round((float) (isset($totales['TOTALEUROSDEVOLUCIONES']) ? $totales['TOTALEUROSDEVOLUCIONES'] : 0), 2);

        $sqlUpdate = "UPDATE informe_diario
                      SET total_devoluciones = ?,
                          total_euros_devoluciones = ?
                      WHERE id_informe = ?";
        $stmtUpdate = mysqli_prepare($conexion, $sqlUpdate);
        if ($stmtUpdate) {
            mysqli_stmt_bind_param($stmtUpdate, 'idi', $totalDevoluciones, $totalEuros, $idInforme);
            mysqli_stmt_execute($stmtUpdate);
            mysqli_stmt_close($stmtUpdate);
        }

        cron_linea('  - Informe ' . $idInforme . ' | devoluciones=' . $totalDevoluciones);
    }

    cron_informe_tarea_generado($idInforme, $sucursalInforme);
}

mysqli_stmt_close($stmtInformes);
