<?php

cron_informe_mensual_recorrer_abiertos('informes-mensual-stock-valorizado', function ($informe, $conexion) {
    $idInforme = (int) $informe['id_informe'];
    $sucursalInforme = (int) $informe['sucursal_informe'];

    $sqlValorizado = "SELECT SUM(precio) AS total_valorizado, SUM(precio_coste) AS total_coste, COUNT(id) AS total_unidades
                      FROM articulos_venta
                      WHERE id_sucursal_destino = ?
                        AND estado = 'enventa'";
    $stmt = mysqli_prepare($conexion, $sqlValorizado);
    if (!$stmt) {
        return;
    }

    mysqli_stmt_bind_param($stmt, 'i', $sucursalInforme);
    mysqli_stmt_execute($stmt);
    $fila = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
    mysqli_stmt_close($stmt);

    $totalValorizado = round((float) (isset($fila['total_valorizado']) ? $fila['total_valorizado'] : 0), 2);
    if ($totalValorizado <= 0) {
        return;
    }

    $totalCoste = round((float) (isset($fila['total_coste']) ? $fila['total_coste'] : 0), 2);
    $totalUnidades = (int) (isset($fila['total_unidades']) ? $fila['total_unidades'] : 0);
    $coeficiente = $totalCoste > 0 ? round($totalValorizado / $totalCoste, 2) : 0;

    $sql = 'UPDATE informe_mensual SET coste_stock_valorizado = ?, stock_articulos = ?, stock_valorizado_eruo = ?, coheficiente_stock_valorizado = ? WHERE id_informe = ?';
    $stmtUpdate = mysqli_prepare($conexion, $sql);
    if ($stmtUpdate) {
        mysqli_stmt_bind_param($stmtUpdate, 'diddi', $totalCoste, $totalUnidades, $totalValorizado, $coeficiente, $idInforme);
        mysqli_stmt_execute($stmtUpdate);
        mysqli_stmt_close($stmtUpdate);
    }

    cron_informe_mensual_tarea_generado($idInforme, $sucursalInforme);
});
