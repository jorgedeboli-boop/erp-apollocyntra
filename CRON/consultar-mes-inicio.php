<?php

/**
 * Comprueba si hoy empieza un mes en listado_numero_meses.
 */

$fecha_informe_today = date('Y-m-d');
$id_numero_mes = null;
$numero_mes = null;
$fecha_mes_desde = null;
$fecha_mes_hasta = null;
$anyo_listado = null;

$conexion = cron_obtener_conexion();
if (!$conexion) {
    cron_linea('ERROR consultar-mes-inicio: sin conexion a base de datos.');
    return;
}

cron_linea('>> Paso: consultar-mes-inicio | fecha=' . $fecha_informe_today);

$sql = "SELECT id_numero_mes, numero_mes, fecha_mes_desde, fecha_mes_hasta, anyo_listado
        FROM listado_numero_meses
        WHERE fecha_mes_desde = ?
        LIMIT 1";
$stmt = mysqli_prepare($conexion, $sql);
if (!$stmt) {
    cron_linea('ERROR consultar-mes-inicio preparando consulta: ' . mysqli_error($conexion));
    return;
}

mysqli_stmt_bind_param($stmt, 's', $fecha_informe_today);
if (!mysqli_stmt_execute($stmt)) {
    cron_linea('ERROR consultar-mes-inicio ejecutando consulta: ' . mysqli_stmt_error($stmt));
    mysqli_stmt_close($stmt);
    return;
}

$resultado = mysqli_stmt_get_result($stmt);
$fila = $resultado ? mysqli_fetch_assoc($resultado) : null;
mysqli_stmt_close($stmt);

if (!$fila || empty($fila['id_numero_mes'])) {
    cron_linea('  No hay numero de mes que empiece hoy.');
    cron_informe_mensual_resolver_anyo();
    return;
}

$id_numero_mes = (int) $fila['id_numero_mes'];
$numero_mes = isset($fila['numero_mes']) ? (int) $fila['numero_mes'] : 0;
$fecha_mes_desde = isset($fila['fecha_mes_desde']) ? (string) $fila['fecha_mes_desde'] : '';
$fecha_mes_hasta = isset($fila['fecha_mes_hasta']) ? (string) $fila['fecha_mes_hasta'] : '';
$anyo_listado = isset($fila['anyo_listado']) ? (string) $fila['anyo_listado'] : '';

cron_linea(
    '  Mes encontrado: n=' . $numero_mes
    . ' | anyo=' . $anyo_listado
    . ' | desde=' . $fecha_mes_desde
    . ' | hasta=' . $fecha_mes_hasta
);
