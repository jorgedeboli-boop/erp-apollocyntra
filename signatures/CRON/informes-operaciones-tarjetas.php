<?php

/**
 * Informe diario: operaciones con tarjeta.
 */

$ctx = cron_informe_contexto('informes-operaciones-tarjetas');
if (!$ctx) {
    return;
}

$conexion = $ctx['conexion'];
$fechaInformeToday = $ctx['fecha'];

$stmtInformes = cron_informe_stmt_abiertos($conexion, $fechaInformeToday);
if (!$stmtInformes) {
    cron_linea('ERROR informes-operaciones-tarjetas preparando consulta de informes.');
    return;
}

$resultadoInformes = mysqli_stmt_get_result($stmtInformes);

while ($informe = $resultadoInformes ? mysqli_fetch_assoc($resultadoInformes) : false) {
    $idInforme = (int) $informe['id_informe'];
    $sucursalInforme = (int) $informe['sucursal_informe'];
    $fechaInforme = (string) $informe['fecha_informe'];

    $sqlTotales = "SELECT SUM(importe) AS TOTALOPERACIONESTARJETA
                   FROM movimientos_tarjeta
                   WHERE sucursal = ? AND DATE(fecha) = ?";
    $stmtTotales = mysqli_prepare($conexion, $sqlTotales);
    if (!$stmtTotales) {
        continue;
    }

    mysqli_stmt_bind_param($stmtTotales, 'is', $sucursalInforme, $fechaInforme);
    mysqli_stmt_execute($stmtTotales);
    $totales = mysqli_fetch_assoc(mysqli_stmt_get_result($stmtTotales));
    mysqli_stmt_close($stmtTotales);

    $totalTarjeta = round((float) (isset($totales['TOTALOPERACIONESTARJETA']) ? $totales['TOTALOPERACIONESTARJETA'] : 0), 2);

    if ($totalTarjeta != 0) {
        $sqlUpdate = "UPDATE informe_diario SET total_operaciones_tarjeta = ? WHERE id_informe = ?";
        $stmtUpdate = mysqli_prepare($conexion, $sqlUpdate);
        if ($stmtUpdate) {
            mysqli_stmt_bind_param($stmtUpdate, 'di', $totalTarjeta, $idInforme);
            mysqli_stmt_execute($stmtUpdate);
            mysqli_stmt_close($stmtUpdate);
        }

        cron_linea('  - Informe ' . $idInforme . ' | tarjeta=' . $totalTarjeta);
    }

    cron_informe_tarea_generado($idInforme, $sucursalInforme);
}

mysqli_stmt_close($stmtInformes);
