<?php

/**
 * Informe semanal: ventas por forma de pago (suma desde informe_diario).
 */

cron_informe_semanal_recorrer_abiertos('informes-semanal-ventas-forma-pago', function ($informe, $conexion) {
    $idInforme = (int) $informe['id_informe'];
    $sucursalInforme = (int) $informe['sucursal_informe'];
    $numeroSemana = (int) $informe['numero_semana'];
    $yearInforme = (string) $informe['year_informe'];

    $ventasContado = (int) cron_informe_semanal_sum_diario($conexion, $numeroSemana, $yearInforme, $sucursalInforme, 'ventas_contado');
    $ventasContadoEuros = round(cron_informe_semanal_sum_diario($conexion, $numeroSemana, $yearInforme, $sucursalInforme, 'ventas_contado_euros'), 2);
    $ventasTarjeta = (int) cron_informe_semanal_sum_diario($conexion, $numeroSemana, $yearInforme, $sucursalInforme, 'ventas_tarjeta');
    $ventasTarjetaEuros = round(cron_informe_semanal_sum_diario($conexion, $numeroSemana, $yearInforme, $sucursalInforme, 'ventas_tarjeta_euros'), 2);
    $ventasTransferencia = (int) cron_informe_semanal_sum_diario($conexion, $numeroSemana, $yearInforme, $sucursalInforme, 'ventas_transferencia');
    $ventasTransferenciaEuros = round(cron_informe_semanal_sum_diario($conexion, $numeroSemana, $yearInforme, $sucursalInforme, 'ventas_transferencia_euros'), 2);
    $ventasBizum = (int) cron_informe_semanal_sum_diario($conexion, $numeroSemana, $yearInforme, $sucursalInforme, 'ventas_bizum');
    $ventasBizumEuros = round(cron_informe_semanal_sum_diario($conexion, $numeroSemana, $yearInforme, $sucursalInforme, 'ventas_bizum_euros'), 2);

    $sql = 'UPDATE informe_semanal
            SET ventas_contado = ?,
                ventas_contado_euros = ?,
                ventas_tarjeta = ?,
                ventas_tarjeta_euros = ?,
                ventas_transferencia = ?,
                ventas_transferencia_euros = ?,
                ventas_bizum = ?,
                ventas_bizum_euros = ?
            WHERE id_informe = ?';
    $stmt = mysqli_prepare($conexion, $sql);
    if ($stmt) {
        mysqli_stmt_bind_param(
            $stmt,
            'ididididi',
            $ventasContado,
            $ventasContadoEuros,
            $ventasTarjeta,
            $ventasTarjetaEuros,
            $ventasTransferencia,
            $ventasTransferenciaEuros,
            $ventasBizum,
            $ventasBizumEuros,
            $idInforme
        );
        mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);
    }

    cron_linea(
        '  - Informe ' . $idInforme
        . ' | contado=' . $ventasContado . '/' . $ventasContadoEuros
        . ' | tarjeta=' . $ventasTarjeta . '/' . $ventasTarjetaEuros
        . ' | transferencia=' . $ventasTransferencia . '/' . $ventasTransferenciaEuros
        . ' | bizum=' . $ventasBizum . '/' . $ventasBizumEuros
    );
    cron_informe_semanal_tarea_generado($idInforme, $sucursalInforme);
});
