<?php

cron_informe_mensual_recorrer_abiertos('informes-mensual-beneficio-articulos-venta', function ($informe, $conexion) {
    $idInforme = (int) $informe['id_informe'];
    $sucursalInforme = (int) $informe['sucursal_informe'];
    $numeroMes = (int) $informe['numero_mes'];
    $yearInforme = (string) $informe['year_informe'];

    $totalArticulos = cron_informe_mensual_sum_semanal($conexion, $numeroMes, $yearInforme, $sucursalInforme, 'total_articulos_vendidos');
    $totalCoste = round(cron_informe_mensual_sum_semanal($conexion, $numeroMes, $yearInforme, $sucursalInforme, 'total_coste_art_venta'), 2);
    $totalBeneficio = round(cron_informe_mensual_sum_semanal($conexion, $numeroMes, $yearInforme, $sucursalInforme, 'total_beneficio_ventas'), 2);

    $sql = 'UPDATE informe_mensual SET total_articulos_vendidos = ?, total_coste_art_venta = ?, total_beneficio_ventas = ? WHERE id_informe = ?';
    $stmt = mysqli_prepare($conexion, $sql);
    if ($stmt) {
        mysqli_stmt_bind_param($stmt, 'dddi', $totalArticulos, $totalCoste, $totalBeneficio, $idInforme);
        mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);
    }

    cron_informe_mensual_tarea_generado($idInforme, $sucursalInforme);
});
