<?php

cron_informe_semanal_recorrer_abiertos('informes-semanal-ventas-web', function ($informe, $conexion) {
    $idInforme = (int) $informe['id_informe'];
    $sucursalInforme = (int) $informe['sucursal_informe'];
    $numeroSemana = (int) $informe['numero_semana'];
    $yearInforme = (string) $informe['year_informe'];

    $totalVentas = cron_informe_semanal_sum_diario($conexion, $numeroSemana, $yearInforme, $sucursalInforme, 'ventas_web');
    $totalEuros = round(cron_informe_semanal_sum_diario($conexion, $numeroSemana, $yearInforme, $sucursalInforme, 'total_euros_ventas_web'), 2);
    $mediaVenta = $totalVentas > 0 ? round($totalEuros / $totalVentas, 2) : 0;

    $sql = 'UPDATE informe_semanal SET ventas_web = ?, total_euros_ventas_web = ?, total_media_ventas_web = ? WHERE id_informe = ?';
    $stmt = mysqli_prepare($conexion, $sql);
    if ($stmt) {
        mysqli_stmt_bind_param($stmt, 'dddi', $totalVentas, $totalEuros, $mediaVenta, $idInforme);
        mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);
    }

    cron_informe_semanal_tarea_generado($idInforme, $sucursalInforme);
});
