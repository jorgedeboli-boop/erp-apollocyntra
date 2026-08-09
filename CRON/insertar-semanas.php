<?php

/**
 * Genera e inserta todas las semanas del año en listado_numero_semanas (TPV).
 * Requiere: $fecha_inicio_anyo, $anyo_semanal (desde generar_listados_semanas_meses.php).
 */

if (!isset($fecha_inicio_anyo) || $fecha_inicio_anyo === '') {
    $anyo_inicio = (int) date('Y');
    $anyo_semanal = $anyo_inicio + 1;
    $fecha_inicio_anyo = $anyo_inicio . '-12-30';
}

if (!isset($anyo_semanal)) {
    $anyo_semanal = (int) date('Y') + 1;
}

$conexion = cron_obtener_conexion();
if (!$conexion) {
    cron_linea('ERROR insertar-semanas: sin conexion a base de datos TPV.');
    return;
}

/**
 * @param DateTime $fechaInicio
 * @param DateTime $fechaReferencia
 * @return array<string, mixed>|false
 */
function cron_calcular_semana_listado($fechaInicio, $fechaReferencia)
{
    if ($fechaReferencia < $fechaInicio) {
        return false;
    }

    $diferencia = $fechaInicio->diff($fechaReferencia);
    $diferenciaDias = $diferencia->days;
    $numeroSemana = (int) floor($diferenciaDias / 7) + 1;

    $diasDesdeInicio = ($numeroSemana - 1) * 7;
    $inicioSemana = clone $fechaInicio;
    $inicioSemana->add(new DateInterval('P' . $diasDesdeInicio . 'D'));

    $finSemana = clone $inicioSemana;
    $finSemana->add(new DateInterval('P6D'));

    return array(
        'numero' => $numeroSemana,
        'inicio' => $inicioSemana->format('d/m/Y'),
        'fin' => $finSemana->format('d/m/Y'),
        'rango' => $inicioSemana->format('d/m/Y') . ' - ' . $finSemana->format('d/m/Y'),
    );
}

/**
 * @param int $numeroSemana
 * @param DateTime $fechaInicio
 * @return array<string, mixed>
 */
function cron_obtener_semana_listado($numeroSemana, $fechaInicio)
{
    $diasDesdeInicio = ($numeroSemana - 1) * 7;
    $inicioSemana = clone $fechaInicio;
    $inicioSemana->add(new DateInterval('P' . $diasDesdeInicio . 'D'));

    $finSemana = clone $inicioSemana;
    $finSemana->add(new DateInterval('P6D'));

    return array(
        'numero' => $numeroSemana,
        'inicio' => $inicioSemana->format('Y-m-d'),
        'fin' => $finSemana->format('Y-m-d'),
        'rango' => $inicioSemana->format('d/m/Y') . ' - ' . $finSemana->format('d/m/Y'),
    );
}

$fechaInicio = new DateTime($fecha_inicio_anyo);
$fechaActual = new DateTime();
$finDelAno = new DateTime($anyo_semanal . '-12-31');

cron_linea('>> Paso: insertar-semanas');
cron_linea('  fecha_inicio_anyo: ' . $fechaInicio->format('Y-m-d'));
cron_linea('  anyo_listado: ' . $anyo_semanal);

$semanaActual = cron_calcular_semana_listado($fechaInicio, $fechaActual);
if ($semanaActual) {
    cron_linea('  semana actual: ' . $semanaActual['numero'] . ' (' . $semanaActual['rango'] . ')');
}

$ultimaSemana = cron_calcular_semana_listado($fechaInicio, $finDelAno);
if ($ultimaSemana === false) {
    cron_linea('ERROR insertar-semanas: no se pudo calcular la ultima semana del año.');
    return;
}

$totalSemanas = (int) $ultimaSemana['numero'];
cron_linea('  total semanas hasta 31/12/' . $anyo_semanal . ': ' . $totalSemanas);

$sqlInsert = 'INSERT INTO listado_numero_semanas (
    numero_semana,
    fecha_inicio_anyo,
    fecha_semana_desde,
    fecha_semana_hasta,
    anyo_listado
) VALUES (?, ?, ?, ?, ?)';

$stmtTpv = mysqli_prepare($conexion, $sqlInsert);

if (!$stmtTpv) {
    cron_linea('ERROR insertar-semanas preparando INSERT: ' . mysqli_error($conexion));
    return;
}

$insertadasTpv = 0;
$errores = 0;

for ($i = 1; $i <= $totalSemanas; $i++) {
    $semana = cron_obtener_semana_listado($i, $fechaInicio);
    $numeroSemana = (int) $semana['numero'];
    $fechaSemanaDesde = $semana['inicio'];
    $fechaSemanaHasta = $semana['fin'];

    mysqli_stmt_bind_param(
        $stmtTpv,
        'isssi',
        $numeroSemana,
        $fecha_inicio_anyo,
        $fechaSemanaDesde,
        $fechaSemanaHasta,
        $anyo_semanal
    );

    if (mysqli_stmt_execute($stmtTpv)) {
        $insertadasTpv++;
    } else {
        $errores++;
        cron_linea('  ERROR INSERT TPV semana ' . $numeroSemana . ': ' . mysqli_stmt_error($stmtTpv));
    }
}

mysqli_stmt_close($stmtTpv);

$sqlTestCron = "INSERT INTO test_cron (hora_insert, origen) VALUES (NOW(), ?)";
$stmtTestCron = mysqli_prepare($conexion, $sqlTestCron);
if ($stmtTestCron) {
    $origen = 'crear listado semanales anyo ' . $anyo_semanal;
    mysqli_stmt_bind_param($stmtTestCron, 's', $origen);
    mysqli_stmt_execute($stmtTestCron);
    mysqli_stmt_close($stmtTestCron);
}

registrar_tareas_cron('Insertadas ' . $insertadasTpv . ' semanas del anyo ' . $anyo_semanal);

cron_linea(
    '  - Semanas insertadas TPV: ' . $insertadasTpv
    . ' | errores: ' . $errores
);
