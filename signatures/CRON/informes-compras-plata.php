<?php

/**
 * Informe diario: totales de compras de plata por sucursal.
 * Lee lotes_joyeria (vista unificada alimentada por triggers desde lotes_{id}).
 */

$ctx = cron_informe_contexto('informes-compras-plata');
if (!$ctx) {
    return;
}

$conexion = $ctx['conexion'];
$fechaInformeToday = $ctx['fecha'];
$tipoDeLote = 'plata';
$estadoLote = 'compra';
$compraOpcion = 'no';

$stmtInformes = cron_informe_stmt_abiertos($conexion, $fechaInformeToday);
if (!$stmtInformes) {
    cron_linea('ERROR informes-compras-plata preparando consulta de informes.');
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
                   WHERE compra_opcion = ?
                     AND estado_lote = ?
                     AND fecha_compra = ?
                     AND tipo_de_lote = ?
                     AND sucursal = ?";
    $stmtTotales = mysqli_prepare($conexion, $sqlTotales);
    if (!$stmtTotales) {
        continue;
    }

    mysqli_stmt_bind_param($stmtTotales, 'ssssi', $compraOpcion, $estadoLote, $fechaInforme, $tipoDeLote, $sucursalInforme);
    mysqli_stmt_execute($stmtTotales);
    $totales = mysqli_fetch_assoc(mysqli_stmt_get_result($stmtTotales));
    mysqli_stmt_close($stmtTotales);

    $totalLotes = isset($totales['TOTALLOTES']) ? (int) $totales['TOTALLOTES'] : 0;
    if ($totalLotes <= 0) {
        cron_informe_tarea_generado($idInforme, $sucursalInforme);
        continue;
    }

    $totalPrecio = round((float) $totales['TOTALPRECIOCOMPRA'], 2);
    $totalGramos = round((float) $totales['TOTALGRAMOS'], 2);
    $mediaGramo = $totalGramos > 0 ? round($totalPrecio / $totalGramos, 2) : 0.0;

    $sqlUpdate = "UPDATE informe_diario
                  SET total_lotes_compra_plata = ?,
                      total_gramos_compra_plata = ?,
                      total_euros_lotes_compra_plata = ?,
                      media_pagado_plata_compra = ?
                  WHERE id_informe = ?";
    $stmtUpdate = mysqli_prepare($conexion, $sqlUpdate);
    if ($stmtUpdate) {
        mysqli_stmt_bind_param($stmtUpdate, 'idddi', $totalLotes, $totalGramos, $totalPrecio, $mediaGramo, $idInforme);
        mysqli_stmt_execute($stmtUpdate);
        mysqli_stmt_close($stmtUpdate);
    }

    cron_linea('  - Informe ' . $idInforme . ' | plata | lotes=' . $totalLotes . ' | euros=' . $totalPrecio);
    cron_informe_tarea_generado($idInforme, $sucursalInforme);
}

mysqli_stmt_close($stmtInformes);
