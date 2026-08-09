<?php

/**
 * Finaliza informes semanales abiertos del año en curso.
 */

$ctx = cron_informe_semanal_contexto('finalizar-informe-semanal');
if (!$ctx) {
    return;
}

$conexion = $ctx['conexion'];
$anyoListado = $ctx['anyo'];

global $numeroSemana;

$origenTestCron = 'finalizar informe inside de la semana Nº ' . (isset($numeroSemana) ? (int) $numeroSemana : 0);
$sqlTestCron = 'INSERT INTO test_cron (hora_insert, origen) VALUES (NOW(), ?)';
$stmtTestCron = mysqli_prepare($conexion, $sqlTestCron);
if ($stmtTestCron) {
    mysqli_stmt_bind_param($stmtTestCron, 's', $origenTestCron);
    mysqli_stmt_execute($stmtTestCron);
    mysqli_stmt_close($stmtTestCron);
}

$stmt = cron_informe_semanal_stmt_abiertos($conexion, $anyoListado);
if (!$stmt) {
    cron_linea('ERROR finalizar-informe-semanal preparando consulta.');
    return;
}

$sqlUpdate = "UPDATE informe_semanal SET estado_cron_informe = 'finalizado_cron', ultima_actualizacion = NOW() WHERE id_informe = ?";
$stmtUpdate = mysqli_prepare($conexion, $sqlUpdate);

$resultado = mysqli_stmt_get_result($stmt);
while ($informe = $resultado ? mysqli_fetch_assoc($resultado) : false) {
    $idInforme = (int) $informe['id_informe'];
    $sucursalInforme = (int) $informe['sucursal_informe'];
    $numeroSemanaInforme = (int) $informe['numero_semana'];

    if ($stmtUpdate) {
        mysqli_stmt_bind_param($stmtUpdate, 'i', $idInforme);
        mysqli_stmt_execute($stmtUpdate);
    }

    registrar_tareas_cron(
        'Finalizo informe semanal Nº ' . $idInforme . ' de la Sucursal ' . $sucursalInforme . ' de la semana Nº ' . $numeroSemanaInforme
    );

    cron_linea('  - Informe semanal ' . $idInforme . ' finalizado.');
}

if ($stmtUpdate) {
    mysqli_stmt_close($stmtUpdate);
}
mysqli_stmt_close($stmt);
