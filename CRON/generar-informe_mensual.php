<?php

/**
 * Genera el registro base de informe_mensual por sucursal habilitada (si no existe).
 */

if (empty($id_numero_mes)) {
    cron_linea('>> Paso: generar-informe_mensual | omitido (no hay mes que empiece hoy)');
    return;
}

$conexion = cron_obtener_conexion();
if (!$conexion) {
    cron_linea('ERROR generar-informe_mensual: sin conexion a base de datos.');
    return;
}

if (!isset($fecha_informe_today) || $fecha_informe_today === '') {
    $fecha_informe_today = date('Y-m-d');
}

cron_linea(
    '>> Paso: generar-informe_mensual | mes=' . (int) $numero_mes
    . ' | anyo=' . $anyo_listado
    . ' | desde=' . $fecha_mes_desde
    . ' | hasta=' . $fecha_mes_hasta
);

$origenTestCron = 'generar informe mensual';
$sqlTestCron = 'INSERT INTO test_cron (hora_insert, origen) VALUES (NOW(), ?)';
$stmtTestCron = mysqli_prepare($conexion, $sqlTestCron);
if ($stmtTestCron) {
    mysqli_stmt_bind_param($stmtTestCron, 's', $origenTestCron);
    mysqli_stmt_execute($stmtTestCron);
    mysqli_stmt_close($stmtTestCron);
}

$sqlSucursales = "SELECT id_sucursal, empresa_id, matriz_beneficio_sucursal
                  FROM sucursal
                  WHERE estado_tienda = 'habilitada'
                  ORDER BY id_sucursal ASC";
$resultadoSucursales = mysqli_query($conexion, $sqlSucursales);
if (!$resultadoSucursales) {
    cron_linea('ERROR generar-informe_mensual consultando sucursales: ' . mysqli_error($conexion));
    return;
}

$sqlExisteInforme = "SELECT id_informe, estado_cron_informe
                     FROM informe_mensual
                     WHERE sucursal_informe = ?
                       AND numero_mes = ?
                       AND year_informe = ?
                     ORDER BY id_informe ASC
                     LIMIT 1";
$stmtExisteInforme = mysqli_prepare($conexion, $sqlExisteInforme);

$sqlInsertInforme = "INSERT INTO informe_mensual (
    sucursal_informe,
    fecha_informe,
    fecha_generado,
    hora_generado,
    empresa_informe_id,
    usuario_genera_informe,
    estado_informe,
    estado_cron_informe,
    numero_mes,
    fecha_desde,
    fecha_hasta,
    year_informe
) VALUES (?, ?, NOW(), NOW(), ?, 1, 'abierto', 'inicializado_cron', ?, ?, ?, ?)";
$stmtInsertInforme = mysqli_prepare($conexion, $sqlInsertInforme);

$sqlUpdateListado = "UPDATE listado_numero_meses
                     SET estado_cron = 'true'
                     WHERE numero_mes = ?
                       AND anyo_listado = ?";
$stmtUpdateListado = mysqli_prepare($conexion, $sqlUpdateListado);

$sqlTestCronSucursal = 'INSERT INTO test_cron (hora_insert, origen) VALUES (NOW(), ?)';
$stmtTestCronSucursal = mysqli_prepare($conexion, $sqlTestCronSucursal);

if (!$stmtExisteInforme || !$stmtInsertInforme || !$stmtUpdateListado) {
    cron_linea('ERROR generar-informe_mensual preparando consultas: ' . mysqli_error($conexion));
    mysqli_free_result($resultadoSucursales);
    if ($stmtExisteInforme) {
        mysqli_stmt_close($stmtExisteInforme);
    }
    if ($stmtInsertInforme) {
        mysqli_stmt_close($stmtInsertInforme);
    }
    if ($stmtUpdateListado) {
        mysqli_stmt_close($stmtUpdateListado);
    }
    if ($stmtTestCronSucursal) {
        mysqli_stmt_close($stmtTestCronSucursal);
    }
    return;
}

$numeroMesInt = (int) $numero_mes;

while ($sucursal = mysqli_fetch_assoc($resultadoSucursales)) {
    $sucursalInforme = isset($sucursal['id_sucursal']) ? (int) $sucursal['id_sucursal'] : 0;
    $empresaInformeId = isset($sucursal['empresa_id']) ? (int) $sucursal['empresa_id'] : 0;

    if ($sucursalInforme <= 0) {
        continue;
    }

    cron_linea('  - sucursal_informe: ' . $sucursalInforme);

    mysqli_stmt_bind_param($stmtExisteInforme, 'iis', $sucursalInforme, $numeroMesInt, $anyo_listado);
    if (!mysqli_stmt_execute($stmtExisteInforme)) {
        cron_linea('    ERROR consultando informe existente: ' . mysqli_stmt_error($stmtExisteInforme));
        continue;
    }

    $resultadoInforme = mysqli_stmt_get_result($stmtExisteInforme);
    $informeExistente = $resultadoInforme ? mysqli_fetch_assoc($resultadoInforme) : null;
    $idInformeReady = $informeExistente && isset($informeExistente['id_informe'])
        ? (int) $informeExistente['id_informe']
        : 0;

    if ($idInformeReady > 0) {
        cron_linea('    El informe mensual ya existe Nº ' . $idInformeReady);
        continue;
    }

    cron_linea('    No existe informe mensual, se genera uno nuevo.');

    if ($stmtTestCronSucursal) {
        $origenSucursal = 'generar informe mensual sucursal ' . $sucursalInforme;
        mysqli_stmt_bind_param($stmtTestCronSucursal, 's', $origenSucursal);
        mysqli_stmt_execute($stmtTestCronSucursal);
    }

    mysqli_stmt_bind_param(
        $stmtInsertInforme,
        'isiisss',
        $sucursalInforme,
        $fecha_informe_today,
        $empresaInformeId,
        $numeroMesInt,
        $fecha_mes_desde,
        $fecha_mes_hasta,
        $anyo_listado
    );

    if (!mysqli_stmt_execute($stmtInsertInforme)) {
        cron_linea('    ERROR insertando informe mensual: ' . mysqli_stmt_error($stmtInsertInforme));
        continue;
    }

    $idInformeGenerate = (int) mysqli_insert_id($conexion);

    mysqli_stmt_bind_param($stmtUpdateListado, 'is', $numeroMesInt, $anyo_listado);
    mysqli_stmt_execute($stmtUpdateListado);

    registrar_tareas_cron(
        'Genero informe mensual Nº ' . $idInformeGenerate . ' de la Sucursal ' . $sucursalInforme
    );

    cron_linea('    Informe mensual creado Nº ' . $idInformeGenerate);
}

mysqli_free_result($resultadoSucursales);
mysqli_stmt_close($stmtExisteInforme);
mysqli_stmt_close($stmtInsertInforme);
mysqli_stmt_close($stmtUpdateListado);
if ($stmtTestCronSucursal) {
    mysqli_stmt_close($stmtTestCronSucursal);
}
