<?php

/**
 * Calcula gastos mensuales desde tabla gastos y reparte en informes semanales del mes.
 */

$ctx = cron_informe_mensual_contexto('informes-mensual-gasto');
if (!$ctx) {
    return;
}

$conexion = $ctx['conexion'];

$sql = "SELECT id_informe, numero_mes, year_informe, fecha_desde, fecha_hasta, sucursal_informe
        FROM informe_mensual
        WHERE estado_informe = 'abierto'
        ORDER BY id_informe ASC";
$resultado = mysqli_query($conexion, $sql);
if (!$resultado) {
    cron_linea('ERROR informes-mensual-gasto consultando informes.');
    return;
}

$sqlCountSemanas = 'SELECT COUNT(id_informe) AS total FROM informe_semanal WHERE mes_semana = ? AND year_informe = ? AND sucursal_informe = ?';
$stmtCountSemanas = mysqli_prepare($conexion, $sqlCountSemanas);

$sqlSumGastos = 'SELECT SUM(total_gasto) AS total FROM gastos WHERE fecha_gasto BETWEEN ? AND ? AND sucursal_gasto = ?';
$stmtSumGastos = mysqli_prepare($conexion, $sqlSumGastos);

$sqlUpdateMensual = 'UPDATE informe_mensual SET total_gastos = ? WHERE id_informe = ?';
$stmtUpdateMensual = mysqli_prepare($conexion, $sqlUpdateMensual);

$sqlUpdateSemanal = 'UPDATE informe_semanal SET total_gastos = ? WHERE mes_semana = ? AND year_informe = ? AND sucursal_informe = ?';
$stmtUpdateSemanal = mysqli_prepare($conexion, $sqlUpdateSemanal);

while ($informe = mysqli_fetch_assoc($resultado)) {
    $idInforme = (int) $informe['id_informe'];
    $numeroMes = (int) $informe['numero_mes'];
    $yearInforme = (string) $informe['year_informe'];
    $fechaDesde = (string) $informe['fecha_desde'];
    $fechaHasta = (string) $informe['fecha_hasta'];
    $sucursalInforme = (int) $informe['sucursal_informe'];

    $totalSemanas = 0;
    if ($stmtCountSemanas) {
        mysqli_stmt_bind_param($stmtCountSemanas, 'isi', $numeroMes, $yearInforme, $sucursalInforme);
        mysqli_stmt_execute($stmtCountSemanas);
        $filaSemanas = mysqli_fetch_assoc(mysqli_stmt_get_result($stmtCountSemanas));
        $totalSemanas = (int) (isset($filaSemanas['total']) ? $filaSemanas['total'] : 0);
    }

    $totalGastos = 0.0;
    if ($stmtSumGastos) {
        mysqli_stmt_bind_param($stmtSumGastos, 'ssi', $fechaDesde, $fechaHasta, $sucursalInforme);
        mysqli_stmt_execute($stmtSumGastos);
        $filaGastos = mysqli_fetch_assoc(mysqli_stmt_get_result($stmtSumGastos));
        $totalGastos = (float) (isset($filaGastos['total']) ? $filaGastos['total'] : 0);
    }

    $totalGastosMensuales = round($totalGastos, 2);
    $totalGastosSemanales = $totalSemanas > 0 ? round($totalGastos / $totalSemanas, 2) : 0;

    if ($stmtUpdateMensual) {
        mysqli_stmt_bind_param($stmtUpdateMensual, 'di', $totalGastosMensuales, $idInforme);
        mysqli_stmt_execute($stmtUpdateMensual);
    }

    if ($stmtUpdateSemanal) {
        mysqli_stmt_bind_param($stmtUpdateSemanal, 'disi', $totalGastosSemanales, $numeroMes, $yearInforme, $sucursalInforme);
        mysqli_stmt_execute($stmtUpdateSemanal);
    }

    cron_informe_mensual_tarea_generado($idInforme, $sucursalInforme);
}

mysqli_free_result($resultado);
if ($stmtCountSemanas) {
    mysqli_stmt_close($stmtCountSemanas);
}
if ($stmtSumGastos) {
    mysqli_stmt_close($stmtSumGastos);
}
if ($stmtUpdateMensual) {
    mysqli_stmt_close($stmtUpdateMensual);
}
if ($stmtUpdateSemanal) {
    mysqli_stmt_close($stmtUpdateSemanal);
}
