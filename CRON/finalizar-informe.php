<?php

/**
 * Informe diario: marca los informes abiertos de hoy como finalizado_cron.
 */

$ctx = cron_informe_contexto('finalizar-informe');
if (!$ctx) {
    return;
}

$conexion = $ctx['conexion'];
$fechaInformeToday = $ctx['fecha'];

global $numeroSemana;
if (!isset($numeroSemana)) {
    $numeroSemana = numeroSemanaConFecha($fechaInformeToday);
}

$sqlTestCron = "INSERT INTO test_cron (hora_insert, origen) VALUES (NOW(), ?)";
$stmtTestCron = mysqli_prepare($conexion, $sqlTestCron);
if ($stmtTestCron) {
    $origen = 'finalizar informe inside de la semana Nº ' . (int) $numeroSemana;
    mysqli_stmt_bind_param($stmtTestCron, 's', $origen);
    mysqli_stmt_execute($stmtTestCron);
    mysqli_stmt_close($stmtTestCron);
}

$stmtInformes = cron_informe_stmt_abiertos($conexion, $fechaInformeToday);
if (!$stmtInformes) {
    cron_linea('ERROR finalizar-informe preparando consulta de informes.');
    return;
}

$resultadoInformes = mysqli_stmt_get_result($stmtInformes);

while ($informe = $resultadoInformes ? mysqli_fetch_assoc($resultadoInformes) : false) {
    $idInforme = (int) $informe['id_informe'];
    $sucursalInforme = (int) $informe['sucursal_informe'];

    $sqlUpdate = "UPDATE informe_diario SET estado_cron_informe = 'finalizado_cron' WHERE id_informe = ?";
    $stmtUpdate = mysqli_prepare($conexion, $sqlUpdate);
    if ($stmtUpdate) {
        mysqli_stmt_bind_param($stmtUpdate, 'i', $idInforme);
        mysqli_stmt_execute($stmtUpdate);
        mysqli_stmt_close($stmtUpdate);
    }

    registrar_tareas_cron(
        'Finalizo informe Nº ' . $idInforme . ' de la Sucursal ' . $sucursalInforme . ' de la semana Nº ' . (int) $numeroSemana
    );

    cron_linea('  - Informe ' . $idInforme . ' finalizado (sucursal ' . $sucursalInforme . ')');
}

mysqli_stmt_close($stmtInformes);
cron_linea('>> Fin paso: finalizar-informe');
