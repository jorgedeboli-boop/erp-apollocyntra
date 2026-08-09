<?php

cron_informe_semanal_recorrer_abiertos('informes-semanal-compras-oro', function ($informe, $conexion) {
    $idInforme = (int) $informe['id_informe'];
    $sucursalInforme = (int) $informe['sucursal_informe'];
    $numeroSemana = (int) $informe['numero_semana'];
    $yearInforme = (string) $informe['year_informe'];

    $totalLotes = cron_informe_semanal_sum_diario($conexion, $numeroSemana, $yearInforme, $sucursalInforme, 'total_lotes_compra_oro');
    $totalEuros = round(cron_informe_semanal_sum_diario($conexion, $numeroSemana, $yearInforme, $sucursalInforme, 'total_euros_lotes_compra_oro'), 2);
    $totalGramos = round(cron_informe_semanal_sum_diario($conexion, $numeroSemana, $yearInforme, $sucursalInforme, 'total_gramos_compra_oro'), 2);
    $media = $totalLotes > 0 ? round($totalEuros / $totalLotes, 2) : 0;

    $sql = 'UPDATE informe_semanal SET total_lotes_compra_oro = ?, total_gramos_compra_oro = ?, total_euros_lotes_compra_oro = ?, media_pagado_oro_compra = ? WHERE id_informe = ?';
    $stmt = mysqli_prepare($conexion, $sql);
    if ($stmt) {
        mysqli_stmt_bind_param($stmt, 'idddi', $totalLotes, $totalGramos, $totalEuros, $media, $idInforme);
        mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);
    }

    cron_informe_semanal_tarea_generado($idInforme, $sucursalInforme);
});
