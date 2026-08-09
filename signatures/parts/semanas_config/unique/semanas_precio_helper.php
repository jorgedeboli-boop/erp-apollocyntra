<?php

function calcular_media_porcentual_diferencia_semana($precioGramoOro, $precio24Mercado)
{
    $precioGramoOro = (float) $precioGramoOro;
    $precio24Mercado = (float) $precio24Mercado;

    if ($precioGramoOro <= 0 || $precio24Mercado <= 0) {
        return 0.0;
    }

    return round((($precioGramoOro - $precio24Mercado) / $precio24Mercado) * 100, 2);
}

function calcular_precio_gramo_desde_media($precio24Mercado, $mediaPorcentual)
{
    $precio24Mercado = (float) $precio24Mercado;
    $mediaPorcentual = (float) $mediaPorcentual;

    if ($precio24Mercado <= 0) {
        return 0.0;
    }

    return round($precio24Mercado * (1 + $mediaPorcentual / 100), 2);
}

/**
 * Media global de semanas manuales y aplicación a semanas automáticas.
 */
function semanas_aplicar_precio_gramo_automatico(mysqli $conexion)
{
    $sqlAvg = "
        SELECT AVG(media_porcentual_diferencia) AS media_global
        FROM listado_numero_semanas
        WHERE precio_gramo_oro > 0
          AND precio_24_mercado > 0
          AND calculo_precio_gramo = 'manual'
    ";

    $resultAvg = mysqli_query($conexion, $sqlAvg);
    if (!$resultAvg) {
        throw new Exception('Error al calcular la media porcentual global');
    }

    $rowAvg = mysqli_fetch_assoc($resultAvg);
    mysqli_free_result($resultAvg);

    $mediaGlobal = null;
    if ($rowAvg && $rowAvg['media_global'] !== null && $rowAvg['media_global'] !== '') {
        $mediaGlobal = round((float) $rowAvg['media_global'], 2);
    }

    if ($mediaGlobal !== null) {
        $stmtCfg = mysqli_prepare(
            $conexion,
            'UPDATE configuracion_general SET decimal_value = ? WHERE id_config = 6 LIMIT 1'
        );
        if (!$stmtCfg) {
            throw new Exception('Error al guardar la media en configuración general');
        }
        mysqli_stmt_bind_param($stmtCfg, 'd', $mediaGlobal);
        if (!mysqli_stmt_execute($stmtCfg)) {
            mysqli_stmt_close($stmtCfg);
            throw new Exception('No se pudo guardar la media en configuración general');
        }
        mysqli_stmt_close($stmtCfg);
    } else {
        $stmtCfgRead = mysqli_prepare(
            $conexion,
            'SELECT decimal_value FROM configuracion_general WHERE id_config = 6 LIMIT 1'
        );
        if ($stmtCfgRead) {
            mysqli_stmt_execute($stmtCfgRead);
            $resCfg = mysqli_stmt_get_result($stmtCfgRead);
            $rowCfg = mysqli_fetch_assoc($resCfg);
            mysqli_stmt_close($stmtCfgRead);
            if ($rowCfg && $rowCfg['decimal_value'] !== null && $rowCfg['decimal_value'] !== '') {
                $mediaGlobal = round((float) $rowCfg['decimal_value'], 2);
            }
        }
    }

    if ($mediaGlobal === null) {
        return null;
    }

    $sqlRows = "
        SELECT id_numero_semana, precio_24_mercado
        FROM listado_numero_semanas
        WHERE precio_24_mercado > 0
          AND calculo_precio_gramo <> 'proformas'
          AND (
            precio_gramo_oro IS NULL
            OR precio_gramo_oro <= 0
            OR calculo_precio_gramo = 'automatico'
          )
    ";

    $resultRows = mysqli_query($conexion, $sqlRows);
    if (!$resultRows) {
        throw new Exception('Error al obtener semanas para precio automático');
    }

    $stmtUpd = mysqli_prepare(
        $conexion,
        "UPDATE listado_numero_semanas
         SET precio_gramo_oro = ?,
             media_porcentual_diferencia = ?,
             calculo_precio_gramo = 'automatico'
         WHERE id_numero_semana = ?
         LIMIT 1"
    );
    if (!$stmtUpd) {
        mysqli_free_result($resultRows);
        throw new Exception('Error al preparar actualización de precio automático');
    }

    while ($row = mysqli_fetch_assoc($resultRows)) {
        $precio24Mercado = (float) $row['precio_24_mercado'];
        $precioGramoOro = calcular_precio_gramo_desde_media($precio24Mercado, $mediaGlobal);
        $mediaRow = calcular_media_porcentual_diferencia_semana($precioGramoOro, $precio24Mercado);
        $idNumeroSemana = (int) $row['id_numero_semana'];

        mysqli_stmt_bind_param($stmtUpd, 'ddi', $precioGramoOro, $mediaRow, $idNumeroSemana);
        if (!mysqli_stmt_execute($stmtUpd)) {
            mysqli_stmt_close($stmtUpd);
            mysqli_free_result($resultRows);
            throw new Exception('No se pudo actualizar el precio automático de la semana');
        }
    }

    mysqli_stmt_close($stmtUpd);
    mysqli_free_result($resultRows);

    return $mediaGlobal;
}

