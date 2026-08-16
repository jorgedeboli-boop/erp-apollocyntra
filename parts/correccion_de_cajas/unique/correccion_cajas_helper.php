<?php

/**
 * Helpers para corrección de aperturas/cierres de caja.
 * Compatible con PHP 7.0+.
 */

function correccion_cajas_tabla_movimientos($idTabla)
{
    return 'movimientos_de_caja_' . (int) $idTabla;
}

function correccion_cajas_listar_ids_tablas(mysqli $conexion)
{
    $ids = [];
    $result = mysqli_query($conexion, "SHOW TABLES LIKE 'movimientos_de_caja_%'");
    if ($result) {
        while ($row = mysqli_fetch_row($result)) {
            if (preg_match('/^movimientos_de_caja_(\d+)$/', $row[0], $m)) {
                $ids[] = (int) $m[1];
            }
        }
    }
    sort($ids);
    return $ids;
}

function correccion_cajas_tabla_existe(mysqli $conexion, $tabla)
{
    $tablaEsc = mysqli_real_escape_string($conexion, $tabla);
    $result = mysqli_query($conexion, "SHOW TABLES LIKE '{$tablaEsc}'");
    return $result && mysqli_num_rows($result) > 0;
}

function correccion_cajas_desplazar_ids(mysqli $conexion, $tabla, $desdeId)
{
    $desdeId = (int) $desdeId;
    if ($desdeId < 1) {
        return;
    }

    $query = "UPDATE `{$tabla}` SET id_movimientos = id_movimientos + 1 WHERE id_movimientos >= ? ORDER BY id_movimientos DESC";
    $stmt = mysqli_prepare($conexion, $query);
    if (!$stmt) {
        throw new Exception('Error al desplazar IDs: ' . mysqli_error($conexion));
    }
    mysqli_stmt_bind_param($stmt, 'i', $desdeId);
    if (!mysqli_stmt_execute($stmt)) {
        $error = mysqli_stmt_error($stmt);
        mysqli_stmt_close($stmt);
        throw new Exception('Error al desplazar IDs: ' . $error);
    }
    mysqli_stmt_close($stmt);
}

function correccion_cajas_actualizar_autoincrement(mysqli $conexion, $tabla)
{
    $query = "SELECT COALESCE(MAX(id_movimientos), 0) + 1 AS next_id FROM `{$tabla}`";
    $result = mysqli_query($conexion, $query);
    if (!$result) {
        return;
    }
    $row = mysqli_fetch_assoc($result);
    $nextId = isset($row['next_id']) ? (int) $row['next_id'] : 1;
    mysqli_query($conexion, "ALTER TABLE `{$tabla}` AUTO_INCREMENT = {$nextId}");
}

function correccion_cajas_obtener_id_cierre_dia_anterior(mysqli $conexion, $tabla, $fecha)
{
    $query = "SELECT id_movimientos
              FROM `{$tabla}`
              WHERE cierre_caja = 'true'
              AND fecha_apunte < ?
              ORDER BY id_movimientos DESC
              LIMIT 1";
    $stmt = mysqli_prepare($conexion, $query);
    if (!$stmt) {
        throw new Exception('Error al buscar cierre anterior: ' . mysqli_error($conexion));
    }
    mysqli_stmt_bind_param($stmt, 's', $fecha);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $row = mysqli_fetch_assoc($result);
    mysqli_stmt_close($stmt);

    return $row ? (int) $row['id_movimientos'] : null;
}

