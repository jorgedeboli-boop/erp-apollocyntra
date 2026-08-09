<?php

/**
 * Genera el registro base de informe_diario por sucursal habilitada (si no existe hoy).
 */

$conexion = cron_obtener_conexion();
if (!$conexion) {
    cron_linea('ERROR generar-informe: sin conexion a base de datos.');
    return;
}

if (!isset($fecha_informe_today) || $fecha_informe_today === '') {
    $fecha_informe_today = date('Y-m-d');
}

if (!isset($numeroSemana)) {
    $numeroSemana = numeroSemanaConFecha($fecha_informe_today);
}

$yearRel = date('Y');

cron_linea('>> Paso: generar-informe | fecha=' . $fecha_informe_today . ' | semana=' . $numeroSemana);

$sqlSucursales = "SELECT id_sucursal, empresa_id, matriz_beneficio_sucursal
                  FROM sucursal
                  WHERE estado_tienda = 'habilitada'
                  ORDER BY id_sucursal ASC";
$resultadoSucursales = mysqli_query($conexion, $sqlSucursales);

if (!$resultadoSucursales) {
    cron_linea('ERROR generar-informe consultando sucursales: ' . mysqli_error($conexion));
    return;
}

$sqlExisteInforme = "SELECT id_informe, estado_cron_informe
                     FROM informe_diario
                     WHERE sucursal_informe = ?
                       AND fecha_informe = ?
                     ORDER BY id_informe ASC
                     LIMIT 1";
$stmtExisteInforme = mysqli_prepare($conexion, $sqlExisteInforme);

$sqlInsertInforme = "INSERT INTO informe_diario (
    sucursal_informe,
    fecha_informe,
    fecha_generado,
    hora_generado,
    empresa_informe_id,
    usuario_genera_informe,
    estado_informe,
    estado_cron_informe,
    matriz_beneficio_tienda,
    year_rel,
    semana_numero
) VALUES (?, ?, NOW(), NOW(), ?, 1, 'abierto', 'inicializado_cron', ?, ?, ?)";
$stmtInsertInforme = mysqli_prepare($conexion, $sqlInsertInforme);

$sqlTestCron = "INSERT INTO test_cron (hora_insert, origen) VALUES (NOW(), ?)";
$stmtTestCron = mysqli_prepare($conexion, $sqlTestCron);

if (!$stmtExisteInforme || !$stmtInsertInforme || !$stmtTestCron) {
    cron_linea('ERROR generar-informe preparando consultas: ' . mysqli_error($conexion));
    if ($resultadoSucursales) {
        mysqli_free_result($resultadoSucursales);
    }
    if ($stmtExisteInforme) {
        mysqli_stmt_close($stmtExisteInforme);
    }
    if ($stmtInsertInforme) {
        mysqli_stmt_close($stmtInsertInforme);
    }
    if ($stmtTestCron) {
        mysqli_stmt_close($stmtTestCron);
    }
    return;
}

while ($sucursal = mysqli_fetch_assoc($resultadoSucursales)) {
    $sucursalInforme = isset($sucursal['id_sucursal']) ? (int) $sucursal['id_sucursal'] : 0;
    $empresaInformeId = isset($sucursal['empresa_id']) ? (int) $sucursal['empresa_id'] : 0;
    $matrizBeneficioTienda = isset($sucursal['matriz_beneficio_sucursal'])
        ? (string) $sucursal['matriz_beneficio_sucursal']
        : '';

    if ($sucursalInforme <= 0) {
        continue;
    }

    cron_linea('  - sucursal_informe: ' . $sucursalInforme);

    mysqli_stmt_bind_param($stmtExisteInforme, 'is', $sucursalInforme, $fecha_informe_today);
    if (!mysqli_stmt_execute($stmtExisteInforme)) {
        cron_linea('    ERROR consultando informe existente: ' . mysqli_stmt_error($stmtExisteInforme));
        continue;
    }

    $resultadoInforme = mysqli_stmt_get_result($stmtExisteInforme);
    $informeExistente = $resultadoInforme ? mysqli_fetch_assoc($resultadoInforme) : null;
    $idInformeReady = $informeExistente && isset($informeExistente['id_informe'])
        ? (int) $informeExistente['id_informe']
        : 0;

    if ($idInformeReady <= 0) {
        cron_linea('    No existe informe, se genera uno nuevo.');

        $origenTestCron = 'generar informe inside sucursal ' . $sucursalInforme;
        mysqli_stmt_bind_param($stmtTestCron, 's', $origenTestCron);
        mysqli_stmt_execute($stmtTestCron);

        $semanaNumero = $numeroSemana !== null ? (int) $numeroSemana : 0;

        mysqli_stmt_bind_param(
            $stmtInsertInforme,
            'isisii',
            $sucursalInforme,
            $fecha_informe_today,
            $empresaInformeId,
            $matrizBeneficioTienda,
            $yearRel,
            $semanaNumero
        );

        if (!mysqli_stmt_execute($stmtInsertInforme)) {
            cron_linea('    ERROR insertando informe: ' . mysqli_stmt_error($stmtInsertInforme));
            continue;
        }

        $idInformeGenerate = (int) mysqli_insert_id($conexion);
        $id_informe_generate = $idInformeGenerate;
        $id_informe_READY = 0;

        registrar_tareas_cron(
            'Genero informe diario Nº ' . $idInformeGenerate . ' de la Sucursal ' . $sucursalInforme
        );

        cron_linea('    Informe creado Nº ' . $idInformeGenerate);
    } else {
        $id_informe_READY = $idInformeReady;
        $id_informe_generate = 0;
        cron_linea('    El informe de hoy ya existe Nº ' . $idInformeReady);
    }
}

mysqli_free_result($resultadoSucursales);
mysqli_stmt_close($stmtExisteInforme);
mysqli_stmt_close($stmtInsertInforme);
mysqli_stmt_close($stmtTestCron);
