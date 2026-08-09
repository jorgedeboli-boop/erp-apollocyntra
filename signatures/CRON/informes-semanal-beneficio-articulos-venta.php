<?php

cron_informe_semanal_recorrer_abiertos('informes-semanal-beneficio-articulos-venta', function ($informe, $conexion) {
    $idInforme = (int) $informe['id_informe'];
    $sucursalInforme = (int) $informe['sucursal_informe'];
    $numeroSemana = (int) $informe['numero_semana'];
    $yearInforme = (string) $informe['year_informe'];

    $sql = "SELECT
                COUNT(id_articulo_rel) AS total_articulos,
                SUM(precio_coste_venta) AS total_coste,
                SUM(rentabilidad_venta) AS total_beneficio
            FROM rel_articulos_estados
            WHERE rel_numero_semana_venta = ?
              AND year_rel = ?
              AND rel_id_sucursal_venta = ?
              AND estado_articulo = 'Vendido'";
    $stmt = mysqli_prepare($conexion, $sql);
    if (!$stmt) {
        return;
    }

    mysqli_stmt_bind_param($stmt, 'isi', $numeroSemana, $yearInforme, $sucursalInforme);
    mysqli_stmt_execute($stmt);
    $fila = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
    mysqli_stmt_close($stmt);

    $totalArticulos = (int) (isset($fila['total_articulos']) ? $fila['total_articulos'] : 0);
    $totalCoste = (float) (isset($fila['total_coste']) ? $fila['total_coste'] : 0);
    $totalBeneficio = round((float) (isset($fila['total_beneficio']) ? $fila['total_beneficio'] : 0), 2);

    $sqlUpdate = 'UPDATE informe_semanal SET total_articulos_vendidos = ?, total_coste_art_venta = ?, total_beneficio_ventas = ? WHERE id_informe = ?';
    $stmtUpdate = mysqli_prepare($conexion, $sqlUpdate);
    if ($stmtUpdate) {
        mysqli_stmt_bind_param($stmtUpdate, 'iddi', $totalArticulos, $totalCoste, $totalBeneficio, $idInforme);
        mysqli_stmt_execute($stmtUpdate);
        mysqli_stmt_close($stmtUpdate);
    }

    cron_informe_semanal_tarea_generado($idInforme, $sucursalInforme);
});
