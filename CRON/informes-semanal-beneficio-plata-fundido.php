<?php

cron_informe_semanal_recorrer_abiertos('informes-semanal-beneficio-plata-fundido', function ($informe, $conexion) {
    $idInforme = (int) $informe['id_informe'];
    $sucursalInforme = (int) $informe['sucursal_informe'];
    $numeroSemana = (int) $informe['numero_semana'];
    $yearInforme = (string) $informe['year_informe'];

    $sql = "SELECT SUM(beneficio_fundicion) AS total
            FROM items_proforma
            WHERE type_item = 'Plata'
              AND rel_semanal_item_proforma = ?
              AND year_semanal_item_proforma = ?
              AND sucursal_item = ?";
    $stmt = mysqli_prepare($conexion, $sql);
    if (!$stmt) {
        return;
    }

    mysqli_stmt_bind_param($stmt, 'isi', $numeroSemana, $yearInforme, $sucursalInforme);
    mysqli_stmt_execute($stmt);
    $fila = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
    mysqli_stmt_close($stmt);

    $total = round((float) (isset($fila['total']) ? $fila['total'] : 0), 2);

    $sqlUpdate = 'UPDATE informe_semanal SET beneficio_fundicion_plata = ? WHERE id_informe = ?';
    $stmtUpdate = mysqli_prepare($conexion, $sqlUpdate);
    if ($stmtUpdate) {
        mysqli_stmt_bind_param($stmtUpdate, 'di', $total, $idInforme);
        mysqli_stmt_execute($stmtUpdate);
        mysqli_stmt_close($stmtUpdate);
    }

    cron_informe_semanal_tarea_generado($idInforme, $sucursalInforme);
});
