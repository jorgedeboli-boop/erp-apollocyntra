<?php

/**
 * Calcula precio_coste_venta y rentabilidad_venta en rel_articulos_estados.
 */

cron_informe_semanal_recorrer_abiertos('informes-semanal-calculo-coste-articulos-venta', function ($informe, $conexion) {
    $idInforme = (int) $informe['id_informe'];
    $sucursalInforme = (int) $informe['sucursal_informe'];
    $numeroSemana = (int) $informe['numero_semana'];
    $yearInforme = (string) $informe['year_informe'];

    $estados = array('Stock', 'Vendido');

    foreach ($estados as $estadoArticulo) {
        $sql = "SELECT rel_id_articulo_venta, precio_venta, total_pagado_fundicion
                FROM rel_articulos_estados
                WHERE rel_numero_semana = ?
                  AND estado_articulo = ?
                  AND year_rel = ?
                  AND rel_id_sucursal_venta = ?
                ORDER BY rel_id_articulo_venta ASC";
        $stmt = mysqli_prepare($conexion, $sql);
        if (!$stmt) {
            continue;
        }

        mysqli_stmt_bind_param($stmt, 'issi', $numeroSemana, $estadoArticulo, $yearInforme, $sucursalInforme);
        mysqli_stmt_execute($stmt);
        $resultado = mysqli_stmt_get_result($stmt);

        $sqlUpdate = 'UPDATE rel_articulos_estados SET precio_coste_venta = ?, rentabilidad_venta = ? WHERE rel_id_articulo_venta = ?';
        $stmtUpdate = mysqli_prepare($conexion, $sqlUpdate);

        while ($articulo = $resultado ? mysqli_fetch_assoc($resultado) : false) {
            $relId = (int) $articulo['rel_id_articulo_venta'];
            $precioVenta = (float) $articulo['precio_venta'];
            $precioCoste = (float) $articulo['total_pagado_fundicion'];
            $rentabilidad = $precioVenta - $precioCoste;

            if ($stmtUpdate) {
                mysqli_stmt_bind_param($stmtUpdate, 'ddi', $precioCoste, $rentabilidad, $relId);
                mysqli_stmt_execute($stmtUpdate);
            }
        }

        if ($stmtUpdate) {
            mysqli_stmt_close($stmtUpdate);
        }
        mysqli_stmt_close($stmt);
    }

    cron_informe_semanal_tarea_generado($idInforme, $sucursalInforme);
});
