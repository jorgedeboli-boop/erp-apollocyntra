<?php

cron_informe_mensual_recorrer_abiertos('informes-mensual-empenyos-perdidos', function ($informe, $conexion) {
    $idInforme = (int) $informe['id_informe'];
    $sucursalInforme = (int) $informe['sucursal_informe'];
    $numeroMes = (int) $informe['numero_mes'];
    $yearInforme = (string) $informe['year_informe'];

    $totalLotes = cron_informe_mensual_sum_semanal($conexion, $numeroMes, $yearInforme, $sucursalInforme, 'total_empenyos_perdidos');
    $totalEuros = round(cron_informe_mensual_sum_semanal($conexion, $numeroMes, $yearInforme, $sucursalInforme, 'total_euros_empenios_perdidos'), 2);
    $totalGramos = round(cron_informe_mensual_sum_semanal($conexion, $numeroMes, $yearInforme, $sucursalInforme, 'total_gramos_empenyos_perdidos'), 2);

    $sql = 'UPDATE informe_mensual SET total_empenyos_perdidos = ?, total_gramos_empenyos_perdidos = ?, total_euros_empenios_perdidos = ? WHERE id_informe = ?';
    $stmt = mysqli_prepare($conexion, $sql);
    if ($stmt) {
        mysqli_stmt_bind_param($stmt, 'dddi', $totalLotes, $totalGramos, $totalEuros, $idInforme);
        mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);
    }

    cron_informe_mensual_tarea_generado($idInforme, $sucursalInforme);
});