function correccion_cajas_obtener_importe_cierre_dia_anterior(mysqli $conexion, $tabla, $fecha)
{
    $fechaAnterior = date('Y-m-d', strtotime($fecha . ' -1 day'));

    $queryDiaAnterior = "SELECT salida
                         FROM `{$tabla}`
                         WHERE cierre_caja = 'true'
                           AND fecha_apunte = ?
                         ORDER BY id_movimientos DESC
                         LIMIT 1";
    $stmtDiaAnterior = mysqli_prepare($conexion, $queryDiaAnterior);
    if (!$stmtDiaAnterior) {
        throw new Exception('Error al buscar importe de cierre anterior: ' . mysqli_error($conexion));
    }
    mysqli_stmt_bind_param($stmtDiaAnterior, 's', $fechaAnterior);
    mysqli_stmt_execute($stmtDiaAnterior);
    $resultDiaAnterior = mysqli_stmt_get_result($stmtDiaAnterior);
    $rowDiaAnterior = mysqli_fetch_assoc($resultDiaAnterior);
    mysqli_stmt_close($stmtDiaAnterior);

    if ($rowDiaAnterior) {
        return (float) $rowDiaAnterior['salida'];
    }

    $query = "SELECT salida
              FROM `{$tabla}`
              WHERE cierre_caja = 'true'
              AND fecha_apunte < ?
              ORDER BY id_movimientos DESC
              LIMIT 1";
    $stmt = mysqli_prepare($conexion, $query);
    if (!$stmt) {
        throw new Exception('Error al buscar importe de cierre anterior: ' . mysqli_error($conexion));
    }
    mysqli_stmt_bind_param($stmt, 's', $fecha);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $row = mysqli_fetch_assoc($result);
    mysqli_stmt_close($stmt);

    return $row ? (float) $row['salida'] : 0.0;
}

function correccion_cajas_obtener_ultimo_id_del_dia(mysqli $conexion, $tabla, $fecha)
{
    $query = "SELECT COALESCE(MAX(id_movimientos), 0) AS ultimo_id
              FROM `{$tabla}`
              WHERE fecha_apunte = ?";
    $stmt = mysqli_prepare($conexion, $query);
    if (!$stmt) {
        throw new Exception('Error al buscar último movimiento del día: ' . mysqli_error($conexion));
    }
    mysqli_stmt_bind_param($stmt, 's', $fecha);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $row = mysqli_fetch_assoc($result);
    mysqli_stmt_close($stmt);

    return isset($row['ultimo_id']) ? (int) $row['ultimo_id'] : 0;
}

function correccion_cajas_tiene_apertura_dia(mysqli $conexion, $tabla, $fecha)
{
    $query = "SELECT COUNT(*) AS total
              FROM `{$tabla}`
              WHERE TRIM(grupos) = 'CAJA INICIO'
              AND fecha_apunte = ?";
    $stmt = mysqli_prepare($conexion, $query);
    if (!$stmt) {
        return false;
    }
    mysqli_stmt_bind_param($stmt, 's', $fecha);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $row = mysqli_fetch_assoc($result);
    mysqli_stmt_close($stmt);

    return isset($row['total']) && (int) $row['total'] > 0;
}

function correccion_cajas_tiene_cierre_dia(mysqli $conexion, $tabla, $fecha)
{
    $query = "SELECT COUNT(*) AS total
              FROM `{$tabla}`
              WHERE cierre_caja = 'true'
              AND fecha_apunte = ?";
    $stmt = mysqli_prepare($conexion, $query);
    if (!$stmt) {
        return false;
    }
    mysqli_stmt_bind_param($stmt, 's', $fecha);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $row = mysqli_fetch_assoc($result);
    mysqli_stmt_close($stmt);

    return isset($row['total']) && (int) $row['total'] > 0;
}

function correccion_cajas_calcular_total_dia(mysqli $conexion, $tabla, $fecha)
{
    $query = "SELECT
                COALESCE(SUM(entrada), 0) AS total_entradas,
                COALESCE(SUM(salida), 0) AS total_salidas
              FROM `{$tabla}`
              WHERE fecha_apunte = ?";
    $stmt = mysqli_prepare($conexion, $query);
    if (!$stmt) {
        return [
            'entradas' => 0.0,
            'salidas' => 0.0,
            'total' => 0.0,
        ];
    }
    mysqli_stmt_bind_param($stmt, 's', $fecha);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $row = mysqli_fetch_assoc($result);
    mysqli_stmt_close($stmt);

    $entradas = isset($row['total_entradas']) ? (float) $row['total_entradas'] : 0.0;
    $salidas = isset($row['total_salidas']) ? (float) $row['total_salidas'] : 0.0;

    return [
        'entradas' => $entradas,
        'salidas' => $salidas,
        'total' => $entradas - $salidas,
    ];
}

