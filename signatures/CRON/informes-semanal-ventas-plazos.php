<?php

cron_informe_semanal_recorrer_abiertos('informes-semanal-ventas-plazos', function ($informe, $conexion) {
    $idInforme = (int) $informe['id_informe'];
    $sucursalInforme = (int) $informe['sucursal_informe'];
    $numeroSemana = (int) $informe['numero_semana'];
    $yearInforme = (string) $informe['year_informe'];

    $totalVentas = cron_informe_semanal_sum_diario($conexion, $numeroSemana, $yearInforme, $sucursalInforme, 'total_ventas_plazo');
    if ($totalVentas <= 0) {
        return;
    }

    $totalEuros = round(cron_informe_semanal_sum_diario($conexion, $numeroSemana, $yearInforme, $sucursalInforme, 'total_ventas_plazo_euro'), 2);

    $sql = 'UPDATE informe_semanal SET total_ventas_plazo = ?, total_ventas_plazo_euro = ? WHERE id_informe = ?';
    $stmt = mysqli_prepare($conexion, $sql);
    if ($stmt) {
        mysqli_stmt_bind_param($stmt, 'ddi', $totalVentas, $totalEuros, $idInforme);
        mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);
    }

    cron_informe_semanal_tarea_generado($idInforme, $sucursalInforme);
});
