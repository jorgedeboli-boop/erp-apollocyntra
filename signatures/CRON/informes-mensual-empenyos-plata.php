<?php

cron_informe_mensual_recorrer_abiertos('informes-mensual-empenyos-plata', function ($informe, $conexion) {
    $idInforme = (int) $informe['id_informe'];
    $sucursalInforme = (int) $informe['sucursal_informe'];
    $numeroMes = (int) $informe['numero_mes'];
    $yearInforme = (string) $informe['year_informe'];

    $totalLotes = cron_informe_mensual_sum_semanal($conexion, $numeroMes, $yearInforme, $sucursalInforme, 'total_lotes_empenios_plata');
    $totalEuros = round(cron_informe_mensual_sum_semanal($conexion, $numeroMes, $yearInforme, $sucursalInforme, 'total_euros_lotes_empenios_plata'), 2);
    $totalGramos = round(cron_informe_mensual_sum_semanal($conexion, $numeroMes, $yearInforme, $sucursalInforme, 'total_gramos_empenios_plata'), 2);
    $media = $totalGramos > 0 ? round($totalEuros / $totalGramos, 2) : 0;

    $sql = 'UPDATE informe_mensual SET total_lotes_empenios_plata = ?, total_gramos_empenios_plata = ?, total_euros_lotes_empenios_plata = ?, media_pagado_plata_empenyo = ? WHERE id_informe = ?';
    $stmt = mysqli_prepare($conexion, $sql);
    if ($stmt) {
        mysqli_stmt_bind_param($stmt, 'idddi', $totalLotes, $totalGramos, $totalEuros, $media, $idInforme);
        mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);
    }

    cron_informe_mensual_tarea_generado($idInforme, $sucursalInforme);
});
