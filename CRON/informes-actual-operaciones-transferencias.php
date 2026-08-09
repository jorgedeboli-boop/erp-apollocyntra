<?php

/**
 * Informe actual: operaciones por transferencia (entradas y salidas).
 */

$ctx = cron_informe_actual_contexto('informes-actual-operaciones-transferencias');
if (!$ctx) {
    return;
}

$conexion = $ctx['conexion'];
$fechaInformeToday = $ctx['fecha'];

$stmtInformes = cron_informe_actual_stmt_abiertos($conexion, $fechaInformeToday);
if (!$stmtInformes) {
    cron_linea('ERROR informes-actual-operaciones-transferencias preparando consulta de informes.');
    return;
}

$resultadoInformes = mysqli_stmt_get_result($stmtInformes);

while ($informe = $resultadoInformes ? mysqli_fetch_assoc($resultadoInformes) : false) {
    $idInforme = (int) $informe['id_informe'];
    $sucursalInforme = (int) $informe['sucursal_informe'];
    $fechaInforme = (string) $informe['fecha_informe'];

    $sqlEntradas = "SELECT SUM(entrada) AS TOTALENTRADA
                    FROM movimientos_transferencia
                    WHERE sucursal = ? AND DATE(fecha) = ?";
    $stmtEntradas = mysqli_prepare($conexion, $sqlEntradas);
    $totalEntrada = 0.0;
    if ($stmtEntradas) {
        mysqli_stmt_bind_param($stmtEntradas, 'is', $sucursalInforme, $fechaInforme);
        mysqli_stmt_execute($stmtEntradas);
        $fila = mysqli_fetch_assoc(mysqli_stmt_get_result($stmtEntradas));
        $totalEntrada = round((float) (isset($fila['TOTALENTRADA']) ? $fila['TOTALENTRADA'] : 0), 2);
        mysqli_stmt_close($stmtEntradas);
    }

    if ($totalEntrada != 0) {
        $sqlUpdate = "UPDATE informe_actual SET total_operaciones_trasnferencia_entrada = ? WHERE id_informe = ?";
        $stmtUpdate = mysqli_prepare($conexion, $sqlUpdate);
        if ($stmtUpdate) {
            mysqli_stmt_bind_param($stmtUpdate, 'di', $totalEntrada, $idInforme);
            mysqli_stmt_execute($stmtUpdate);
            mysqli_stmt_close($stmtUpdate);
        }
    }

    $sqlSalidas = "SELECT SUM(salida) AS TOTALSALIDA
                   FROM movimientos_transferencia
                   WHERE sucursal = ? AND DATE(fecha) = ?";
    $stmtSalidas = mysqli_prepare($conexion, $sqlSalidas);
    $totalSalida = 0.0;
    if ($stmtSalidas) {
        mysqli_stmt_bind_param($stmtSalidas, 'is', $sucursalInforme, $fechaInforme);
        mysqli_stmt_execute($stmtSalidas);
        $fila = mysqli_fetch_assoc(mysqli_stmt_get_result($stmtSalidas));
        $totalSalida = round((float) (isset($fila['TOTALSALIDA']) ? $fila['TOTALSALIDA'] : 0), 2);
        mysqli_stmt_close($stmtSalidas);
    }

    if ($totalSalida != 0) {
        $sqlUpdate = "UPDATE informe_actual SET total_operaciones_trasnferencia_salida = ? WHERE id_informe = ?";
        $stmtUpdate = mysqli_prepare($conexion, $sqlUpdate);
        if ($stmtUpdate) {
            mysqli_stmt_bind_param($stmtUpdate, 'di', $totalSalida, $idInforme);
            mysqli_stmt_execute($stmtUpdate);
            mysqli_stmt_close($stmtUpdate);
        }
    }

    cron_linea('  - Informe ' . $idInforme . ' | transferencias | entrada=' . $totalEntrada . ' | salida=' . $totalSalida);
    cron_informe_actual_tarea_generado($idInforme, $sucursalInforme);
}

mysqli_stmt_close($stmtInformes);
