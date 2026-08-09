<?php

cron_informe_mensual_recorrer_abiertos('informes-mensual-operaciones-bizum', function ($informe, $conexion) {
    $idInforme = (int) $informe['id_informe'];
    $sucursalInforme = (int) $informe['sucursal_informe'];
    $numeroMes = (int) $informe['numero_mes'];
    $yearInforme = (string) $informe['year_informe'];

    $total = round(cron_informe_mensual_sum_semanal($conexion, $numeroMes, $yearInforme, $sucursalInforme, 'total_operaciones_bizum'), 2);

    $sql = 'UPDATE informe_mensual SET total_operaciones_bizum = ? WHERE id_informe = ?';
    $stmt = mysqli_prepare($conexion, $sql);
    if ($stmt) {
        mysqli_stmt_bind_param($stmt, 'di', $total, $idInforme);
        mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);
    }

    cron_informe_mensual_tarea_generado($idInforme, $sucursalInforme);
});
