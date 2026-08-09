<?php

/**
 * Informe actual: marca los informes abiertos de hoy como finalizado.
 */

$ctx = cron_informe_actual_contexto('finalizar-informe_actual');
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
    $origen = 'finalizar informe actual fecha ' . $fechaInformeToday;
    mysqli_stmt_bind_param($stmtTestCron, 's', $origen);
    mysqli_stmt_execute($stmtTestCron);
    mysqli_stmt_close($stmtTestCron);
}

$stmtInformes = cron_informe_actual_stmt_abiertos($conexion, $fechaInformeToday);
if (!$stmtInformes) {
    cron_linea('ERROR finalizar-informe_actual preparando consulta de informes.');
    return;
}

$resultadoInformes = mysqli_stmt_get_result($stmtInformes);
$totalFinalizados = 0;

while ($informe = $resultadoInformes ? mysqli_fetch_assoc($resultadoInformes) : false) {
    $idInforme = (int) $informe['id_informe'];
    $sucursalInforme = (int) $informe['sucursal_informe'];

    $sqlUpdate = "UPDATE informe_actual
                  SET estado_informe = 'finalizado',
                      estado_cron_informe = 'finalizado_cron'
                  WHERE id_informe = ?";
    $stmtUpdate = mysqli_prepare($conexion, $sqlUpdate);
    if (!$stmtUpdate) {
        cron_linea('  ERROR preparando UPDATE informe ' . $idInforme);
        continue;
    }

    mysqli_stmt_bind_param($stmtUpdate, 'i', $idInforme);
    if (!mysqli_stmt_execute($stmtUpdate)) {
        cron_linea('  ERROR finalizando informe ' . $idInforme . ': ' . mysqli_stmt_error($stmtUpdate));
        mysqli_stmt_close($stmtUpdate);
        continue;
    }

    mysqli_stmt_close($stmtUpdate);
    $totalFinalizados++;

    registrar_tareas_cron(
        'Finalizo informe actual Nº ' . $idInforme . ' de la Sucursal ' . $sucursalInforme . ' (fecha ' . $fechaInformeToday . ')'
    );

    cron_linea('  - Informe ' . $idInforme . ' finalizado (sucursal ' . $sucursalInforme . ')');
}

mysqli_stmt_close($stmtInformes);

if ($totalFinalizados === 0) {
    cron_linea('  - No hay informes abiertos de hoy para finalizar.');
} else {
    cron_linea('  - Total finalizados: ' . $totalFinalizados);
}

cron_linea('>> Fin paso: finalizar-informe_actual');
