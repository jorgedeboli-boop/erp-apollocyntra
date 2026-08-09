<?php

/**
 * Informe diario: totales de compras de oro por sucursal (lotes en estado compra).
 * Lee lotes_joyeria (vista unificada alimentada por triggers desde lotes_{id}).
 */

$conexion = cron_obtener_conexion();
if (!$conexion) {
    cron_linea('ERROR informes-compras-oro: sin conexion a base de datos.');
    return;
}

if (!isset($fecha_informe_today) || $fecha_informe_today === '') {
    $fecha_informe_today = date('Y-m-d');
}

cron_linea('>> Paso: informes-compras-oro | fecha=' . $fecha_informe_today);

$tipoDeLote = 'oro';
$estadoLote = 'compra';
$compraOpcion = 'no';

// CRON_MANUAL: sin filtro estado_informe (incluye cerrados)
$sqlInformes = "SELECT id_informe, sucursal_informe, fecha_informe, total_salidas
                FROM informe_diario
                WHERE fecha_informe = ?
                ORDER BY id_informe ASC";
$stmtInformes = mysqli_prepare($conexion, $sqlInformes);

if (!$stmtInformes) {
    cron_linea('ERROR informes-compras-oro preparando consulta de informes: ' . mysqli_error($conexion));
    return;
}

mysqli_stmt_bind_param($stmtInformes, 's', $fecha_informe_today);

if (!mysqli_stmt_execute($stmtInformes)) {
    cron_linea('ERROR informes-compras-oro consultando informes: ' . mysqli_stmt_error($stmtInformes));
    mysqli_stmt_close($stmtInformes);
    return;
}

$resultadoInformes = mysqli_stmt_get_result($stmtInformes);

while ($informe = $resultadoInformes ? mysqli_fetch_assoc($resultadoInformes) : false) {

    $idInforme = isset($informe['id_informe']) ? (int) $informe['id_informe'] : 0;
    $sucursalInforme = isset($informe['sucursal_informe']) ? (int) $informe['sucursal_informe'] : 0;
    $fechaInforme = isset($informe['fecha_informe']) ? (string) $informe['fecha_informe'] : $fecha_informe_today;

    if ($idInforme <= 0 || $sucursalInforme <= 0) {
        continue;
    }

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
        cron_linea('  - Informe ' . $idInforme . ': error en lotes_joyeria — ' . mysqli_error($conexion));
        continue;
    }

    mysqli_stmt_bind_param($stmtTotales, 'ssssi', $compraOpcion, $estadoLote, $fechaInforme, $tipoDeLote, $sucursalInforme);

    if (!mysqli_stmt_execute($stmtTotales)) {
        cron_linea('  - Informe ' . $idInforme . ': error consultando lotes_joyeria — ' . mysqli_stmt_error($stmtTotales));
        mysqli_stmt_close($stmtTotales);
        continue;
    }

    $resultadoTotales = mysqli_stmt_get_result($stmtTotales);
    $totales = $resultadoTotales ? mysqli_fetch_assoc($resultadoTotales) : null;
    mysqli_stmt_close($stmtTotales);

    $totalLotesCompraOro = isset($totales['TOTALLOTES']) ? (int) $totales['TOTALLOTES'] : 0;

    if ($totalLotesCompraOro > 0) {
        $totalPrecioCompraOro = isset($totales['TOTALPRECIOCOMPRA']) ? (float) $totales['TOTALPRECIOCOMPRA'] : 0.0;
        $totalGramosOro = isset($totales['TOTALGRAMOS']) ? (float) $totales['TOTALGRAMOS'] : 0.0;

        $mediaPagadoGramoOro = $totalGramosOro > 0
            ? round($totalPrecioCompraOro / $totalGramosOro, 2)
            : 0.0;
        $totalPrecioCompraOro = round($totalPrecioCompraOro, 2);
        $totalGramosOro = round($totalGramosOro, 2);

        cron_linea('  - Informe ' . $idInforme . ' | sucursal ' . $sucursalInforme . ' | fecha ' . $fechaInforme);
        cron_linea('    tipo: ' . $tipoDeLote);
        cron_linea('    TOTALLOTES_COMPRA_ORO: ' . $totalLotesCompraOro);
        cron_linea('    TOTALPRECIOCOMPRA_ORO: ' . $totalPrecioCompraOro);
        cron_linea('    MEDIA_PAGADO_GRAMO_ORO: ' . $mediaPagadoGramoOro);
        cron_linea('    TOTALGRAMOS_ORO: ' . $totalGramosOro);

        $sqlUpdate = "UPDATE informe_diario
                      SET total_lotes_compra_oro = ?,
                          total_gramos_compra_oro = ?,
                          total_euros_lotes_compra_oro = ?,
                          media_pagado_oro_compra = ?
                      WHERE id_informe = ?";
        $stmtUpdate = mysqli_prepare($conexion, $sqlUpdate);

        if (!$stmtUpdate) {
            cron_linea('    ERROR preparando UPDATE informe: ' . mysqli_error($conexion));
        } else {
            mysqli_stmt_bind_param(
                $stmtUpdate,
                'idddi',
                $totalLotesCompraOro,
                $totalGramosOro,
                $totalPrecioCompraOro,
                $mediaPagadoGramoOro,
                $idInforme
            );

            if (!mysqli_stmt_execute($stmtUpdate)) {
                cron_linea('    ERROR actualizando informe: ' . mysqli_stmt_error($stmtUpdate));
            }

            mysqli_stmt_close($stmtUpdate);
        }
    }

    registrar_tareas_cron(
        'Genero informe Nº ' . $idInforme . ' de la Sucursal ' . $sucursalInforme
    );
}

mysqli_stmt_close($stmtInformes);
