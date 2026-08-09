<?php

/**
 * Informe diario: paso de calculo de totales (marcador en tareas_cron).
 */

$ctx = cron_informe_contexto('informes-calculo-totales');
if (!$ctx) {
    return;
}

$conexion = $ctx['conexion'];
$fechaInformeToday = $ctx['fecha'];

$stmtInformes = cron_informe_stmt_abiertos($conexion, $fechaInformeToday);
if (!$stmtInformes) {
    cron_linea('ERROR informes-calculo-totales preparando consulta de informes.');
    return;
}

$resultadoInformes = mysqli_stmt_get_result($stmtInformes);

while ($informe = $resultadoInformes ? mysqli_fetch_assoc($resultadoInformes) : false) {
    $idInforme = (int) $informe['id_informe'];
    $sucursalInforme = (int) $informe['sucursal_informe'];
    cron_informe_tarea_generado($idInforme, $sucursalInforme);
}

mysqli_stmt_close($stmtInformes);
