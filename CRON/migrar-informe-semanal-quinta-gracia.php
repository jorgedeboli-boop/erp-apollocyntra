<?php

/**
 * Informe semanal: sincroniza informes abiertos a Manager Quinta Gracia (UPDATE por id_informe).
 */

$ctx = cron_informe_semanal_contexto('migrar-informe-semanal-quinta-gracia');
if (!$ctx) {
    return;
}

$conexion = $ctx['conexion'];
$anyoListado = $ctx['anyo'];

$conexionManager = cron_conectar_manager_quinta_gracia();
if (!$conexionManager) {
    cron_linea('ERROR migrar-informe-semanal-quinta-gracia: no se pudo conectar a Manager Quinta Gracia.');
    return;
}

$origenTestInicio = 'test cron semanal inicia migracion quinta';
$sqlTestCron = 'INSERT INTO test_cron (origen, hora_insert) VALUES (?, NOW())';
$stmtTestCron = mysqli_prepare($conexion, $sqlTestCron);
if ($stmtTestCron) {
    mysqli_stmt_bind_param($stmtTestCron, 's', $origenTestInicio);
    mysqli_stmt_execute($stmtTestCron);
    mysqli_stmt_close($stmtTestCron);
}

$columnasPermitidas = array();
$resultadoColumnas = mysqli_query($conexion, 'DESCRIBE informe_semanal');
if ($resultadoColumnas) {
    while ($col = mysqli_fetch_assoc($resultadoColumnas)) {
        if (isset($col['Field']) && $col['Field'] !== 'id_informe') {
            $columnasPermitidas[] = $col['Field'];
        }
    }
    mysqli_free_result($resultadoColumnas);
}

if (empty($columnasPermitidas)) {
    cron_linea('ERROR migrar-informe-semanal-quinta-gracia: no se pudieron leer columnas.');
    mysqli_close($conexionManager);
    return;
}

$sqlInformes = "SELECT * FROM informe_semanal WHERE estado_informe = 'abierto' AND year_informe = ? ORDER BY id_informe ASC";
$stmtInformes = mysqli_prepare($conexion, $sqlInformes);
if (!$stmtInformes) {
    mysqli_close($conexionManager);
    return;
}

mysqli_stmt_bind_param($stmtInformes, 's', $anyoListado);
mysqli_stmt_execute($stmtInformes);
$resultadoInformes = mysqli_stmt_get_result($stmtInformes);

$totalMigrados = 0;
$totalErrores = 0;

while ($fila = $resultadoInformes ? mysqli_fetch_assoc($resultadoInformes) : false) {
    $idInforme = isset($fila['id_informe']) ? (int) $fila['id_informe'] : 0;
    if ($idInforme <= 0) {
        continue;
    }

    $updates = array();
    foreach ($columnasPermitidas as $columna) {
        if (!array_key_exists($columna, $fila)) {
            continue;
        }
        $valor = $fila[$columna];
        if ($valor === null) {
            $updates[] = '`' . $columna . '` = NULL';
        } else {
            $updates[] = '`' . $columna . '` = \'' . mysqli_real_escape_string($conexionManager, (string) $valor) . '\'';
        }
    }

    if (empty($updates)) {
        continue;
    }

    $sqlUpdate = 'UPDATE informe_semanal SET ' . implode(', ', $updates) . ' WHERE id_informe = ' . $idInforme;
    if (mysqli_query($conexionManager, $sqlUpdate)) {
        $totalMigrados++;
        cron_linea('  - Informe semanal ' . $idInforme . ' migrado a Manager.');
    } else {
        $totalErrores++;
        cron_linea('  ERROR UPDATE informe semanal ' . $idInforme . ': ' . mysqli_error($conexionManager));
    }
}

mysqli_stmt_close($stmtInformes);

$origenTestFin = 'test cron semanal finaliza migracion quinta';
$stmtTestCronFin = mysqli_prepare($conexion, $sqlTestCron);
if ($stmtTestCronFin) {
    mysqli_stmt_bind_param($stmtTestCronFin, 's', $origenTestFin);
    mysqli_stmt_execute($stmtTestCronFin);
    mysqli_stmt_close($stmtTestCronFin);
}

mysqli_close($conexionManager);

cron_linea('  - Migrados: ' . $totalMigrados . ' | errores: ' . $totalErrores);
