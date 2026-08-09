<?php

/**
 * Informe diario: empeños perdidos en la fecha del informe.
 * Lee lotes_joyeria (vista unificada alimentada por triggers desde lotes_{id}).
 */

$ctx = cron_informe_contexto('informes-empenyos-perdidos');
if (!$ctx) {
    return;
}

$conexion = $ctx['conexion'];
$fechaInformeToday = $ctx['fecha'];
$estadoLote = 'perdido';
$compraOpcion = 'si';

$stmtInformes = cron_informe_stmt_abiertos($conexion, $fechaInformeToday);
if (!$stmtInformes) {
    cron_linea('ERROR informes-empenyos-perdidos preparando consulta de informes.');
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
                     AND fecha_perdido = ?
                     AND sucursal = ?";
    $stmtTotales = mysqli_prepare($conexion, $sqlTotales);
    if (!$stmtTotales) {
        continue;
    }

    mysqli_stmt_bind_param($stmtTotales, 'sssi', $compraOpcion, $estadoLote, $fechaInforme, $sucursalInforme);
    mysqli_stmt_execute($stmtTotales);
    $totales = mysqli_fetch_assoc(mysqli_stmt_get_result($stmtTotales));
    mysqli_stmt_close($stmtTotales);

    $totalLotes = isset($totales['TOTALLOTES']) ? (int) $totales['TOTALLOTES'] : 0;
    if ($totalLotes > 0) {
        $totalPrecio = round((float) $totales['TOTALPRECIOCOMPRA'], 2);
        $totalGramos = round((float) $totales['TOTALGRAMOS'], 2);

        $sqlUpdate = "UPDATE informe_diario
                      SET total_empenyos_perdidos = ?,
                          total_gramos_empenyos_perdidos = ?,
                          total_euros_empenios_perdidos = ?
                      WHERE id_informe = ?";
        $stmtUpdate = mysqli_prepare($conexion, $sqlUpdate);
        if ($stmtUpdate) {
            mysqli_stmt_bind_param($stmtUpdate, 'iddi', $totalLotes, $totalGramos, $totalPrecio, $idInforme);
            mysqli_stmt_execute($stmtUpdate);
            mysqli_stmt_close($stmtUpdate);
        }

        cron_linea('  - Informe ' . $idInforme . ' | perdidos | lotes=' . $totalLotes);
    }

    cron_informe_tarea_generado($idInforme, $sucursalInforme);
}

mysqli_stmt_close($stmtInformes);
