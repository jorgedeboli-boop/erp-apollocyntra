<?php

/**
 * Informe mensual: ajustes de lotes.
 */

cron_informe_mensual_recorrer_abiertos('informes-mensual-ajustes_de_lotes', function ($informe, $conexion) {
    $idInforme = (int) $informe['id_informe'];
    $sucursalInforme = (int) $informe['sucursal_informe'];
    $numeroMes = (int) $informe['numero_mes'];
    $yearInforme = (string) $informe['year_informe'];

    $totalAjustes = round(cron_informe_mensual_sum_semanal($conexion, $numeroMes, $yearInforme, $sucursalInforme, 'ajustes_de_lotes'), 2);

    $sql = 'UPDATE informe_mensual SET ajustes_de_lotes = ? WHERE id_informe = ?';
    $stmt = mysqli_prepare($conexion, $sql);
    if ($stmt) {
        mysqli_stmt_bind_param($stmt, 'di', $totalAjustes, $idInforme);
        mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);
    }

    cron_linea('  - Informe ' . $idInforme . ' | ajustes de lotes | total=' . $totalAjustes);
    cron_informe_mensual_tarea_generado($idInforme, $sucursalInforme);
});
