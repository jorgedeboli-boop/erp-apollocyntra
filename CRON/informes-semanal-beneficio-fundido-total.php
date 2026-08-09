<?php

cron_informe_semanal_recorrer_abiertos('informes-semanal-beneficio-fundido-total', function ($informe, $conexion) {
    $idInforme = (int) $informe['id_informe'];
    $sucursalInforme = (int) $informe['sucursal_informe'];
    $numeroProforma = isset($informe['numero_proforma']) ? (int) $informe['numero_proforma'] : 0;

    if ($numeroProforma <= 0) {
        return;
    }

    $sql = "SELECT
                SUM(gramos_item_proforma) AS total_gramos_proforma,
                SUM(cantidad_articulos_enviados) AS cantidad_articulos_enviados,
                SUM(total_final_pagado_fundicion) AS total_final_pagado_fundicion,
                SUM(importe_item_proforma) AS importe_item_proforma,
                SUM(pagado_fundicion_gramo_final) AS pagado_fundicion_gramo_final,
                SUM(beneficio_fundicion) AS beneficio_fundicion
            FROM items_proforma
            WHERE rel_proforma_id = ?
              AND sucursal_item = ?";
    $stmt = mysqli_prepare($conexion, $sql);
    if (!$stmt) {
        return;
    }

    mysqli_stmt_bind_param($stmt, 'ii', $numeroProforma, $sucursalInforme);
    mysqli_stmt_execute($stmt);
    $fila = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
    mysqli_stmt_close($stmt);

    $totalGramosProforma = (float) (isset($fila['total_gramos_proforma']) ? $fila['total_gramos_proforma'] : 0);
    $cantidadArticulos = (float) (isset($fila['cantidad_articulos_enviados']) ? $fila['cantidad_articulos_enviados'] : 0);
    $importeCobrado = (float) (isset($fila['total_final_pagado_fundicion']) ? $fila['total_final_pagado_fundicion'] : 0);
    $totalPagadoProforma = (float) (isset($fila['importe_item_proforma']) ? $fila['importe_item_proforma'] : 0);
    $totalGramosEnviado = (float) (isset($fila['pagado_fundicion_gramo_final']) ? $fila['pagado_fundicion_gramo_final'] : 0);
    $beneficioFundicion = (float) (isset($fila['beneficio_fundicion']) ? $fila['beneficio_fundicion'] : 0);

    $sqlUpdate = 'UPDATE informe_semanal SET
        total_gramos_proforma = ?,
        total_articulos_enviado_fundicion = ?,
        importe_cobrado_funcidion = ?,
        total_pagado_proforma = ?,
        total_gramos_enviado_fundicion = ?,
        beneficio_fundicion = ?
        WHERE id_informe = ?';
    $stmtUpdate = mysqli_prepare($conexion, $sqlUpdate);
    if ($stmtUpdate) {
        mysqli_stmt_bind_param(
            $stmtUpdate,
            'ddddddi',
            $totalGramosProforma,
            $cantidadArticulos,
            $importeCobrado,
            $totalPagadoProforma,
            $totalGramosEnviado,
            $beneficioFundicion,
            $idInforme
        );
        mysqli_stmt_execute($stmtUpdate);
        mysqli_stmt_close($stmtUpdate);
    }

    cron_informe_semanal_tarea_generado($idInforme, $sucursalInforme);
});
