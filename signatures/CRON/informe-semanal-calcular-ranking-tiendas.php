<?php

/**
 * Calcula ranking de tiendas por semana (porcentaje y posición).
 */

$ctx = cron_informe_semanal_contexto('informe-semanal-calcular-ranking-tiendas');
if (!$ctx) {
    return;
}

$conexion = $ctx['conexion'];
$anyoListado = $ctx['anyo'];

$sqlSemanas = "SELECT DISTINCT numero_semana, year_informe
               FROM informe_semanal
               WHERE estado_informe = 'abierto'
                 AND year_informe = ?
               ORDER BY numero_semana ASC";
$stmtSemanas = mysqli_prepare($conexion, $sqlSemanas);
if (!$stmtSemanas) {
    cron_linea('ERROR informe-semanal-calcular-ranking-tiendas preparando semanas.');
    return;
}

mysqli_stmt_bind_param($stmtSemanas, 's', $anyoListado);
mysqli_stmt_execute($stmtSemanas);
$resultadoSemanas = mysqli_stmt_get_result($stmtSemanas);

while ($semana = $resultadoSemanas ? mysqli_fetch_assoc($resultadoSemanas) : false) {
    $numeroSemana = (int) $semana['numero_semana'];
    $yearInforme = (string) $semana['year_informe'];

    $sqlTotal = 'SELECT SUM(beneficio_tienda) AS total FROM informe_semanal WHERE numero_semana = ? AND year_informe = ?';
    $stmtTotal = mysqli_prepare($conexion, $sqlTotal);
    if (!$stmtTotal) {
        continue;
    }

    mysqli_stmt_bind_param($stmtTotal, 'is', $numeroSemana, $yearInforme);
    mysqli_stmt_execute($stmtTotal);
    $filaTotal = mysqli_fetch_assoc(mysqli_stmt_get_result($stmtTotal));
    mysqli_stmt_close($stmtTotal);

    $totalBeneficiosTodas = (float) (isset($filaTotal['total']) ? $filaTotal['total'] : 0);
    if ($totalBeneficiosTodas == 0) {
        continue;
    }

    $sqlInformes = 'SELECT id_informe, beneficio_tienda FROM informe_semanal WHERE numero_semana = ? AND year_informe = ? ORDER BY id_informe ASC';
    $stmtInformes = mysqli_prepare($conexion, $sqlInformes);
    if (!$stmtInformes) {
        continue;
    }

    mysqli_stmt_bind_param($stmtInformes, 'is', $numeroSemana, $yearInforme);
    mysqli_stmt_execute($stmtInformes);
    $resultadoInformes = mysqli_stmt_get_result($stmtInformes);

    $sqlUpdatePct = 'UPDATE informe_semanal SET porcentaje_ranking_tienda = ? WHERE id_informe = ?';
    $stmtUpdatePct = mysqli_prepare($conexion, $sqlUpdatePct);

    while ($informe = $resultadoInformes ? mysqli_fetch_assoc($resultadoInformes) : false) {
        $idInforme = (int) $informe['id_informe'];
        $beneficioTienda = (float) $informe['beneficio_tienda'];
        $porcentaje = round(($beneficioTienda / $totalBeneficiosTodas) * 100, 2);

        if ($stmtUpdatePct) {
            mysqli_stmt_bind_param($stmtUpdatePct, 'di', $porcentaje, $idInforme);
            mysqli_stmt_execute($stmtUpdatePct);
        }
    }

    if ($stmtUpdatePct) {
        mysqli_stmt_close($stmtUpdatePct);
    }
    mysqli_stmt_close($stmtInformes);

    $sqlRanking = 'SELECT id_informe FROM informe_semanal WHERE numero_semana = ? AND year_informe = ? ORDER BY porcentaje_ranking_tienda DESC';
    $stmtRanking = mysqli_prepare($conexion, $sqlRanking);
    if (!$stmtRanking) {
        continue;
    }

    mysqli_stmt_bind_param($stmtRanking, 'is', $numeroSemana, $yearInforme);
    mysqli_stmt_execute($stmtRanking);
    $resultadoRanking = mysqli_stmt_get_result($stmtRanking);

    $sqlUpdateRank = 'UPDATE informe_semanal SET ranking_tienda = ? WHERE id_informe = ?';
    $stmtUpdateRank = mysqli_prepare($conexion, $sqlUpdateRank);
    $posicion = 0;

    while ($filaRank = $resultadoRanking ? mysqli_fetch_assoc($resultadoRanking) : false) {
        $posicion++;
        $idInforme = (int) $filaRank['id_informe'];
        if ($stmtUpdateRank) {
            mysqli_stmt_bind_param($stmtUpdateRank, 'ii', $posicion, $idInforme);
            mysqli_stmt_execute($stmtUpdateRank);
        }
    }

    if ($stmtUpdateRank) {
        mysqli_stmt_close($stmtUpdateRank);
    }
    mysqli_stmt_close($stmtRanking);

    cron_linea('  - Ranking semana ' . $numeroSemana . ' | anyo=' . $yearInforme);
}

mysqli_stmt_close($stmtSemanas);
