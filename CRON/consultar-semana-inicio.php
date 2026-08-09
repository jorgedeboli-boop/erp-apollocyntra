<?php

/**
 * Comprueba si hoy empieza una semana en listado_numero_semanas.
 */

$fecha_informe_today = date('Y-m-d');
$id_numero_semana = null;
$numeroSemana = null;
$fecha_semana_desde = null;
$fecha_semana_hasta = null;
$anyo_listado = null;
$rel_id_mes = null;

$conexion = cron_obtener_conexion();
if (!$conexion) {
    cron_linea('ERROR consultar-semana-inicio: sin conexion a base de datos.');
    return;
}

cron_linea('>> Paso: consultar-semana-inicio | fecha=' . $fecha_informe_today);

$sql = "SELECT id_numero_semana, numero_semana, fecha_semana_desde, fecha_semana_hasta, anyo_listado, rel_id_mes
        FROM listado_numero_semanas
        WHERE fecha_semana_desde = ?
        LIMIT 1";
$stmt = mysqli_prepare($conexion, $sql);
if (!$stmt) {
    cron_linea('ERROR consultar-semana-inicio preparando consulta: ' . mysqli_error($conexion));
    return;
}

mysqli_stmt_bind_param($stmt, 's', $fecha_informe_today);
if (!mysqli_stmt_execute($stmt)) {
    cron_linea('ERROR consultar-semana-inicio ejecutando consulta: ' . mysqli_stmt_error($stmt));
    mysqli_stmt_close($stmt);
    return;
}

$resultado = mysqli_stmt_get_result($stmt);
$fila = $resultado ? mysqli_fetch_assoc($resultado) : null;
mysqli_stmt_close($stmt);

if (!$fila || empty($fila['id_numero_semana'])) {
    cron_linea('  No hay numero de semana que empiece hoy.');
    cron_informe_semanal_resolver_anyo();
    return;
}

$id_numero_semana = (int) $fila['id_numero_semana'];
$numeroSemana = isset($fila['numero_semana']) ? (int) $fila['numero_semana'] : 0;
$fecha_semana_desde = isset($fila['fecha_semana_desde']) ? (string) $fila['fecha_semana_desde'] : '';
$fecha_semana_hasta = isset($fila['fecha_semana_hasta']) ? (string) $fila['fecha_semana_hasta'] : '';
$anyo_listado = isset($fila['anyo_listado']) ? (string) $fila['anyo_listado'] : '';
$rel_id_mes = isset($fila['rel_id_mes']) ? (int) $fila['rel_id_mes'] : 0;

cron_linea(
    '  Semana encontrada: n=' . $numeroSemana
    . ' | anyo=' . $anyo_listado
    . ' | desde=' . $fecha_semana_desde
    . ' | hasta=' . $fecha_semana_hasta
    . ' | mes=' . $rel_id_mes
);
