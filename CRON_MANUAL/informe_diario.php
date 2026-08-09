<?php

/**
 * CRON_MANUAL — informe diario (rango o un día).
 * Copia adaptada de CRON/informe_diario.php. NO modificar CRON/.
 *
 * CLI:
 *   php CRON_MANUAL/informe_diario.php
 *   php CRON_MANUAL/informe_diario.php --fecha=2025-01-15
 *   php CRON_MANUAL/informe_diario.php --desde=2024-12-30 --hasta=2026-08-05
 *
 * Navegador (no producción):
 *   /CRON_MANUAL/informe_diario.php?desde=2024-12-30&hasta=2026-08-05
 *   /CRON_MANUAL/informe_diario.php?fecha=2025-01-15
 *
 * Variables globales opcionales (si se incluye desde otro script):
 *   $cron_manual_fecha_desde, $cron_manual_fecha_hasta, $cron_manual_fecha
 */

require_once __DIR__ . '/cron_state_guard.php';

$esCli = (PHP_SAPI === 'cli');

if (!$esCli) {
    ini_set('display_errors', '1');
    error_reporting(E_ALL);
}

if (!function_exists('cron_imprimir')) {
    function cron_imprimir($mensaje)
    {
        global $esCli;
        if ($esCli) {
            echo $mensaje;
            return;
        }
        echo htmlspecialchars($mensaje, ENT_QUOTES, 'UTF-8');
    }
}

if (!function_exists('cron_linea')) {
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
}

if (!function_exists('cron_iniciar_salida')) {
    function cron_iniciar_salida()
    {
        global $esCli;
        if ($esCli) {
            return;
        }
        header('Content-Type: text/html; charset=utf-8');
        echo '<!DOCTYPE html><html lang="es"><head><meta charset="utf-8"><title>Informe diario manual</title></head><body>';
        echo '<h1>CRON_MANUAL — informe diario</h1>';
        echo '<pre style="font-family:monospace;font-size:13px;line-height:1.45;">';
    }
}

if (!function_exists('cron_cerrar_salida')) {
    function cron_cerrar_salida()
    {
        global $esCli;
        if ($esCli) {
            return;
        }
        echo '</pre></body></html>';
    }
}

if (!function_exists('cron_manual_validar_fecha')) {
    function cron_manual_validar_fecha($fecha)
    {
        $fecha = trim((string) $fecha);
        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $fecha)) {
            return '';
        }
        $dt = DateTime::createFromFormat('Y-m-d', $fecha);
        if (!$dt || $dt->format('Y-m-d') !== $fecha) {
            return '';
        }

        return $fecha;
    }
}

if (!function_exists('cron_manual_resolver_rango')) {
    /**
     * @return array{0:string,1:string}|null [desde, hasta]
     */
    function cron_manual_resolver_rango()
    {
        global $cron_manual_fecha_desde, $cron_manual_fecha_hasta, $cron_manual_fecha, $argv;

        $desde = '';
        $hasta = '';
        $fechaUnica = '';

        if (isset($cron_manual_fecha_desde) || isset($cron_manual_fecha_hasta) || isset($cron_manual_fecha)) {
            $desde = isset($cron_manual_fecha_desde) ? (string) $cron_manual_fecha_desde : '';
            $hasta = isset($cron_manual_fecha_hasta) ? (string) $cron_manual_fecha_hasta : '';
            $fechaUnica = isset($cron_manual_fecha) ? (string) $cron_manual_fecha : '';
        } elseif (PHP_SAPI === 'cli') {
            $args = isset($argv) && is_array($argv) ? array_slice($argv, 1) : array();
            foreach ($args as $arg) {
                if (preg_match('/^--desde=(\d{4}-\d{2}-\d{2})$/', $arg, $m)) {
                    $desde = $m[1];
                } elseif (preg_match('/^--hasta=(\d{4}-\d{2}-\d{2})$/', $arg, $m)) {
                    $hasta = $m[1];
                } elseif (preg_match('/^--fecha=(\d{4}-\d{2}-\d{2})$/', $arg, $m)) {
                    $fechaUnica = $m[1];
                }
            }
        } else {
            $desde = isset($_GET['desde']) ? (string) $_GET['desde'] : '';
            $hasta = isset($_GET['hasta']) ? (string) $_GET['hasta'] : '';
            $fechaUnica = isset($_GET['fecha']) ? (string) $_GET['fecha'] : '';
        }

        if ($fechaUnica !== '') {
            $f = cron_manual_validar_fecha($fechaUnica);
            if ($f === '') {
                return null;
            }
            return array($f, $f);
        }

        if ($desde === '' && $hasta === '') {
            $hoy = date('Y-m-d');
            return array($hoy, $hoy);
        }

        $desde = cron_manual_validar_fecha($desde !== '' ? $desde : $hasta);
        $hasta = cron_manual_validar_fecha($hasta !== '' ? $hasta : $desde);
        if ($desde === '' || $hasta === '') {
            return null;
        }
        if ($desde > $hasta) {
            $tmp = $desde;
            $desde = $hasta;
            $hasta = $tmp;
        }

        return array($desde, $hasta);
    }
}

