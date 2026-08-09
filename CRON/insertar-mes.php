<?php

/**
 * Genera meses (bloques de 4 semanas) y actualiza rel_id_mes en listado_numero_semanas.
 * Requiere: $anyo_semanal_mensual, $fecha_inicio_anyo_mensual (desde generar_listados_semanas_meses.php).
 */

if (!isset($anyo_semanal_mensual)) {
    $anyo_inicio = (int) date('Y');
    $anyo_semanal_mensual = $anyo_inicio + 1;
}

if (!isset($fecha_inicio_anyo_mensual) || $fecha_inicio_anyo_mensual === '') {
    $fecha_inicio_anyo_mensual = (int) date('Y') . '-12-30';
}

$conexion = cron_obtener_conexion();
if (!$conexion) {
    cron_linea('ERROR insertar-mes: sin conexion a base de datos TPV.');
    return;
}

cron_linea('>> Paso: insertar-mes | anyo_listado=' . $anyo_semanal_mensual);

$sqlSemanas = 'SELECT id_numero_semana, numero_semana, fecha_inicio_anyo, fecha_semana_desde, fecha_semana_hasta
               FROM listado_numero_semanas
               WHERE anyo_listado = ?
               ORDER BY numero_semana ASC';
$stmtSemanas = mysqli_prepare($conexion, $sqlSemanas);

if (!$stmtSemanas) {
    cron_linea('ERROR insertar-mes preparando consulta de semanas: ' . mysqli_error($conexion));
    return;
}

mysqli_stmt_bind_param($stmtSemanas, 'i', $anyo_semanal_mensual);
if (!mysqli_stmt_execute($stmtSemanas)) {
    cron_linea('ERROR insertar-mes consultando semanas: ' . mysqli_stmt_error($stmtSemanas));
    mysqli_stmt_close($stmtSemanas);
    return;
}

$resultadoSemanas = mysqli_stmt_get_result($stmtSemanas);
$semanas = array();

while ($fila = $resultadoSemanas ? mysqli_fetch_assoc($resultadoSemanas) : false) {
    $semanas[] = array(
        'id_numero_semana' => (int) $fila['id_numero_semana'],
        'numero_semana' => (int) $fila['numero_semana'],
        'fecha_inicio_anyo' => (string) $fila['fecha_inicio_anyo'],
        'fecha_semana_desde' => (string) $fila['fecha_semana_desde'],
        'fecha_semana_hasta' => (string) $fila['fecha_semana_hasta'],
    );
}

mysqli_stmt_close($stmtSemanas);

if (count($semanas) === 0) {
    cron_linea('ERROR insertar-mes: no hay semanas para anyo ' . $anyo_semanal_mensual);
    return;
}

$sqlInsertMes = 'INSERT INTO listado_numero_meses (
    numero_mes,
    fecha_inicio_anyo,
    fecha_mes_desde,
    fecha_mes_hasta,
    anyo_listado
) VALUES (?, ?, ?, ?, ?)';

$stmtInsertMesTpv = mysqli_prepare($conexion, $sqlInsertMes);

$sqlUpdSemanasTpv = 'UPDATE listado_numero_semanas
                     SET rel_id_mes = ?
                     WHERE anyo_listado = ?
                       AND fecha_semana_desde BETWEEN ? AND ?';
$stmtUpdSemanasTpv = mysqli_prepare($conexion, $sqlUpdSemanasTpv);

if (!$stmtInsertMesTpv || !$stmtUpdSemanasTpv) {
    cron_linea('ERROR insertar-mes preparando consultas: ' . mysqli_error($conexion));
    if ($stmtInsertMesTpv) {
        mysqli_stmt_close($stmtInsertMesTpv);
    }
    if ($stmtUpdSemanasTpv) {
        mysqli_stmt_close($stmtUpdSemanasTpv);
    }
    return;
}

$numeroMes = 0;
$mesesInsertados = 0;
$errores = 0;
$totalSemanas = count($semanas);

for ($i = 0; $i < $totalSemanas; $i++) {
    $numeroSemana = $semanas[$i]['numero_semana'];

    if (($numeroSemana - 1) % 4 !== 0) {
        continue;
    }

    $numeroMes++;
    $fechaInicioRango = $semanas[$i]['fecha_semana_desde'];
    $semanaFinRango = $numeroSemana + 3;
    $fechaFinRango = null;

    for ($j = $i; $j < $totalSemanas; $j++) {
        if ($semanas[$j]['numero_semana'] === $semanaFinRango) {
            $fechaFinRango = $semanas[$j]['fecha_semana_hasta'];
            break;
        }
    }

    if ($fechaFinRango === null) {
        cron_linea('  - Mes ' . $numeroMes . ': sin semana fin ' . $semanaFinRango . ', se omite.');
        continue;
    }

    cron_linea(
        '  - Mes ' . $numeroMes . ' | semanas ' . $numeroSemana . '-' . $semanaFinRango
        . ' | ' . $fechaInicioRango . ' a ' . $fechaFinRango
    );

    mysqli_stmt_bind_param(
        $stmtInsertMesTpv,
        'isssi',
        $numeroMes,
        $fecha_inicio_anyo_mensual,
        $fechaInicioRango,
        $fechaFinRango,
        $anyo_semanal_mensual
    );

    $okTpv = mysqli_stmt_execute($stmtInsertMesTpv);
    if (!$okTpv) {
        $errores++;
        cron_linea('    ERROR INSERT mes TPV: ' . mysqli_stmt_error($stmtInsertMesTpv));
    } else {
        $mesesInsertados++;
    }

    mysqli_stmt_bind_param(
        $stmtUpdSemanasTpv,
        'iiss',
        $numeroMes,
        $anyo_semanal_mensual,
        $fechaInicioRango,
        $fechaFinRango
    );

    if (!mysqli_stmt_execute($stmtUpdSemanasTpv)) {
        $errores++;
        cron_linea('    ERROR UPDATE semanas TPV mes ' . $numeroMes . ': ' . mysqli_stmt_error($stmtUpdSemanasTpv));
    }
}

mysqli_stmt_close($stmtInsertMesTpv);
mysqli_stmt_close($stmtUpdSemanasTpv);

registrar_tareas_cron('Insertados ' . $mesesInsertados . ' meses del anyo ' . $anyo_semanal_mensual);

cron_linea('  - Meses insertados TPV: ' . $mesesInsertados . ' | errores: ' . $errores);
