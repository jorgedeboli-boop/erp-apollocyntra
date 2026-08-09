<?php

cron_informe_mensual_recorrer_abiertos('informes-mensual-operaciones-transferencias', function ($informe, $conexion) {
    $idInforme = (int) $informe['id_informe'];
    $sucursalInforme = (int) $informe['sucursal_informe'];
    $numeroMes = (int) $informe['numero_mes'];
    $yearInforme = (string) $informe['year_informe'];

    $totalEntrada = round(cron_informe_mensual_sum_semanal($conexion, $numeroMes, $yearInforme, $sucursalInforme, 'total_operaciones_trasnferencia_entrada'), 2);
    $totalSalida = round(cron_informe_mensual_sum_semanal($conexion, $numeroMes, $yearInforme, $sucursalInforme, 'total_operaciones_trasnferencia_salida'), 2);

    $sql = 'UPDATE informe_mensual SET total_operaciones_trasnferencia_entrada = ?, total_operaciones_trasnferencia_salida = ? WHERE id_informe = ?';
    $stmt = mysqli_prepare($conexion, $sql);
    if ($stmt) {
        mysqli_stmt_bind_param($stmt, 'ddi', $totalEntrada, $totalSalida, $idInforme);
        mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);
    }

    cron_informe_mensual_tarea_generado($idInforme, $sucursalInforme);
});