if (!function_exists('cron_manual_procesar_dia')) {
    /**
     * Ejecuta la misma cadena de pasos que CRON/informe_diario.php para una fecha concreta.
     *
     * @param string $fecha Y-m-d
     * @return bool
     */
    function cron_manual_procesar_dia($fecha)
    {
        $fecha = cron_manual_validar_fecha($fecha);
        if ($fecha === '') {
            cron_linea('ERROR: fecha inválida.');
            return false;
        }

        global $fecha_informe_today, $numeroSemana;
        $fecha_informe_today = $fecha;

        cron_linea('======== Día ' . $fecha_informe_today . ' ========');

        $pasosInforme = array(
            'calculo_semana_numero.php',
            'generar-informe.php',
            'informes-compras-oro.php',
            'informes-compras-plata.php',
            'informes-empenyos-global.php',
            'informes-empenyos-oro.php',
            'informes-empenyos-plata.php',
            'informes-empenyos-retirados.php',
            'informes-empenyos-vencidos.php',
            'informes-empenyos-perdidos.php',
            'informes-empenyos-renovaciones.php',
            'informes-lotes-intervenidos.php',
            'informes-caja.php',
            'informes-ajustes_de_lotes.php',
            'informes-operaciones-tarjetas.php',
            'informes-operaciones-transferencias.php',
            'informes-operaciones-bizum.php',
            'informes-stock-valorizado.php',
            'informes-ventas.php',
            'informes-ventas-plazos.php',
            'informes-ventas-web.php',
            'informes_ventas_forma_pago.php',
            'informes-devoluciones.php',
            'informes-gastos.php',
            'informes-precio-oro.php',
            'informes-calculo-totales.php',
            'finalizar-informe.php',
        );

        foreach ($pasosInforme as $archivo) {
            $ruta = __DIR__ . '/' . $archivo;
            if (!is_file($ruta)) {
                cron_linea('  - ERROR: no existe ' . $archivo);
                return false;
            }
            cron_linea('  - Cargando ' . $archivo);
            require $ruta;
        }

        cron_linea('>> Fin día: ' . $fecha);
        return true;
    }
}

// --- Ejecución standalone (no cuando se incluye solo para cargar funciones) ---
if (defined('CRON_MANUAL_SOLO_FUNCIONES') && CRON_MANUAL_SOLO_FUNCIONES) {
    return;
}

if ($esCli) {
    $_SERVER['HTTP_HOST'] = $_SERVER['HTTP_HOST'] ?? 'localhost';
    $_SERVER['REQUEST_URI'] = $_SERVER['REQUEST_URI'] ?? '/CRON_MANUAL/informe_diario.php';
    $_SERVER['REMOTE_ADDR'] = $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';
    $_SERVER['HTTP_USER_AGENT'] = $_SERVER['HTTP_USER_AGENT'] ?? 'cron-manual';
}

cron_iniciar_salida();
cron_linea($esCli ? 'Entorno: CLI (CRON_MANUAL)' : 'Entorno: navegador (CRON_MANUAL)');
cron_linea('>> Inicio: informe_diario (manual)');

$conexion = null;

try {
    chdir(__DIR__ . '/../include');
    require_once __DIR__ . '/../include/functions.php';

    if (!$esCli && function_exists('environment_is_production') && environment_is_production()) {
        cron_linea('ERROR: la ejecución manual por navegador no está permitida en producción.');
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

    $rango = cron_manual_resolver_rango();
    if ($rango === null) {
        cron_linea('ERROR: rango de fechas inválido. Usa --desde=YYYY-MM-DD --hasta=YYYY-MM-DD o --fecha=YYYY-MM-DD');
        cron_cerrar_salida();
        exit(1);
    }

    list($fechaDesde, $fechaHasta) = $rango;
    cron_linea('Rango: ' . $fechaDesde . ' → ' . $fechaHasta);

    $cursor = new DateTime($fechaDesde);
    $fin = new DateTime($fechaHasta);
    $diasOk = 0;
    $diasKo = 0;

    while ($cursor <= $fin) {
        $fechaDia = $cursor->format('Y-m-d');
        if (cron_manual_procesar_dia($fechaDia)) {
            $diasOk++;
        } else {
            $diasKo++;
        }
        $cursor->modify('+1 day');
    }

    cron_linea('>> Fin: informe_diario (manual) | ok=' . $diasOk . ' | error=' . $diasKo);
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
