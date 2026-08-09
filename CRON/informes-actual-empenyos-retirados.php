<?php

/**
 * Informe actual: empeños retirados (importe desde historico_renovaciones_gobal).
 * Lotes: lotes_joyeria (vista unificada alimentada por triggers desde lotes_{id}).
 */

$ctx = cron_informe_actual_contexto('informes-actual-empenyos-retirados');
if (!$ctx) {
    return;
}

$conexion = $ctx['conexion'];
$fechaInformeToday = $ctx['fecha'];
$estadoLote = 'retirado';
$compraOpcion = 'si';
$estadoHistorico = 'Retirado';

$stmtInformes = cron_informe_actual_stmt_abiertos($conexion, $fechaInformeToday);
if (!$stmtInformes) {
    cron_linea('ERROR informes-actual-empenyos-retirados preparando consulta de informes.');
    return;
}

$resultadoInformes = mysqli_stmt_get_result($stmtInformes);

while ($informe = $resultadoInformes ? mysqli_fetch_assoc($resultadoInformes) : false) {
    $idInforme = (int) $informe['id_informe'];
    $sucursalInforme = (int) $informe['sucursal_informe'];
    $fechaInforme = (string) $informe['fecha_informe'];

    $sqlLotes = "SELECT COUNT(identificador) AS TOTALLOTES, SUM(peso) AS TOTALGRAMOS
                 FROM lotes_joyeria
                 WHERE compra_opcion = ?
                   AND estado_lote = ?
                   AND fecha_retirado = ?
                   AND sucursal = ?";
    $stmtLotes = mysqli_prepare($conexion, $sqlLotes);
    if (!$stmtLotes) {
        continue;
    }

    mysqli_stmt_bind_param($stmtLotes, 'sssi', $compraOpcion, $estadoLote, $fechaInforme, $sucursalInforme);
    mysqli_stmt_execute($stmtLotes);
    $totalesLotes = mysqli_fetch_assoc(mysqli_stmt_get_result($stmtLotes));
    mysqli_stmt_close($stmtLotes);

    $totalLotes = isset($totalesLotes['TOTALLOTES']) ? (int) $totalesLotes['TOTALLOTES'] : 0;
    if ($totalLotes > 0) {
        $sqlImporte = "SELECT SUM(importe_renovacion) AS TOTALPRECIOCOMPRA
                       FROM historico_renovaciones_gobal
                       WHERE estado_historico = ?
                         AND fecha_insert = ?
                         AND sucursal_id = ?";
        $stmtImporte = mysqli_prepare($conexion, $sqlImporte);
        $totalPrecio = 0.0;
        if ($stmtImporte) {
            mysqli_stmt_bind_param($stmtImporte, 'ssi', $estadoHistorico, $fechaInforme, $sucursalInforme);
            mysqli_stmt_execute($stmtImporte);
            $filaImporte = mysqli_fetch_assoc(mysqli_stmt_get_result($stmtImporte));
            $totalPrecio = isset($filaImporte['TOTALPRECIOCOMPRA']) ? (float) $filaImporte['TOTALPRECIOCOMPRA'] : 0.0;
            mysqli_stmt_close($stmtImporte);
        }

        $totalGramos = round((float) $totalesLotes['TOTALGRAMOS'], 2);
        $totalPrecio = round($totalPrecio, 2);

        $sqlUpdate = "UPDATE informe_actual
                      SET total_empenyos_retirados = ?,
                          total_gramos_empenios_retirados = ?,
                          total_euros_empenyos_retirados = ?
                      WHERE id_informe = ?";
        $stmtUpdate = mysqli_prepare($conexion, $sqlUpdate);
        if ($stmtUpdate) {
            mysqli_stmt_bind_param($stmtUpdate, 'iddi', $totalLotes, $totalGramos, $totalPrecio, $idInforme);
            mysqli_stmt_execute($stmtUpdate);
            mysqli_stmt_close($stmtUpdate);
        }

        cron_linea('  - Informe ' . $idInforme . ' | retirados | lotes=' . $totalLotes);
    }

    cron_informe_actual_tarea_generado($idInforme, $sucursalInforme);
}

mysqli_stmt_close($stmtInformes);
