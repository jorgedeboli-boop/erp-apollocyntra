<?php

cron_informe_mensual_recorrer_abiertos('informes-mensual-beneficio-fundido-total', function ($informe, $conexion) {
    $idInforme = (int) $informe['id_informe'];
    $sucursalInforme = (int) $informe['sucursal_informe'];
    $numeroMes = (int) $informe['numero_mes'];
    $yearInforme = (string) $informe['year_informe'];

    $totalArticulos = cron_informe_mensual_sum_semanal($conexion, $numeroMes, $yearInforme, $sucursalInforme, 'total_articulos_enviado_fundicion');
    $totalGramos = cron_informe_mensual_sum_semanal($conexion, $numeroMes, $yearInforme, $sucursalInforme, 'total_gramos_enviado_fundicion');
    $importeCobrado = cron_informe_mensual_sum_semanal($conexion, $numeroMes, $yearInforme, $sucursalInforme, 'importe_cobrado_funcidion');
    $beneficioFundicion = cron_informe_mensual_sum_semanal($conexion, $numeroMes, $yearInforme, $sucursalInforme, 'beneficio_fundicion');

    $sql = 'UPDATE informe_mensual SET
        total_articulos_enviado_fundicion = ?,
        total_gramos_enviado_fundicion = ?,
        importe_cobrado_funcidion = ?,
        beneficio_fundicion = ?
        WHERE id_informe = ?';
    $stmt = mysqli_prepare($conexion, $sql);
    if ($stmt) {
        mysqli_stmt_bind_param($stmt, 'ddddi', $totalArticulos, $totalGramos, $importeCobrado, $beneficioFundicion, $idInforme);
        mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);
    }

    cron_informe_mensual_tarea_generado($idInforme, $sucursalInforme);
});