function correccion_cajas_obtener_id_insercion_tras_apertura(mysqli $conexion, $tabla, $fecha)
{
    $query = "SELECT id_movimientos
              FROM `{$tabla}`
              WHERE fecha_apunte = ?
                AND TRIM(grupos) = 'CAJA INICIO'
              ORDER BY id_movimientos ASC
              LIMIT 1";
    $stmt = mysqli_prepare($conexion, $query);
    if (!$stmt) {
        throw new Exception('Error al localizar apertura de caja');
    }
    mysqli_stmt_bind_param($stmt, 's', $fecha);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $row = mysqli_fetch_assoc($result);
    mysqli_stmt_close($stmt);

    if ($row) {
        return (int) $row['id_movimientos'] + 1;
    }

    $queryMin = "SELECT MIN(id_movimientos) AS min_id FROM `{$tabla}` WHERE fecha_apunte = ?";
    $stmtMin = mysqli_prepare($conexion, $queryMin);
    if (!$stmtMin) {
        throw new Exception('Error al localizar movimientos del día');
    }
    mysqli_stmt_bind_param($stmtMin, 's', $fecha);
    mysqli_stmt_execute($stmtMin);
    $rowMin = mysqli_fetch_assoc(mysqli_stmt_get_result($stmtMin));
    mysqli_stmt_close($stmtMin);

    $minId = isset($rowMin['min_id']) ? (int) $rowMin['min_id'] : 0;
    if ($minId > 0) {
        return $minId;
    }

    return correccion_cajas_obtener_ultimo_id_del_dia($conexion, $tabla, $fecha) + 1;
}

function correccion_cajas_obtener_hora_insercion_tras_apertura(mysqli $conexion, $tabla, $fecha)
{
    $query = "SELECT hora_de_apunte
              FROM `{$tabla}`
              WHERE fecha_apunte = ?
                AND TRIM(grupos) = 'CAJA INICIO'
              ORDER BY id_movimientos ASC
              LIMIT 1";
    $stmt = mysqli_prepare($conexion, $query);
    if (!$stmt) {
        return '09:00:00';
    }
    mysqli_stmt_bind_param($stmt, 's', $fecha);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $row = mysqli_fetch_assoc($result);
    mysqli_stmt_close($stmt);

    if (empty($row['hora_de_apunte'])) {
        return '09:00:00';
    }

    $ts = strtotime($fecha . ' ' . $row['hora_de_apunte']);
    if ($ts === false) {
        return '09:00:00';
    }

    return date('H:i:s', $ts + 60);
}

function correccion_cajas_insertar_movimiento(
    mysqli $conexion,
    $tabla,
    $idMovimiento,
    $grupos,
    $concepto,
    $entrada,
    $salida,
    $usuario,
    $fecha,
    $hora,
    $cierreCaja
) {
    correccion_cajas_desplazar_ids($conexion, $tabla, $idMovimiento);

    $query = "INSERT INTO `{$tabla}` (
        id_movimientos,
        grupos,
        concepto,
        entrada,
        salida,
        usuario,
        fecha_apunte,
        hora_de_apunte,
        acumulado,
        acumulado_diario,
        apunte_cierre_caja,
        cierre_caja
    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, 0, 0, 0, ?)";

    $stmt = mysqli_prepare($conexion, $query);
    if (!$stmt) {
        throw new Exception('Error al preparar inserción: ' . mysqli_error($conexion));
    }

    mysqli_stmt_bind_param(
        $stmt,
        'issddisss',
        $idMovimiento,
        $grupos,
        $concepto,
        $entrada,
        $salida,
        $usuario,
        $fecha,
        $hora,
        $cierreCaja
    );

    if (!mysqli_stmt_execute($stmt)) {
        $error = mysqli_stmt_error($stmt);
        mysqli_stmt_close($stmt);
        throw new Exception('Error al insertar movimiento: ' . $error);
    }

    mysqli_stmt_close($stmt);
    correccion_cajas_actualizar_autoincrement($conexion, $tabla);
}

function correccion_cajas_es_apertura($grupos)
{
    return trim((string) $grupos) === 'CAJA INICIO';
}

function correccion_cajas_es_cierre_registro($cierreCaja)
{
    return (string) $cierreCaja === 'true';
}

