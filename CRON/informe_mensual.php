<?php

/**
 * Cron independiente: informe mensual.
 * Ejecución: servidor Linux (CLI) — CRON/informe_mensual.php
 *
 * Ejemplo crontab:
 * 0 8 1 * * /usr/bin/php /ruta/al/proyecto/CRON/informe_mensual.php >> /ruta/logs/informe_mensual.log 2>&1
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
    echo '<!DOCTYPE html><html lang="es"><head><meta charset="utf-8"><title>Informe mensual</title></head><body>';
    echo '<h1>CRON — informe mensual</h1>';
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
    $_SERVER['REQUEST_URI'] = $_SERVER['REQUEST_URI'] ?? '/CRON/informe_mensual.php';
    $_SERVER['REMOTE_ADDR'] = $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';
    $_SERVER['HTTP_USER_AGENT'] = $_SERVER['HTTP_USER_AGENT'] ?? 'cron';
}

cron_iniciar_salida();
cron_linea($esCli ? 'Entorno: CLI (cron)' : 'Entorno: navegador (prueba manual)');
cron_linea('>> Inicio: informe_mensual');

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
        'consultar-mes-inicio.php',
        'generar-informe_mensual.php',
        'informes-mensual-caja.php',
        'informes-mensual-ajustes_de_lotes.php',
        'informes-mensual-operaciones-tarjetas.php',
        'informes-mensual-operaciones-transferencias.php',
        'informes-mensual-operaciones-bizum.php',
        'informes-mensual-compras-oro.php',
        'informes-mensual-compras-plata.php',
        'informes-mensual-empenyos-global.php',
        'informes-mensual-empenyos-oro.php',
        'informes-mensual-empenyos-plata.php',
        'informes-mensual-empenyos-retirados.php',
        'informes-mensual-empenyos-vencidos.php',
        'informes-mensual-empenyos-perdidos.php',
        'informes-mensual-empenyos-renovaciones.php',
        'informes-mensual-beneficios-empenyos.php',
        'informes-mensual-lotes-intervenidos.php',
        'informes-mensual-stock-valorizado.php',
        'informes-mensual-beneficio-oro-fundido.php',
        'informes-mensual-beneficio-plata-fundido.php',
        'informes-mensual-beneficio-fundido-total.php',
        'informes-mensual-calculo-coste-articulos-venta.php',
        'informes-mensual-beneficio-articulos-venta.php',
        'informes-mensual-ventas.php',
        'informes-mensual-ventas-plazos.php',
        'informes-mensual-ventas-web.php',
        'informes-mensual-ventas-forma-pago.php',
        'informes-mensual-devoluciones.php',
        'informes-mensual-gasto.php',
        'informe-mensual-calcular-beneficio.php',
        'informe-mensual-calcular-ranking-tiendas.php',
        'finalizar-informe-mensual.php',
    );

    foreach ($pasosInforme as $archivo) {
        $ruta = __DIR__ . '/' . $archivo;
        cron_linea('  - Cargando ' . $archivo);
        require $ruta;
    }

    cron_linea('>> Fin: informe_mensual');
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
