<?php

/**
 * Cron independiente: sincronización ventas web (PrestaShop).
 * Ejecución: servidor Linux (CLI) — CRON/ventas_web.php
 *
 * Ejemplo crontab (cada 5 minutos):
 * 0,5,10,15,20,25,30,35,40,45,50,55 * * * * /usr/bin/php /ruta/al/proyecto/CRON/ventas_web.php >> /ruta/logs/ventas_web.log 2>&1
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
    echo '<!DOCTYPE html><html lang="es"><head><meta charset="utf-8"><title>Ventas web</title></head><body>';
    echo '<h1>CRON — ventas web</h1>';
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
    $_SERVER['REQUEST_URI'] = $_SERVER['REQUEST_URI'] ?? '/CRON/ventas_web.php';
    $_SERVER['REMOTE_ADDR'] = $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';
    $_SERVER['HTTP_USER_AGENT'] = $_SERVER['HTTP_USER_AGENT'] ?? 'cron';
}

if (!function_exists('environment_is_production') || !environment_is_production()) {
    exit(0);
}

cron_iniciar_salida();
cron_linea($esCli ? 'Entorno: CLI (cron)' : 'Entorno: navegador (prueba manual)');
cron_linea('>> Inicio: ventas_web');

$conexion = null;
$conexionWeb = null;

try {
    chdir(__DIR__ . '/../include');
    require_once __DIR__ . '/../include/functions.php';

    $conexion = conectar_bd();
    if (!$conexion) {
        cron_linea('ERROR: no se pudo conectar a la base de datos TPV.');
        cron_cerrar_salida();
        exit(1);
    }

    if (function_exists('mysqli_report')) {
        mysqli_report(MYSQLI_REPORT_OFF);
    }

    global $conexionWeb;
    if (!isset($conexionWeb) || !($conexionWeb instanceof mysqli) || $conexionWeb->connect_errno) {
        cron_linea('ERROR: no se pudo conectar a la base de datos web.');
        cron_cerrar_salida();
        exit(1);
    }

    cron_linea('OK: conexión TPV establecida.');
    cron_linea('OK: conexión web establecida.');

    require_once __DIR__ . '/functions_cron.php';
    cron_establecer_conexion($conexion);
    cron_establecer_conexion_web($conexionWeb);

    $origenTestCron = $esCli ? 'cronweb_ok' : 'cronweb_ventas_manual';
    $sqlTestCron = 'INSERT INTO test_cron (hora_insert, origen) VALUES (NOW(), ?)';
    $stmtTestCron = mysqli_prepare($conexion, $sqlTestCron);
    if ($stmtTestCron) {
        mysqli_stmt_bind_param($stmtTestCron, 's', $origenTestCron);
        mysqli_stmt_execute($stmtTestCron);
        mysqli_stmt_close($stmtTestCron);
    }

    $pasosVentasWeb = array(
        'verificar_productos_web_1.php',
        'verificar_productos_web_2.php',
        'verificar_productos_web_3.php',
        'verificar_productos_web_4.php',
        'verificar_productos_web_5.php',
        'verificar_productos_web_6.php',
    );

    foreach ($pasosVentasWeb as $archivo) {
        $ruta = __DIR__ . '/' . $archivo;
        cron_linea('  - Cargando ' . $archivo);
        require $ruta;
    }

    cron_linea('>> Fin: ventas_web');
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
