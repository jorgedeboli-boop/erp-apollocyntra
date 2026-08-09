<?php

/**
 * Cron independiente: informe semanal.
 * Ejecución: servidor Linux (CLI) — CRON/informe_semanal.php
 *
 * Ejemplo crontab:
 * 0 7 * * 1 /usr/bin/php /ruta/al/proyecto/CRON/informe_semanal.php >> /ruta/logs/informe_semanal.log 2>&1
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
    echo '<!DOCTYPE html><html lang="es"><head><meta charset="utf-8"><title>Informe semanal</title></head><body>';
    echo '<h1>CRON — informe semanal</h1>';
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
    $_SERVER['REQUEST_URI'] = $_SERVER['REQUEST_URI'] ?? '/CRON/informe_semanal.php';
    $_SERVER['REMOTE_ADDR'] = $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';
    $_SERVER['HTTP_USER_AGENT'] = $_SERVER['HTTP_USER_AGENT'] ?? 'cron';
}

cron_iniciar_salida();
cron_linea($esCli ? 'Entorno: CLI (cron)' : 'Entorno: navegador (prueba manual)');
cron_linea('>> Inicio: informe_semanal');

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

    $pasosInforme = array(
        'consultar-semana-inicio.php',
        'generar-informe_semanal.php',
        'informes-semanal-caja.php',
        'informes-semanal-ajustes_de_lotes.php',
        'informes-semanal-operaciones-tarjetas.php',
        'informes-semanal-operaciones-transferencias.php',
        'informes-semanal-operaciones-bizum.php',
        'informes-semanal-compras-oro.php',
        'informes-semanal-compras-plata.php',
        'informes-semanal-empenyos-global.php',
        'informes-semanal-empenyos-oro.php',
        'informes-semanal-empenyos-plata.php',
        'informes-semanal-empenyos-retirados.php',
        'informes-semanal-empenyos-vencidos.php',
        'informes-semanal-empenyos-perdidos.php',
        'informes-semanal-empenyos-renovaciones.php',
        'informes-semanal-beneficios-empenyos.php',
        'informes-semanal-lotes-intervenidos.php',
        'informes-semanal-stock-valorizado.php',
        'informes-semanal-beneficio-oro-fundido.php',
        'informes-semanal-beneficio-plata-fundido.php',
        'informes-semanal-beneficio-fundido-total.php',
        'informes-semanal-calculo-coste-articulos-venta.php',
        'informes-semanal-beneficio-articulos-venta.php',
        'informes-semanal-ventas.php',
        'informes-semanal-ventas-plazos.php',
        'informes-semanal-ventas-web.php',
        'informes-semanal-ventas-forma-pago.php',
        'informes-semanal-devoluciones.php',
        'informe-semanal-calcular-beneficio.php',
        'informe-semanal-calcular-ranking-tiendas.php',
        'finalizar-informe-semanal.php',
    );
    foreach ($pasosInforme as $archivo) {
        $ruta = __DIR__ . '/' . $archivo;
        cron_linea('  - Cargando ' . $archivo);
        require $ruta;
    }

    cron_linea('>> Fin: informe_semanal');
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