function correccion_cajas_obtener_movimientos_dia(mysqli $conexion, $tabla, $fecha)
{
    $query = "SELECT id_movimientos, grupos, cierre_caja
              FROM `{$tabla}`
              WHERE fecha_apunte = ?
              ORDER BY id_movimientos ASC";
    $stmt = mysqli_prepare($conexion, $query);
    if (!$stmt) {
        return [];
    }
    mysqli_stmt_bind_param($stmt, 's', $fecha);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $movimientos = [];
    while ($row = mysqli_fetch_assoc($result)) {
        $movimientos[] = [
            'id_movimientos' => (int) $row['id_movimientos'],
            'grupos' => $row['grupos'],
            'cierre_caja' => $row['cierre_caja'],
        ];
    }
    mysqli_stmt_close($stmt);

    return $movimientos;
}

function correccion_cajas_analizar_dia(mysqli $conexion, $tabla, $fecha)
{
    $movimientos = correccion_cajas_obtener_movimientos_dia($conexion, $tabla, $fecha);
    $total = count($movimientos);

    $analisis = [
        'falta_apertura' => false,
        'falta_cierre' => false,
        'apertura_id_erroneo' => false,
        'cierre_id_erroneo' => false,
        'tiene_conflicto' => false,
        'min_id' => null,
        'max_id' => null,
        'id_apertura' => null,
        'id_cierre' => null,
    ];

    if ($total === 0) {
        $analisis['falta_apertura'] = true;
        $analisis['falta_cierre'] = true;
        $analisis['tiene_conflicto'] = true;
        return $analisis;
    }

    $minId = (int) $movimientos[0]['id_movimientos'];
    $maxId = (int) $movimientos[$total - 1]['id_movimientos'];
    $analisis['min_id'] = $minId;
    $analisis['max_id'] = $maxId;

    $tieneApertura = false;
    $tieneCierre = false;
    $aperturaEnMin = false;
    $cierreEnMax = false;

    foreach ($movimientos as $mov) {
        $id = (int) $mov['id_movimientos'];
        $esApertura = correccion_cajas_es_apertura($mov['grupos']);
        $esCierre = correccion_cajas_es_cierre_registro($mov['cierre_caja']);

        if ($esApertura) {
            $tieneApertura = true;
            $analisis['id_apertura'] = $id;
            if ($id === $minId) {
                $aperturaEnMin = true;
            }
        }
        if ($esCierre) {
            $tieneCierre = true;
            $analisis['id_cierre'] = $id;
        }
        if ($id === $maxId && $esCierre) {
            $cierreEnMax = true;
        }
    }

    $analisis['falta_apertura'] = !$tieneApertura;
    $analisis['falta_cierre'] = !$tieneCierre;
    $analisis['apertura_id_erroneo'] = $tieneApertura && !$aperturaEnMin;
    $analisis['cierre_id_erroneo'] = $tieneCierre && !$cierreEnMax;

    $balanceCierre = correccion_cajas_comprobar_cierre_no_coincide($conexion, $tabla, $fecha, $analisis);
    $analisis['cierre_no_coincide'] = !empty($balanceCierre['cierre_no_coincide']);
    $analisis['importe_apertura'] = $balanceCierre['importe_apertura'];
    $analisis['importe_cierre'] = $balanceCierre['importe_cierre'];
    $analisis['importe_cierre_esperado'] = $balanceCierre['importe_cierre_esperado'];

    $analisis['tiene_conflicto'] = $analisis['falta_apertura']
        || $analisis['falta_cierre']
        || $analisis['apertura_id_erroneo']
        || $analisis['cierre_id_erroneo']
        || $analisis['cierre_no_coincide'];

    return $analisis;
}

