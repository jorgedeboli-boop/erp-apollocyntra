<?php

cron_informe_mensual_recorrer_abiertos('informes-mensual-ventas', function ($informe, $conexion) {
    $idInforme = (int) $informe['id_informe'];
    $sucursalInforme = (int) $informe['sucursal_informe'];
    $numeroMes = (int) $informe['numero_mes'];
    $yearInforme = (string) $informe['year_informe'];

    $totalVentas = cron_informe_mensual_sum_semanal($conexion, $numeroMes, $yearInforme, $sucursalInforme, 'total_ventas');
    if ($totalVentas <= 0) {
        return;
    }

    $totalEuros = round(cron_informe_mensual_sum_semanal($conexion, $numeroMes, $yearInforme, $sucursalInforme, 'total_euros_ventas'), 2);
    $totalGramos = round(cron_informe_mensual_sum_semanal($conexion, $numeroMes, $yearInforme, $sucursalInforme, 'total_gramos_ventas'), 2);
    $totalCoste = round(cron_informe_mensual_sum_semanal($conexion, $numeroMes, $yearInforme, $sucursalInforme, 'total_coste_art_venta'), 2);
    $totalBeneficio = round($totalEuros - $totalCoste, 2);
    $mediaVenta = $totalVentas > 0 ? round($totalEuros / $totalVentas, 2) : 0;

    $sql = 'UPDATE informe_mensual SET total_gramos_ventas = ?, total_ventas = ?, total_euros_ventas = ?, total_media_ventas = ?, total_coste_art_venta = ?, total_beneficio_ventas = ? WHERE id_informe = ?';
    $stmt = mysqli_prepare($conexion, $sql);
    if ($stmt) {
        mysqli_stmt_bind_param($stmt, 'ddddddi', $totalGramos, $totalVentas, $totalEuros, $mediaVenta, $totalCoste, $totalBeneficio, $idInforme);
        mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);
    }

    cron_informe_mensual_tarea_generado($idInforme, $sucursalInforme);
});
