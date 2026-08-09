<?php

cron_informe_mensual_recorrer_abiertos('informes-mensual-caja', function ($informe, $conexion) {
    $idInforme = (int) $informe['id_informe'];
    $sucursalInforme = (int) $informe['sucursal_informe'];
    $numeroMes = (int) $informe['numero_mes'];
    $yearInforme = (string) $informe['year_informe'];

    $totalEntradas = round(cron_informe_mensual_sum_semanal($conexion, $numeroMes, $yearInforme, $sucursalInforme, 'total_caja_entradas'), 2);
    $totalSalidas = round(cron_informe_mensual_sum_semanal($conexion, $numeroMes, $yearInforme, $sucursalInforme, 'total_caja_salidas'), 2);

    $sql = 'UPDATE informe_mensual SET total_caja_entradas = ?, total_caja_salidas = ? WHERE id_informe = ?';
    $stmt = mysqli_prepare($conexion, $sql);
    if ($stmt) {
        mysqli_stmt_bind_param($stmt, 'ddi', $totalEntradas, $totalSalidas, $idInforme);
        mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);
    }

    cron_linea('  - Informe ' . $idInforme . ' | caja | entradas=' . $totalEntradas . ' | salidas=' . $totalSalidas);
    cron_informe_mensual_tarea_generado($idInforme, $sucursalInforme);
});