function correccion_cajas_comprobar_cierre_no_coincide(mysqli $conexion, $tabla, $fecha, array $analisis)
{
    $resultado = [
        'cierre_no_coincide' => false,
        'importe_apertura' => null,
        'importe_cierre' => null,
        'importe_cierre_esperado' => null,
    ];

    if (!empty($analisis['falta_apertura'])
        || !empty($analisis['falta_cierre'])
        || !empty($analisis['apertura_id_erroneo'])
        || !empty($analisis['cierre_id_erroneo'])) {
        return $resultado;
    }

    $query = "SELECT grupos, entrada, salida, cierre_caja
              FROM `{$tabla}`
              WHERE fecha_apunte = ?
              ORDER BY id_movimientos ASC";
    $stmt = mysqli_prepare($conexion, $query);
    if (!$stmt) {
        return $resultado;
    }
    mysqli_stmt_bind_param($stmt, 's', $fecha);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);

    $importeApertura = 0.0;
    $importeCierre = 0.0;
    $entradasMovimientos = 0.0;
    $salidasMovimientos = 0.0;
    $tieneApertura = false;
    $tieneCierre = false;

    while ($row = mysqli_fetch_assoc($result)) {
        $entrada = isset($row['entrada']) ? (float) $row['entrada'] : 0.0;
        $salida = isset($row['salida']) ? (float) $row['salida'] : 0.0;
        $esApertura = correccion_cajas_es_apertura($row['grupos']);
        $esCierre = correccion_cajas_es_cierre_registro($row['cierre_caja']);

        if ($esApertura) {
            $importeApertura = $entrada;
            $tieneApertura = true;
            continue;
        }
        if ($esCierre) {
            $importeCierre = $salida;
            $tieneCierre = true;
            continue;
        }

        $entradasMovimientos += $entrada;
        $salidasMovimientos += $salida;
    }
    mysqli_stmt_close($stmt);

    if (!$tieneApertura || !$tieneCierre) {
        return $resultado;
    }

    $importeCierreEsperado = $importeApertura + $entradasMovimientos - $salidasMovimientos;
    $resultado['importe_apertura'] = round($importeApertura, 2);
    $resultado['importe_cierre'] = round($importeCierre, 2);
    $resultado['importe_cierre_esperado'] = round($importeCierreEsperado, 2);
    $resultado['cierre_no_coincide'] = abs($importeCierreEsperado - $importeCierre) >= 0.005;

    return $resultado;
}

function correccion_cajas_construir_mensaje_conflicto(array $analisis)
{
    $mensajes = [];

    if (!empty($analisis['falta_apertura'])) {
        $mensajes[] = 'falta apertura';
    } elseif (!empty($analisis['apertura_id_erroneo'])) {
        $mensajes[] = 'apertura con id erróneo';
    }

    if (!empty($analisis['falta_cierre'])) {
        $mensajes[] = 'falta cierre';
    } elseif (!empty($analisis['cierre_id_erroneo'])) {
        $mensajes[] = 'cierre con id erróneo';
    }

    if (!empty($analisis['cierre_no_coincide'])) {
        $mensajes[] = 'Caja final no coincide con la diferencia en entradas y salidas';
    }

    if (empty($mensajes)) {
        return '';
    }

    return 'Caja en conflicto: ' . implode(' y ', $mensajes);
}

function correccion_cajas_item_listado_conflicto(
    mysqli $conexion,
    $tabla,
    $idTabla,
    $fecha,
    array $analisis,
    $sufijoMensaje = ''
) {
    $conflicto = correccion_cajas_construir_mensaje_conflicto($analisis) . $sufijoMensaje;
    $importeAperturaSugerido = null;

    if (!empty($analisis['falta_apertura'])) {
        $importeAperturaSugerido = correccion_cajas_obtener_importe_cierre_dia_anterior($conexion, $tabla, $fecha);
        if ($importeAperturaSugerido > 0) {
            $conflicto .= '. Apertura sugerida: '
                . number_format($importeAperturaSugerido, 2, ',', '.') . ' € (cierre día anterior)';
        }
    }

    return [
        'id_tabla' => (int) $idTabla,
        'fecha' => $fecha,
        'fecha_texto' => date('d-m-Y', strtotime($fecha)),
        'conflicto' => $conflicto,
        'falta_apertura' => !empty($analisis['falta_apertura']),
        'falta_cierre' => !empty($analisis['falta_cierre']),
        'apertura_id_erroneo' => !empty($analisis['apertura_id_erroneo']),
        'cierre_id_erroneo' => !empty($analisis['cierre_id_erroneo']),
        'cierre_no_coincide' => !empty($analisis['cierre_no_coincide']),
        'importe_apertura_sugerido' => $importeAperturaSugerido,
    ];
}

