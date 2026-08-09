<?php

/**
 * Informe actual: ventas del dia por sucursal.
 */

$ctx = cron_informe_actual_contexto('informes-actual-ventas');
if (!$ctx) {
    return;
}

$conexion = $ctx['conexion'];
$fechaInformeToday = $ctx['fecha'];
$estadoVenta = 'vendido';
$estadoArticulo = 'vendido';

$stmtInformes = cron_informe_actual_stmt_abiertos($conexion, $fechaInformeToday);
if (!$stmtInformes) {
    cron_linea('ERROR informes-actual-ventas preparando consulta de informes.');
    return;
}

$resultadoInformes = mysqli_stmt_get_result($stmtInformes);

while ($informe = $resultadoInformes ? mysqli_fetch_assoc($resultadoInformes) : false) {
    $idInforme = (int) $informe['id_informe'];
    $sucursalInforme = (int) $informe['sucursal_informe'];
    $fechaInforme = (string) $informe['fecha_informe'];

    $sqlVentas = "SELECT SUM(precio) AS TOTALEUROSVENTAS, COUNT(id) AS TOTALVENTAS
                  FROM ventas
                  WHERE id_sucursal = ? AND estado = ? AND DATE(fecha) = ?";
    $stmtVentas = mysqli_prepare($conexion, $sqlVentas);
    if (!$stmtVentas) {
        continue;
    }

    mysqli_stmt_bind_param($stmtVentas, 'iss', $sucursalInforme, $estadoVenta, $fechaInforme);
    mysqli_stmt_execute($stmtVentas);
    $ventas = mysqli_fetch_assoc(mysqli_stmt_get_result($stmtVentas));
    mysqli_stmt_close($stmtVentas);

    $totalVentas = (int) (isset($ventas['TOTALVENTAS']) ? $ventas['TOTALVENTAS'] : 0);
    if ($totalVentas <= 0) {
        cron_informe_actual_tarea_generado($idInforme, $sucursalInforme);
        continue;
    }

    $totalEuros = (float) (isset($ventas['TOTALEUROSVENTAS']) ? $ventas['TOTALEUROSVENTAS'] : 0);
    $mediaVenta = round($totalEuros / $totalVentas, 2);

    $sqlArticulos = "SELECT SUM(peso) AS TOTALGRAMOSVENTAS,
                            SUM(precio_coste) AS TOTALCOSTEARTICULOSVENDIDOS
                     FROM articulos_venta
                     WHERE id_sucursal_destino = ? AND estado = ? AND DATE(fecha_vendido) = ?";
    $stmtArticulos = mysqli_prepare($conexion, $sqlArticulos);
    $totalGramos = 0.0;
    $totalCoste = 0.0;
    if ($stmtArticulos) {
        mysqli_stmt_bind_param($stmtArticulos, 'iss', $sucursalInforme, $estadoArticulo, $fechaInforme);
        mysqli_stmt_execute($stmtArticulos);
        $articulos = mysqli_fetch_assoc(mysqli_stmt_get_result($stmtArticulos));
        $totalGramos = (float) (isset($articulos['TOTALGRAMOSVENTAS']) ? $articulos['TOTALGRAMOSVENTAS'] : 0);
        $totalCoste = (float) (isset($articulos['TOTALCOSTEARTICULOSVENDIDOS']) ? $articulos['TOTALCOSTEARTICULOSVENDIDOS'] : 0);
        mysqli_stmt_close($stmtArticulos);
    }

    $totalEuros = round($totalEuros, 2);
    $totalCoste = round($totalCoste, 2);
    $totalBeneficio = round($totalEuros - $totalCoste, 2);
    $totalGramos = round($totalGramos, 2);

    $sqlUpdate = "UPDATE informe_actual
                  SET total_beneficio_ventas = ?,
                      total_coste_art_venta = ?,
                      total_gramos_ventas = ?,
                      total_ventas = ?,
                      total_euros_ventas = ?,
                      total_media_ventas = ?
                  WHERE id_informe = ?";
    $stmtUpdate = mysqli_prepare($conexion, $sqlUpdate);
    if ($stmtUpdate) {
        mysqli_stmt_bind_param(
            $stmtUpdate,
            'dddiddi',
            $totalBeneficio,
            $totalCoste,
            $totalGramos,
            $totalVentas,
            $totalEuros,
            $mediaVenta,
            $idInforme
        );
        mysqli_stmt_execute($stmtUpdate);
        mysqli_stmt_close($stmtUpdate);
    }

    cron_linea('  - Informe ' . $idInforme . ' | ventas=' . $totalVentas . ' | euros=' . $totalEuros);
    cron_informe_actual_tarea_generado($idInforme, $sucursalInforme);
}

mysqli_stmt_close($stmtInformes);
