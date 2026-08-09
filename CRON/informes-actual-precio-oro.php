<?php

/**
 * Informe actual: precio del oro vigente.
 */

$ctx = cron_informe_actual_contexto('informes-actual-precio-oro');
if (!$ctx) {
    return;
}

$conexion = $ctx['conexion'];
$fechaInformeToday = $ctx['fecha'];

$sqlPrecio = 'SELECT precio_oro FROM precio_oro WHERE id_precio_oro = (SELECT MAX(id_precio_oro) FROM precio_oro) LIMIT 1';
$resultadoPrecio = mysqli_query($conexion, $sqlPrecio);
$precioOro = 0.0;

if ($resultadoPrecio) {
    $filaPrecio = mysqli_fetch_assoc($resultadoPrecio);
    $precioOro = isset($filaPrecio['precio_oro']) ? (float) $filaPrecio['precio_oro'] : 0.0;
    mysqli_free_result($resultadoPrecio);
}

$stmtInformes = cron_informe_actual_stmt_abiertos($conexion, $fechaInformeToday);
if (!$stmtInformes) {
    cron_linea('ERROR informes-actual-precio-oro preparando consulta de informes.');
    return;
}

$resultadoInformes = mysqli_stmt_get_result($stmtInformes);

while ($informe = $resultadoInformes ? mysqli_fetch_assoc($resultadoInformes) : false) {
    $idInforme = (int) $informe['id_informe'];
    $sucursalInforme = (int) $informe['sucursal_informe'];

    $sqlUpdate = 'UPDATE informe_actual SET precio_oro = ? WHERE id_informe = ?';
    $stmtUpdate = mysqli_prepare($conexion, $sqlUpdate);
    if ($stmtUpdate) {
        mysqli_stmt_bind_param($stmtUpdate, 'di', $precioOro, $idInforme);
        mysqli_stmt_execute($stmtUpdate);
        mysqli_stmt_close($stmtUpdate);
    }

    cron_linea('  - Informe ' . $idInforme . ' | precio_oro=' . $precioOro);
    cron_informe_actual_tarea_generado($idInforme, $sucursalInforme);
}

mysqli_stmt_close($stmtInformes);
