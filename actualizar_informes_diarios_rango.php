<?php

/**
 * Recalcula todos los informes diarios en un rango de fechas (valores + ultima_actualizacion).
 * Usa CRON_MANUAL (copia de los pasos diarios de CRON, sin filtrar por estado_informe='abierto').
 *
 * Por defecto: 2024-12-30 → hoy.
 *
 * CLI:
 *   php actualizar_informes_diarios_rango.php
 *   php actualizar_informes_diarios_rango.php --desde=2024-12-30 --hasta=2026-08-05
 *   php actualizar_informes_diarios_rango.php --ejecutar
 *
 * Navegador (requiere sesión; no producción):
 *   /actualizar_informes_diarios_rango.php
 *   /actualizar_informes_diarios_rango.php?ejecutar=1&desde=2024-12-30&hasta=2026-08-05
 *
 * Sin --ejecutar / ejecutar=1 solo muestra el rango previsto (modo vista).
 */

$esCli = (PHP_SAPI === 'cli');

$fechaDesdeDefault = '2024-12-30';
$fechaHastaDefault = date('Y-m-d');
$fechaDesde = $fechaDesdeDefault;
$fechaHasta = $fechaHastaDefault;
$ejecutar = false;

if ($esCli) {
    $_SERVER['HTTP_HOST'] = $_SERVER['HTTP_HOST'] ?? 'localhost';
    $_SERVER['REQUEST_URI'] = $_SERVER['REQUEST_URI'] ?? '/actualizar_informes_diarios_rango.php';
    $_SERVER['REMOTE_ADDR'] = $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';
    $_SERVER['HTTP_USER_AGENT'] = $_SERVER['HTTP_USER_AGENT'] ?? 'cli-actualizar-informes-diarios';

    foreach (array_slice($argv, 1) as $arg) {
        if ($arg === '--ejecutar' || $arg === '--execute') {
            $ejecutar = true;
            continue;
        }
        if (preg_match('/^--desde=(\d{4}-\d{2}-\d{2})$/', $arg, $m)) {
            $fechaDesde = $m[1];
            continue;
        }
        if (preg_match('/^--hasta=(\d{4}-\d{2}-\d{2})$/', $arg, $m)) {
            $fechaHasta = $m[1];
            continue;
        }
        if ($arg === '--help' || $arg === '-h') {
            echo "Uso:\n";
            echo "  php actualizar_informes_diarios_rango.php [--ejecutar] [--desde=YYYY-MM-DD] [--hasta=YYYY-MM-DD]\n";
            echo "Por defecto: {$fechaDesdeDefault} → hoy\n";
            exit(0);
        }
        fwrite(STDERR, "Argumento no reconocido: {$arg}\n");
        exit(1);
    }
} else {
    require_once __DIR__ . '/include/session.php';

    if (empty($usuario_id) || (int) $usuario_id <= 0) {
        http_response_code(401);
        echo 'No autorizado.';
        exit;
    }

    header('Content-Type: text/html; charset=utf-8');
    echo '<!DOCTYPE html><html lang="es"><head><meta charset="utf-8"><title>Actualizar informes diarios</title></head><body>';
    echo '<h1>Actualizar informes diarios (rango)</h1><pre style="font-family:monospace;font-size:13px;line-height:1.45;">';

    $ejecutar = isset($_GET['ejecutar']) && (string) $_GET['ejecutar'] === '1';
    if (!empty($_GET['desde'])) {
        $fechaDesde = (string) $_GET['desde'];
    }
    if (!empty($_GET['hasta'])) {
        $fechaHasta = (string) $_GET['hasta'];
    }
}

function aid_rango_imprimir($msg)
{
    global $esCli;
    if ($esCli) {
        echo $msg . "\n";
        return;
    }
    echo htmlspecialchars($msg, ENT_QUOTES, 'UTF-8') . "\n";
    if (ob_get_level() > 0) {
        ob_flush();
    }
    flush();
}

function aid_rango_validar_fecha($fecha)
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

$fechaDesde = aid_rango_validar_fecha($fechaDesde);
$fechaHasta = aid_rango_validar_fecha($fechaHasta);

if ($fechaDesde === '' || $fechaHasta === '') {
    aid_rango_imprimir('ERROR: fechas inválidas.');
    if (!$esCli) {
        echo '</pre></body></html>';
    }
    exit(1);
}

if ($fechaDesde > $fechaHasta) {
    $tmp = $fechaDesde;
    $fechaDesde = $fechaHasta;
    $fechaHasta = $tmp;
}

$inicio = new DateTime($fechaDesde);
$fin = new DateTime($fechaHasta);
$numDias = (int) $inicio->diff($fin)->days + 1;

aid_rango_imprimir('Rango: ' . $fechaDesde . ' → ' . $fechaHasta . ' (' . $numDias . ' días)');
aid_rango_imprimir('Motor: CRON_MANUAL (sin filtro estado_informe=abierto)');

if (!$ejecutar) {
    aid_rango_imprimir('Modo vista: no se ha ejecutado nada.');
    aid_rango_imprimir($esCli
        ? 'Para ejecutar: php actualizar_informes_diarios_rango.php --ejecutar'
        : 'Para ejecutar: añade ?ejecutar=1 a la URL');
    if (!$esCli) {
        echo '</pre></body></html>';
    }
    exit(0);
}

try {
    if ($esCli) {
        require_once __DIR__ . '/include/config.php';
        require_once __DIR__ . '/include/functions.php';
    } else {
        require_once __DIR__ . '/include/functions.php';
        if (function_exists('environment_is_production') && environment_is_production()) {
            aid_rango_imprimir('ERROR: ejecución por navegador no permitida en producción.');
            echo '</pre></body></html>';
            exit(1);
        }
    }

    $conexion = conectar_bd();
    if (!$conexion) {
        throw new Exception('No se pudo conectar a la base de datos.');
    }

    // Cargar helpers de CRON_MANUAL sin ejecutar el bucle standalone
    define('CRON_MANUAL_SOLO_FUNCIONES', true);
    require_once __DIR__ . '/CRON_MANUAL/informe_diario.php';
    require_once __DIR__ . '/CRON_MANUAL/functions_cron.php';
    cron_establecer_conexion($conexion);

    aid_rango_imprimir('OK: conexión lista. Recalculando…');

    $cursor = new DateTime($fechaDesde);
    $finLoop = new DateTime($fechaHasta);
    $diasOk = 0;
    $diasKo = 0;

    while ($cursor <= $finLoop) {
        $fechaDia = $cursor->format('Y-m-d');
        aid_rango_imprimir('--- ' . $fechaDia . ' ---');
        if (cron_manual_procesar_dia($fechaDia)) {
            $diasOk++;
        } else {
            $diasKo++;
        }
        $cursor->modify('+1 day');
    }

    aid_rango_imprimir('Fin. Días OK=' . $diasOk . ' | errores=' . $diasKo);
    mysqli_close($conexion);
} catch (Throwable $e) {
    aid_rango_imprimir('ERROR: ' . $e->getMessage());
    if (!$esCli) {
        echo '</pre></body></html>';
    }
    exit(1);
}

if (!$esCli) {
    echo '</pre></body></html>';
}
