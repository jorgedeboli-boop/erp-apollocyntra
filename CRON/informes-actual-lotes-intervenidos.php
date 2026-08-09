<?php

/**
 * Informe actual: lotes intervenidos.
 * Lee lotes_joyeria (vista unificada alimentada por triggers desde lotes_{id}).
 */

$ctx = cron_informe_actual_contexto('informes-actual-lotes-intervenidos');
if (!$ctx) {
    return;
}

$conexion = $ctx['conexion'];
$fechaInformeToday = $ctx['fecha'];
$estadoLote = 'intervenido';

$stmtInformes = cron_informe_actual_stmt_abiertos($conexion, $fechaInformeToday);
if (!$stmtInformes) {
    cron_linea('ERROR informes-actual-lotes-intervenidos preparando consulta de informes.');
    return;
}

$resultadoInformes = mysqli_stmt_get_result($stmtInformes);

while ($informe = $resultadoInformes ? mysqli_fetch_assoc($resultadoInformes) : false) {
    $idInforme = (int) $informe['id_informe'];
    $sucursalInforme = (int) $informe['sucursal_informe'];
    $fechaInforme = (string) $informe['fecha_informe'];

    $sqlTotales = "SELECT COUNT(identificador) AS TOTALLOTES,
                          SUM(precio_compra) AS TOTALPRECIOCOMPRA,
                          SUM(peso) AS TOTALGRAMOS
                   FROM lotes_joyeria
                   WHERE estado_lote = ?
                     AND fecha_intervenido = ?
                     AND sucursal = ?";
    $stmtTotales = mysqli_prepare($conexion, $sqlTotales);
    if (!$stmtTotales) {
        continue;
    }

    mysqli_stmt_bind_param($stmtTotales, 'ssi', $estadoLote, $fechaInforme, $sucursalInforme);
    mysqli_stmt_execute($stmtTotales);
    $totales = mysqli_fetch_assoc(mysqli_stmt_get_result($stmtTotales));
    mysqli_stmt_close($stmtTotales);

    $totalLotes = isset($totales['TOTALLOTES']) ? (int) $totales['TOTALLOTES'] : 0;
    if ($totalLotes > 0) {
        $totalPrecio = round((float) $totales['TOTALPRECIOCOMPRA'], 2);
        $totalGramos = round((float) $totales['TOTALGRAMOS'], 2);

        $sqlUpdate = "UPDATE informe_actual
                      SET total_contratos_intervenidos = ?,
                          total_gramos_contratos_intervenidos = ?,
                          total_euros_contratos_intervenidos = ?
                      WHERE id_informe = ?";
        $stmtUpdate = mysqli_prepare($conexion, $sqlUpdate);
        if ($stmtUpdate) {
            mysqli_stmt_bind_param($stmtUpdate, 'iddi', $totalLotes, $totalGramos, $totalPrecio, $idInforme);
            mysqli_stmt_execute($stmtUpdate);
            mysqli_stmt_close($stmtUpdate);
        }

        cron_linea('  - Informe ' . $idInforme . ' | intervenidos | lotes=' . $totalLotes);
    }

    cron_informe_actual_tarea_generado($idInforme, $sucursalInforme);
}

mysqli_stmt_close($stmtInformes);
