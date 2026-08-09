<?php

/**
 * Cron independiente: finalizar informe actual del día.
 * Ejecución: servidor Linux (CLI) — CRON/informe_actual_finalizar.php
 *
 * Ejemplo crontab (todos los días a las 21:20):
 * 20 21 * * * /usr/bin/php /ruta/al/proyecto/CRON/informe_actual_finalizar.php >> /ruta/logs/informe_actual_finalizar.log 2>&1
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
    echo '<!DOCTYPE html><html lang="es"><head><meta charset="utf-8"><title>Finalizar informe actual</title></head><body>';
    echo '<h1>CRON — finalizar informe actual</h1>';
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
    $_SERVER['REQUEST_URI'] = $_SERVER['REQUEST_URI'] ?? '/CRON/informe_actual_finalizar.php';
    $_SERVER['REMOTE_ADDR'] = $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';
    $_SERVER['HTTP_USER_AGENT'] = $_SERVER['HTTP_USER_AGENT'] ?? 'cron';
}

cron_iniciar_salida();
cron_linea($esCli ? 'Entorno: CLI (cron)' : 'Entorno: navegador (prueba manual)');
cron_linea('>> Inicio: informe_actual_finalizar');

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

    cron_linea('OK: conexión a base de datos establecida.');

    require_once __DIR__ . '/functions_cron.php';
    cron_establecer_conexion($conexion);

    $fecha_informe_actual_today = date('Y-m-d');
    $numeroSemana = numeroSemanaConFecha($fecha_informe_actual_today);

    cron_linea('  fecha_informe: ' . $fecha_informe_actual_today);

    require __DIR__ . '/finalizar-informe_actual.php';

    cron_linea('>> Fin: informe_actual_finalizar');
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
