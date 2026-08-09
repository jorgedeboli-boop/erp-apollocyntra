<?php

/**
 * Bootstrap compartido para informes manuales con fecha.
 *
 * Uso HTTP/cURL:
 *   ?fecha=YYYY-MM-DD
 *   o POST fecha=YYYY-MM-DD
 *
 * Uso CLI:
 *   php informe_diario.php --fecha=YYYY-MM-DD
 *   php informe_diario.php 2026-07-20
 */

if (!defined('CRON_MANUAL_BOOTSTRAP')) {
    define('CRON_MANUAL_BOOTSTRAP', true);
}

if (!defined('CRON_DIR')) {
    define('CRON_DIR', realpath(__DIR__ . '/../../CRON'));
}

if (!defined('CRON_MANUAL_PASOS_DIR')) {
    define('CRON_MANUAL_PASOS_DIR', __DIR__ . '/pasos');
}

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
    function cron_iniciar_salida($titulo)
    {
        global $esCli;
        if ($esCli) {
            return;
        }
        header('Content-Type: text/html; charset=utf-8');
        echo '<!DOCTYPE html><html lang="es"><head><meta charset="utf-8"><title>'
            . htmlspecialchars($titulo, ENT_QUOTES, 'UTF-8')
            . '</title></head><body>';
        echo '<h1>' . htmlspecialchars($titulo, ENT_QUOTES, 'UTF-8') . '</h1>';
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

/**
 * Resuelve fecha YYYY-MM-DD desde CLI / GET / POST.
 *
 * @return string
 */
function cron_manual_resolver_fecha()
{
    global $esCli;
    $fecha = '';

    if ($esCli) {
        global $argv;
        if (!empty($argv) && is_array($argv)) {
            foreach ($argv as $arg) {
                if (!is_string($arg)) {
                    continue;
                }
                if (strpos($arg, '--fecha=') === 0) {
                    $fecha = substr($arg, 8);
                    break;
                }
                if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $arg)) {
                    $fecha = $arg;
                    break;
                }
            }
        }
    } else {
        if (isset($_GET['fecha'])) {
            $fecha = trim((string) $_GET['fecha']);
        } elseif (isset($_POST['fecha'])) {
            $fecha = trim((string) $_POST['fecha']);
        }
    }

    $fecha = trim($fecha);
    if ($fecha === '' || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $fecha)) {
        cron_linea('ERROR: falta o es inválida la fecha. Usa YYYY-MM-DD (parametro fecha).');
        cron_linea('Ejemplos:');
        cron_linea('  curl "…/parts/cron_manual_informes/informe_diario.php?fecha=2026-07-20"');
        cron_linea('  php parts/cron_manual_informes/informe_diario.php --fecha=2026-07-20');
        cron_cerrar_salida();
        exit(1);
    }

    $dt = DateTime::createFromFormat('Y-m-d', $fecha);
    $errores = DateTime::getLastErrors();
    $sinErroresFecha = ($errores === false)
        || (empty($errores['warning_count']) && empty($errores['error_count']));
    $ok = $dt && $dt->format('Y-m-d') === $fecha && $sinErroresFecha;

    if (!$ok) {
        cron_linea('ERROR: fecha no válida en el calendario: ' . $fecha);
        cron_cerrar_salida();
        exit(1);
    }

    return $fecha;
}

/**
 * Devuelve la ruta de un paso: primero pasos/ local si existe, si no CRON/.
 *
 * Importante: el require debe hacerse en el script principal (no dentro de una
 * función), para que $fecha_informe_today y el resto de variables compartan ámbito.
 *
 * @param string $archivo
 * @return string
 */
function cron_manual_ruta_paso($archivo)
{
    $local = CRON_MANUAL_PASOS_DIR . '/' . $archivo;
    if (is_file($local)) {
        return $local;
    }

    $remoto = CRON_DIR . '/' . $archivo;
    if (!is_file($remoto)) {
        throw new Exception('No se encuentra el paso: ' . $archivo);
    }

    return $remoto;
}

/**
 * Indica si el paso se carga desde la copia manual.
 *
 * @param string $archivo
 * @return bool
 */
function cron_manual_paso_es_local($archivo)
{
    return is_file(CRON_MANUAL_PASOS_DIR . '/' . $archivo);
}

/**
 * Prepara entorno CLI / HTTP y conexión.
 *
 * @param string $scriptRel
 * @return mysqli
 */
function cron_manual_preparar_entorno($scriptRel)
{
    global $esCli;

    if ($esCli) {
        $_SERVER['HTTP_HOST'] = $_SERVER['HTTP_HOST'] ?? 'localhost';
        $_SERVER['REQUEST_URI'] = $_SERVER['REQUEST_URI'] ?? $scriptRel;
        $_SERVER['REMOTE_ADDR'] = $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';
        $_SERVER['HTTP_USER_AGENT'] = $_SERVER['HTTP_USER_AGENT'] ?? 'cron-manual';
    }

    // Respeta el interruptor global de crons (sin modificar CRON/).
    require_once CRON_DIR . '/cron_state_guard.php';

    chdir(__DIR__ . '/../../include');
    require_once __DIR__ . '/../../include/functions.php';
    require_once CRON_DIR . '/functions_cron.php';

    $conexion = conectar_bd();
    if (!$conexion) {
        throw new Exception('No se pudo conectar a la base de datos.');
    }

    if (function_exists('mysqli_report')) {
        mysqli_report(MYSQLI_REPORT_OFF);
    }

    cron_establecer_conexion($conexion);

    return $conexion;
}