function correccion_cajas_reordenar_movimientos_dia(mysqli $conexion, $tabla, $fecha, array $ordenIds)
{
    $ordenIds = array_values(array_unique(array_map('intval', $ordenIds)));
    if (empty($ordenIds)) {
        throw new Exception('No se recibió el orden de movimientos');
    }

    $movimientos = correccion_cajas_obtener_movimientos_dia($conexion, $tabla, $fecha);
    $currentIds = [];
    foreach ($movimientos as $mov) {
        $currentIds[] = (int) $mov['id_movimientos'];
    }

    if (count($currentIds) !== count($ordenIds)) {
        throw new Exception('El número de movimientos no coincide');
    }

    $currentSorted = $currentIds;
    $ordenSorted = $ordenIds;
    sort($currentSorted);
    sort($ordenSorted);

    if ($currentSorted !== $ordenSorted) {
        throw new Exception('Los movimientos enviados no corresponden al día');
    }

    if ($currentIds === $ordenIds) {
        return;
    }

    $queryNegar = "UPDATE `{$tabla}` SET id_movimientos = -id_movimientos WHERE fecha_apunte = ?";
    $stmtNegar = mysqli_prepare($conexion, $queryNegar);
    if (!$stmtNegar) {
        throw new Exception('Error al preparar reordenación: ' . mysqli_error($conexion));
    }
    mysqli_stmt_bind_param($stmtNegar, 's', $fecha);
    if (!mysqli_stmt_execute($stmtNegar)) {
        $error = mysqli_stmt_error($stmtNegar);
        mysqli_stmt_close($stmtNegar);
        throw new Exception('Error al reordenar movimientos: ' . $error);
    }
    mysqli_stmt_close($stmtNegar);

    $queryAsignar = "UPDATE `{$tabla}`
                     SET id_movimientos = ?
                     WHERE id_movimientos = ?
                     AND fecha_apunte = ?";
    $stmtAsignar = mysqli_prepare($conexion, $queryAsignar);
    if (!$stmtAsignar) {
        throw new Exception('Error al preparar asignación de ids: ' . mysqli_error($conexion));
    }

    for ($i = 0; $i < count($ordenIds); $i++) {
        $oldId = -1 * (int) $ordenIds[$i];
        $newId = (int) $currentIds[$i];
        mysqli_stmt_bind_param($stmtAsignar, 'iis', $newId, $oldId, $fecha);
        if (!mysqli_stmt_execute($stmtAsignar)) {
            $error = mysqli_stmt_error($stmtAsignar);
            mysqli_stmt_close($stmtAsignar);
            throw new Exception('Error al asignar ids: ' . $error);
        }
    }

    mysqli_stmt_close($stmtAsignar);
    correccion_cajas_actualizar_autoincrement($conexion, $tabla);
}

function correccion_cajas_detectar_conflictos_tabla(mysqli $conexion, $idTabla)
{
    $idTabla = (int) $idTabla;
    $tabla = correccion_cajas_tabla_movimientos($idTabla);
    if (!correccion_cajas_tabla_existe($conexion, $tabla)) {
        return [];
    }

    $queryFechas = "SELECT DISTINCT fecha_apunte
                    FROM `{$tabla}`
                    WHERE fecha_apunte < CURDATE()
                      AND fecha_apunte >= DATE_SUB(CURDATE(), INTERVAL 2 MONTH)
                    ORDER BY fecha_apunte ASC";
    $resultFechas = mysqli_query($conexion, $queryFechas);
    if (!$resultFechas) {
        return [];
    }

    while ($rowFecha = mysqli_fetch_assoc($resultFechas)) {
        $fecha = $rowFecha['fecha_apunte'];
        $analisis = correccion_cajas_analizar_dia($conexion, $tabla, $fecha);
        if (empty($analisis['tiene_conflicto'])) {
            continue;
        }

        return [correccion_cajas_item_listado_conflicto(
            $conexion,
            $tabla,
            $idTabla,
            $fecha,
            $analisis
        )];
    }

    $hoy = date('Y-m-d');
    if (!correccion_cajas_tiene_apertura_dia($conexion, $tabla, $hoy)) {
        return [correccion_cajas_item_listado_conflicto(
            $conexion,
            $tabla,
            $idTabla,
            $hoy,
            [
                'falta_apertura' => true,
                'falta_cierre' => false,
                'apertura_id_erroneo' => false,
                'cierre_id_erroneo' => false,
                'cierre_no_coincide' => false,
            ],
            ' (hoy)'
        )];
    }

    return [];
}
