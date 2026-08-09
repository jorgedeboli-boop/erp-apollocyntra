<?php

/**
 * Cron independiente: generar listados de semanas y meses.
 * Ejecución: servidor Linux (CLI) — CRON/generar_listados_semanas_meses.php
 *
 * Se lanza ~45 min después del informe diario (ej. informe 6:00, este 6:45).
 *
 * Ejemplo crontab:
 * 45 6 * * * /usr/bin/php /ruta/al/proyecto/CRON/generar_listados_semanas_meses.php >> /ruta/logs/listados_semanas_meses.log 2>&1
 */

require_once __DIR__ . '/cron_state_guard.php';

$esCli = (PHP_SAPI === 'cli');

if (!$esCli) {
    ini_set('display_errors', '1');
    error_reporting(E_ALL);
}

function cron_imprimir($mensaje)
{
    global $esCli;
    if ($esCli) {
        echo $mensaje;
        return;
    }
    echo htmlspecialchars($mensaje, ENT_QUOTES, 'UTF-8');
}

function cron_linea($mensaje)
{
    cron_imprimir($mensaje . "\n");
    if (!(PHP_SAPI === 'cli')) {
        if (ob_get_level() > 0) {
            ob_flush();
        }
        flush();
    }
}

function cron_iniciar_salida()
{
    global $esCli;
    if ($esCli) {
        return;
    }
    header('Content-Type: text/html; charset=utf-8');
    echo '<!DOCTYPE html><html lang="es"><head><meta charset="utf-8"><title>Listados semanas/meses</title></head><body>';
    echo '<h1>CRON — listados semanas y meses</h1>';
    echo '<pre style="font-family:monospace;font-size:13px;line-height:1.45;">';
}

function cron_cerrar_salida()
{
    global $esCli;
    if ($esCli) {
        return;
    }
    echo '</pre></body></html>';
}

if ($esCli) {
    $_SERVER['HTTP_HOST'] = $_SERVER['HTTP_HOST'] ?? 'localhost';
    $_SERVER['REQUEST_URI'] = $_SERVER['REQUEST_URI'] ?? '/CRON/generar_listados_semanas_meses.php';
    $_SERVER['REMOTE_ADDR'] = $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';
    $_SERVER['HTTP_USER_AGENT'] = $_SERVER['HTTP_USER_AGENT'] ?? 'cron';
}

cron_iniciar_salida();
cron_linea($esCli ? 'Entorno: CLI (cron)' : 'Entorno: navegador (prueba manual)');
cron_linea('>> Inicio: generar_listados_semanas_meses');

$conexion = null;

