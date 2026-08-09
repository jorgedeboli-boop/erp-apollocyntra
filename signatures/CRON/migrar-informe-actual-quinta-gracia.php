<?php

/**
 * Informe actual: copia informes abiertos de hoy a Manager Quinta Gracia (manager_main_222).
 */

$ctx = cron_informe_actual_contexto('migrar-informe-actual-quinta-gracia');
if (!$ctx) {
    return;
}

$conexion = $ctx['conexion'];
$fechaInformeToday = $ctx['fecha'];

$conexionManager = cron_conectar_manager_quinta_gracia();
if (!$conexionManager) {
    cron_linea('ERROR migrar-informe-actual-quinta-gracia: no se pudo conectar a Manager Quinta Gracia.');
    return;
}

$sqlInformes = "SELECT * FROM informe_actual
                WHERE fecha_informe = ?
                  AND estado_informe = 'abierto'
                ORDER BY id_informe ASC";
$stmtInformes = mysqli_prepare($conexion, $sqlInformes);
if (!$stmtInformes) {
    cron_linea('ERROR migrar-informe-actual-quinta-gracia preparando SELECT.');
    mysqli_close($conexionManager);
    return;
}

mysqli_stmt_bind_param($stmtInformes, 's', $fechaInformeToday);
if (!mysqli_stmt_execute($stmtInformes)) {
    cron_linea('ERROR migrar-informe-actual-quinta-gracia ejecutando SELECT.');
    mysqli_stmt_close($stmtInformes);
    mysqli_close($conexionManager);
    return;
}

$sqlExiste = 'SELECT id_informe FROM informe_actual WHERE id_informe = ? LIMIT 1';
$stmtExiste = mysqli_prepare($conexionManager, $sqlExiste);

$resultadoInformes = mysqli_stmt_get_result($stmtInformes);
$totalMigrados = 0;
$totalActualizados = 0;
$totalErrores = 0;

while ($fila = $resultadoInformes ? mysqli_fetch_assoc($resultadoInformes) : false) {
    $idInforme = isset($fila['id_informe']) ? (int) $fila['id_informe'] : 0;
    if ($idInforme <= 0) {
        continue;
    }

    $yaExiste = false;
    if ($stmtExiste) {
        mysqli_stmt_bind_param($stmtExiste, 'i', $idInforme);
        mysqli_stmt_execute($stmtExiste);
        $resultadoExiste = mysqli_stmt_get_result($stmtExiste);
        $yaExiste = $resultadoExiste && mysqli_fetch_assoc($resultadoExiste);
    }

    if ($yaExiste) {
        $asignaciones = array();
        foreach ($fila as $columna => $valor) {
            if ($columna === 'id_informe') {
                continue;
            }
            if ($valor === null) {
                $asignaciones[] = '`' . $columna . '` = NULL';
            } else {
                $asignaciones[] = '`' . $columna . '` = \'' . mysqli_real_escape_string($conexionManager, (string) $valor) . '\'';
            }
        }

        $sqlUpdate = 'UPDATE informe_actual SET ' . implode(', ', $asignaciones) . ' WHERE id_informe = ' . $idInforme;
        if (mysqli_query($conexionManager, $sqlUpdate)) {
            $totalActualizados++;
            cron_linea('  - Informe ' . $idInforme . ' actualizado en Manager.');
        } else {
            $totalErrores++;
            cron_linea('  ERROR UPDATE informe ' . $idInforme . ': ' . mysqli_error($conexionManager));
        }
        continue;
    }

    $columnas = array_keys($fila);
    $valoresEscapados = array();

    foreach ($fila as $valor) {
        if ($valor === null) {
            $valoresEscapados[] = 'NULL';
        } else {
            $valoresEscapados[] = "'" . mysqli_real_escape_string($conexionManager, (string) $valor) . "'";
        }
    }

    $columnasSql = '`' . implode('`, `', $columnas) . '`';
    $valoresSql = implode(', ', $valoresEscapados);
    $sqlInsert = 'INSERT INTO informe_actual (' . $columnasSql . ') VALUES (' . $valoresSql . ')';

    if (mysqli_query($conexionManager, $sqlInsert)) {
        $totalMigrados++;
        cron_linea('  - Informe ' . $idInforme . ' migrado.');
    } else {
        $totalErrores++;
        cron_linea('  ERROR INSERT informe ' . $idInforme . ': ' . mysqli_error($conexionManager));
    }
}

if ($stmtExiste) {
    mysqli_stmt_close($stmtExiste);
}
mysqli_stmt_close($stmtInformes);
mysqli_close($conexionManager);

if ($totalMigrados === 0 && $totalActualizados === 0 && $totalErrores === 0) {
    cron_linea('  - No hay informes abiertos para migrar.');
} else {
    cron_linea(
        '  - Migrados: ' . $totalMigrados
        . ' | actualizados: ' . $totalActualizados
        . ' | errores: ' . $totalErrores
    );
}
