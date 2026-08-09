<?php

cron_informe_semanal_recorrer_abiertos('informe-semanal-calcular-beneficio', function ($informe, $conexion) {
    $idInforme = (int) $informe['id_informe'];
    $sucursalInforme = (int) $informe['sucursal_informe'];

    $sql = 'SELECT total_beneficio_ventas, beneficio_fundicion, beneficios_empenios, total_gastos, ajustes_de_lotes
            FROM informe_semanal
            WHERE id_informe = ?
            LIMIT 1';
    $stmt = mysqli_prepare($conexion, $sql);
    if (!$stmt) {
        return;
    }

    mysqli_stmt_bind_param($stmt, 'i', $idInforme);
    mysqli_stmt_execute($stmt);
    $fila = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
    mysqli_stmt_close($stmt);

    if (!$fila) {
        return;
    }

    $beneficioVentas = (float) (isset($fila['total_beneficio_ventas']) ? $fila['total_beneficio_ventas'] : 0);
    $beneficioFundicion = (float) (isset($fila['beneficio_fundicion']) ? $fila['beneficio_fundicion'] : 0);
    $beneficiosEmpenios = (float) (isset($fila['beneficios_empenios']) ? $fila['beneficios_empenios'] : 0);
    $totalGastos = (float) (isset($fila['total_gastos']) ? $fila['total_gastos'] : 0);
    $ajustes_de_lotes = (float) (isset($fila['ajustes_de_lotes']) ? $fila['ajustes_de_lotes'] : 0);

    $masAlta = max($beneficioVentas, $beneficioFundicion, $beneficiosEmpenios);
    $masBaja = min($beneficioVentas, $beneficioFundicion, $beneficiosEmpenios);

    if ($beneficioVentas == $masAlta) {
        $sectorMasBeneficio = 'ventas';
    } elseif ($beneficioFundicion == $masAlta) {
        $sectorMasBeneficio = 'compras';
    } else {
        $sectorMasBeneficio = 'empenyos';
    }

    if ($beneficioVentas == $masBaja) {
        $sectorMenosBeneficio = 'ventas';
    } elseif ($beneficioFundicion == $masBaja) {
        $sectorMenosBeneficio = 'compras';
    } else {
        $sectorMenosBeneficio = 'empenyos';
    }

    $benficios_total = $beneficioVentas + $beneficioFundicion + $beneficiosEmpenios;
    $salidas_total = $totalGastos + $ajustes_de_lotes;

    $beneficioTienda = round($benficios_total - $salidas_total, 2);

    $sqlUpdate = 'UPDATE informe_semanal SET beneficio_tienda = ?, sector_mas_beneficio = ?, sector_menos_beneficio = ? WHERE id_informe = ?';
    $stmtUpdate = mysqli_prepare($conexion, $sqlUpdate);
    if ($stmtUpdate) {
        mysqli_stmt_bind_param($stmtUpdate, 'dssi', $beneficioTienda, $sectorMasBeneficio, $sectorMenosBeneficio, $idInforme);
        mysqli_stmt_execute($stmtUpdate);
        mysqli_stmt_close($stmtUpdate);
    }

    cron_informe_semanal_tarea_generado($idInforme, $sucursalInforme);
});