try {
    chdir(__DIR__ . '/../include');
    require_once __DIR__ . '/../include/functions.php';

    if (!$esCli && function_exists('environment_is_production') && environment_is_production()) {
        cron_linea('ERROR: la ejecución manual no está permitida en producción.');
        cron_cerrar_salida();
        exit(1);
    }

    $conexion = conectar_bd();
    if (!$conexion) {
        cron_linea('ERROR: no se pudo conectar a la base de datos.');
        cron_cerrar_salida();
        exit(1);
    }

    if (function_exists('mysqli_report')) {
        mysqli_report(MYSQLI_REPORT_OFF);
    }

    cron_linea('OK: conexión a base de datos establecida.');

    require_once __DIR__ . '/functions_cron.php';
    cron_establecer_conexion($conexion);

    $fecha_informe_today = date('Y-m-d');
    $anyo_inicio = (int) date('Y');
    $anyo_semanal = $anyo_inicio + 1;
    $fecha_inicio_anyo = $anyo_inicio . '-12-30';

    cron_linea('fecha_informe_today: ' . $fecha_informe_today);
    cron_linea('fecha_inicio_anyo: ' . $fecha_inicio_anyo);
    cron_linea('anyo_semanal: ' . $anyo_semanal);

    if ($fecha_informe_today !== $fecha_inicio_anyo) {
        cron_linea('No se ejecuta: hoy no es el 30 de diciembre de lanzamiento.');
        cron_linea('>> Fin: generar_listados_semanas_meses');
        cron_cerrar_salida();
        exit(0);
    }

    cron_linea('Es el 30 de diciembre: se comprueba listado de semanas para ' . $anyo_semanal);

    $id_numero_semana = 0;
    $sqlExiste = 'SELECT id_numero_semana FROM listado_numero_semanas WHERE anyo_listado = ? LIMIT 1';
    $stmtExiste = mysqli_prepare($conexion, $sqlExiste);

    if (!$stmtExiste) {
        cron_linea('ERROR preparando consulta listado_numero_semanas: ' . mysqli_error($conexion));
        cron_cerrar_salida();
        exit(1);
    }

    mysqli_stmt_bind_param($stmtExiste, 'i', $anyo_semanal);
    if (!mysqli_stmt_execute($stmtExiste)) {
        cron_linea('ERROR consultando listado_numero_semanas: ' . mysqli_stmt_error($stmtExiste));
        mysqli_stmt_close($stmtExiste);
        cron_cerrar_salida();
        exit(1);
    }

    $resultadoExiste = mysqli_stmt_get_result($stmtExiste);
    $filaExiste = $resultadoExiste ? mysqli_fetch_assoc($resultadoExiste) : null;
    mysqli_stmt_close($stmtExiste);

    if ($filaExiste && isset($filaExiste['id_numero_semana'])) {
        $id_numero_semana = (int) $filaExiste['id_numero_semana'];
    }

    if ($id_numero_semana <= 0) {
        cron_linea('No existe listado para anyo ' . $anyo_semanal . ': cargando insertar-semanas.php');
        require __DIR__ . '/insertar-semanas.php';
    } else {
        cron_linea('Listado ya existe (id_numero_semana=' . $id_numero_semana . '), no se inserta.');
    }

    $anyo_semanal_mensual = $anyo_semanal;
    $fecha_inicio_anyo_mensual = $fecha_inicio_anyo;

    cron_linea('Comprobando listado de meses para anyo ' . $anyo_semanal_mensual);

    $id_numero_mes = 0;
    $sqlExisteMes = 'SELECT id_numero_mes FROM listado_numero_meses WHERE anyo_listado = ? LIMIT 1';
    $stmtExisteMes = mysqli_prepare($conexion, $sqlExisteMes);

    if (!$stmtExisteMes) {
        cron_linea('ERROR preparando consulta listado_numero_meses: ' . mysqli_error($conexion));
        cron_cerrar_salida();
        exit(1);
    }

    mysqli_stmt_bind_param($stmtExisteMes, 'i', $anyo_semanal_mensual);
    if (!mysqli_stmt_execute($stmtExisteMes)) {
        cron_linea('ERROR consultando listado_numero_meses: ' . mysqli_stmt_error($stmtExisteMes));
        mysqli_stmt_close($stmtExisteMes);
        cron_cerrar_salida();
        exit(1);
    }

    $resultadoExisteMes = mysqli_stmt_get_result($stmtExisteMes);
    $filaExisteMes = $resultadoExisteMes ? mysqli_fetch_assoc($resultadoExisteMes) : null;
    mysqli_stmt_close($stmtExisteMes);

    if ($filaExisteMes && isset($filaExisteMes['id_numero_mes'])) {
        $id_numero_mes = (int) $filaExisteMes['id_numero_mes'];
    }

    if ($id_numero_mes <= 0) {
        cron_linea('No existe listado de meses para anyo ' . $anyo_semanal_mensual . ': cargando insertar-mes.php');
        require __DIR__ . '/insertar-mes.php';
    } else {
        cron_linea('Listado de meses ya existe (id_numero_mes=' . $id_numero_mes . '), no se inserta.');
    }

    cron_linea('>> Fin: generar_listados_semanas_meses');
} catch (Exception $e) {
    cron_linea('ERROR: ' . $e->getMessage());
    cron_cerrar_salida();
    exit(1);
} catch (Error $e) {
    cron_linea('ERROR: ' . $e->getMessage());
    cron_cerrar_salida();
    exit(1);
}

cron_cerrar_salida();
