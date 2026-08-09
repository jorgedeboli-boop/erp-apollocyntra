<?php

/**
 * Informe semanal: ajustes de lotes.
 * (Pendiente de implementar la lógica de cálculo.)
 */

cron_informe_semanal_recorrer_abiertos('informes-semanal-ajustes_de_lotes', function ($informe, $conexion) {
    $idInforme = (int) $informe['id_informe'];
    $sucursalInforme = (int) $informe['sucursal_informe'];
    $numeroSemana = (int) $informe['numero_semana'];
    $yearInforme = (string) $informe['year_informe'];

    $totalSalidas = round(cron_informe_semanal_sum_diario($conexion, $numeroSemana, $yearInforme, $sucursalInforme, 'ajustes_de_lotes'), 2);

    $sql = 'UPDATE informe_semanal SET ajustes_de_lotes = ? WHERE id_informe = ?';
    $stmt = mysqli_prepare($conexion, $sql);
    if ($stmt) {
        mysqli_stmt_bind_param($stmt, 'di', $totalSalidas, $idInforme);
        mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);
    }

    cron_linea('  - Informe ' . $idInforme . ' | ajustes de lotes | pendiente');
    cron_informe_semanal_tarea_generado($idInforme, $sucursalInforme);
});