/**
 * Guarda precios manuales de una semana y recalcula las automáticas.
 */
function semanas_guardar_precios_semana_manual(mysqli $conexion, $idNumeroSemana, $precio24Mercado, $precioGramoOro)
{
    $idNumeroSemana = (int) $idNumeroSemana;
    $precio24Mercado = (float) $precio24Mercado;
    $precioGramoOro = (float) $precioGramoOro;

    if ($idNumeroSemana <= 0) {
        throw new Exception('Semana no válida');
    }

    if ($precio24Mercado < 0 || $precioGramoOro < 0) {
        throw new Exception('Los precios no pueden ser negativos');
    }

    $stmtCheck = mysqli_prepare(
        $conexion,
        'SELECT id_numero_semana FROM listado_numero_semanas WHERE id_numero_semana = ? LIMIT 1'
    );
    if (!$stmtCheck) {
        throw new Exception('Error al consultar la semana');
    }
    mysqli_stmt_bind_param($stmtCheck, 'i', $idNumeroSemana);
    mysqli_stmt_execute($stmtCheck);
    $resCheck = mysqli_stmt_get_result($stmtCheck);
    $exists = mysqli_fetch_assoc($resCheck);
    mysqli_stmt_close($stmtCheck);

    if (!$exists) {
        throw new Exception('Registro no encontrado');
    }

    $mediaPorcentual = calcular_media_porcentual_diferencia_semana($precioGramoOro, $precio24Mercado);
    $calculoPrecioGramo = 'manual';

    $stmtUpd = mysqli_prepare(
        $conexion,
        "UPDATE listado_numero_semanas
         SET precio_24_mercado = ?,
             precio_gramo_oro = ?,
             media_porcentual_diferencia = ?,
             calculo_precio_gramo = ?
         WHERE id_numero_semana = ?
         LIMIT 1"
    );
    if (!$stmtUpd) {
        throw new Exception('Error al preparar la actualización');
    }

    mysqli_stmt_bind_param(
        $stmtUpd,
        'dddsi',
        $precio24Mercado,
        $precioGramoOro,
        $mediaPorcentual,
        $calculoPrecioGramo,
        $idNumeroSemana
    );

    if (!mysqli_stmt_execute($stmtUpd)) {
        mysqli_stmt_close($stmtUpd);
        throw new Exception('No se pudo actualizar la semana');
    }
    mysqli_stmt_close($stmtUpd);

    $mediaGlobal = semanas_aplicar_precio_gramo_automatico($conexion);

    return [
        'id_numero_semana' => $idNumeroSemana,
        'precio_24_mercado' => $precio24Mercado,
        'precio_gramo_oro' => $precioGramoOro,
        'media_porcentual_diferencia' => $mediaPorcentual,
        'calculo_precio_gramo' => $calculoPrecioGramo,
        'media_global_automatico' => $mediaGlobal,
    ];
}
