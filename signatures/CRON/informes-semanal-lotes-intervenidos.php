<?php

cron_informe_semanal_recorrer_abiertos('informes-semanal-lotes-intervenidos', function ($informe, $conexion) {
    $idInforme = (int) $informe['id_informe'];
    $sucursalInforme = (int) $informe['sucursal_informe'];
    $numeroSemana = (int) $informe['numero_semana'];
    $yearInforme = (string) $informe['year_informe'];

    $totalLotes = cron_informe_semanal_sum_diario($conexion, $numeroSemana, $yearInforme, $sucursalInforme, 'total_contratos_intervenidos');
    $totalEuros = round(cron_informe_semanal_sum_diario($conexion, $numeroSemana, $yearInforme, $sucursalInforme, 'total_euros_contratos_intervenidos'), 2);
    $totalGramos = round(cron_informe_semanal_sum_diario($conexion, $numeroSemana, $yearInforme, $sucursalInforme, 'total_gramos_contratos_intervenidos'), 2);

    $sql = 'UPDATE informe_semanal SET total_contratos_intervenidos = ?, total_gramos_contratos_intervenidos = ?, total_euros_contratos_intervenidos = ? WHERE id_informe = ?';
    $stmt = mysqli_prepare($conexion, $sql);
    if ($stmt) {
        mysqli_stmt_bind_param($stmt, 'dddi', $totalLotes, $totalGramos, $totalEuros, $idInforme);
        mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);
    }

    cron_informe_semanal_tarea_generado($idInforme, $sucursalInforme);
});
