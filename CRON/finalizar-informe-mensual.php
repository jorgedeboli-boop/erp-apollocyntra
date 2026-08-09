<?php

/**
 * Finaliza informes mensuales abiertos del año en curso.
 */

$ctx = cron_informe_mensual_contexto('finalizar-informe-mensual');
if (!$ctx) {
    return;
}

$conexion = $ctx['conexion'];
$anyoListado = $ctx['anyo'];

global $numero_mes;

$origenTestCron = 'finalizar informe inside del mes Nº ' . (isset($numero_mes) ? (int) $numero_mes : 0);
$sqlTestCron = 'INSERT INTO test_cron (hora_insert, origen) VALUES (NOW(), ?)';
$stmtTestCron = mysqli_prepare($conexion, $sqlTestCron);
if ($stmtTestCron) {
    mysqli_stmt_bind_param($stmtTestCron, 's', $origenTestCron);
    mysqli_stmt_execute($stmtTestCron);
    mysqli_stmt_close($stmtTestCron);
}

$stmt = cron_informe_mensual_stmt_abiertos($conexion, $anyoListado);
if (!$stmt) {
    cron_linea('ERROR finalizar-informe-mensual preparando consulta.');
    return;
}

$sqlUpdate = "UPDATE informe_mensual SET estado_cron_informe = 'finalizado_cron', ultima_actualizacion = NOW() WHERE id_informe = ?";
$stmtUpdate = mysqli_prepare($conexion, $sqlUpdate);

$resultado = mysqli_stmt_get_result($stmt);
while ($informe = $resultado ? mysqli_fetch_assoc($resultado) : false) {
    $idInforme = (int) $informe['id_informe'];
    $sucursalInforme = (int) $informe['sucursal_informe'];
    $numeroMesInforme = (int) $informe['numero_mes'];

    if ($stmtUpdate) {
        mysqli_stmt_bind_param($stmtUpdate, 'i', $idInforme);
        mysqli_stmt_execute($stmtUpdate);
    }

    registrar_tareas_cron(
        'Finalizo informe mensual Nº ' . $idInforme . ' de la Sucursal ' . $sucursalInforme . ' del mes Nº ' . $numeroMesInforme
    );

    cron_linea('  - Informe mensual ' . $idInforme . ' finalizado.');
}

if ($stmtUpdate) {
    mysqli_stmt_close($stmtUpdate);
}
mysqli_stmt_close($stmt);
