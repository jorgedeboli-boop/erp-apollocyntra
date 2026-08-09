<?php

cron_informe_semanal_recorrer_abiertos('informes-semanal-operaciones-transferencias', function ($informe, $conexion) {
    $idInforme = (int) $informe['id_informe'];
    $sucursalInforme = (int) $informe['sucursal_informe'];
    $numeroSemana = (int) $informe['numero_semana'];
    $yearInforme = (string) $informe['year_informe'];

    $totalEntrada = round(cron_informe_semanal_sum_diario($conexion, $numeroSemana, $yearInforme, $sucursalInforme, 'total_operaciones_trasnferencia_entrada'), 2);
    $totalSalida = round(cron_informe_semanal_sum_diario($conexion, $numeroSemana, $yearInforme, $sucursalInforme, 'total_operaciones_trasnferencia_salida'), 2);

    $sql = 'UPDATE informe_semanal SET total_operaciones_trasnferencia_entrada = ?, total_operaciones_trasnferencia_salida = ? WHERE id_informe = ?';
    $stmt = mysqli_prepare($conexion, $sql);
    if ($stmt) {
        mysqli_stmt_bind_param($stmt, 'ddi', $totalEntrada, $totalSalida, $idInforme);
        mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);
    }

    cron_informe_semanal_tarea_generado($idInforme, $sucursalInforme);
});
