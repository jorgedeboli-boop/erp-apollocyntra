<?php

cron_informe_mensual_recorrer_abiertos('informes-mensual-ventas-plazos', function ($informe, $conexion) {
    $idInforme = (int) $informe['id_informe'];
    $sucursalInforme = (int) $informe['sucursal_informe'];
    $numeroMes = (int) $informe['numero_mes'];
    $yearInforme = (string) $informe['year_informe'];

    $totalVentas = cron_informe_mensual_sum_semanal($conexion, $numeroMes, $yearInforme, $sucursalInforme, 'total_ventas_plazo');
    if ($totalVentas <= 0) {
        return;
    }

    $totalEuros = round(cron_informe_mensual_sum_semanal($conexion, $numeroMes, $yearInforme, $sucursalInforme, 'total_ventas_plazo_euro'), 2);

    $sql = 'UPDATE informe_mensual SET total_ventas_plazo = ?, total_ventas_plazo_euro = ? WHERE id_informe = ?';
    $stmt = mysqli_prepare($conexion, $sql);
    if ($stmt) {
        mysqli_stmt_bind_param($stmt, 'ddi', $totalVentas, $totalEuros, $idInforme);
        mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);
    }

    cron_informe_mensual_tarea_generado($idInforme, $sucursalInforme);
});
