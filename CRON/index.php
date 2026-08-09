<?php

/**
 * Cron principal del TPV.
 * - Producción: servidor Linux todos los días a las 00:00 (CLI).
 * - Pruebas: también se puede abrir en el navegador (solo si ENVIRONMENT !== 'production').
 *
 * Ejemplo crontab:
 * 0 0 * * * /usr/bin/php /ruta/al/proyecto/CRON/index.php >> /ruta/logs/cron.log 2>&1
 */

require_once __DIR__ . '/cron_state_guard.php';

$esCli = (PHP_SAPI === 'cli');
$only_view = false;

if (!$esCli) {
    ini_set('display_errors', '1');
    error_reporting(E_ALL);
    // Solo consulta en navegador: cambiar a false para permitir escrituras desde el navegador.
    $only_view = true;
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
    echo '<!DOCTYPE html><html lang="es"><head><meta charset="utf-8"><title>CRON TPV</title></head><body>';
    echo '<h1>CRON — ejecución manual</h1>';
    echo '<pre style="font-family:monospace;font-size:13px;line-height:1.45;">';
    echo 'Fecha y hora de inicio: ' . date('Y-m-d H:i:s') . '<br>';
}

function cron_cerrar_salida()
{
    global $esCli;
    if ($esCli) {
        return;
    }
    echo '</pre></body></html>';
}

// En CLI (cron) $_SERVER suele venir vacío; config.php lo utiliza.
if ($esCli) {
    $_SERVER['HTTP_HOST'] = $_SERVER['HTTP_HOST'] ?? 'localhost';
    $_SERVER['REQUEST_URI'] = $_SERVER['REQUEST_URI'] ?? '/CRON/index.php';
    $_SERVER['REMOTE_ADDR'] = $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';
    $_SERVER['HTTP_USER_AGENT'] = $_SERVER['HTTP_USER_AGENT'] ?? 'cron';
}

cron_iniciar_salida();
cron_linea('========================================');
cron_linea('Ejecutando cron con la fecha y hora: ' . date('Y-m-d H:i:s'));
cron_linea('========================================');
cron_linea($esCli ? 'Entorno: CLI (cron)' : 'Entorno: navegador (prueba manual)');
cron_linea($only_view ? 'Modo: SOLO VISTA (sin INSERT/UPDATE/DELETE)' : 'Modo: EJECUCION (lectura y escritura)');

$conexion = null;

try {
    // functions.php hace require de config.php con ruta relativa (desde include/).
    chdir(__DIR__ . '/../include');
    require_once __DIR__ . '/../include/functions.php';

    $numeroSemanaEnvio = numeroSemanaEnvio();
    if (is_array($numeroSemanaEnvio)) {
        cron_linea(
            'Numero semana envio: ' . $numeroSemanaEnvio['numero_semana'] .
            ' (' . $numeroSemanaEnvio['anyo_listado'] . ')'
        );
    } else {
        cron_linea('Numero semana envio: no disponible');
    }

    if (!$esCli && function_exists('environment_is_production') && environment_is_production()) {
        cron_linea('ERROR: la ejecución manual del cron no está permitida en producción.');
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
    cron_establecer_only_view($only_view);

    require_once __DIR__ . '/sucursales.php';
    $sucursalesActivas = cron_obtener_sucursales_activas($conexion);

    cron_linea('Sucursales activas (estado_tienda = habilitada): ' . count($sucursalesActivas));
    foreach ($sucursalesActivas as $sucursal) {
        $id = isset($sucursal['id_sucursal']) ? (int) $sucursal['id_sucursal'] : 0;
        $nombre = isset($sucursal['nombre_sucursal']) ? $sucursal['nombre_sucursal'] : '';
        cron_linea('  - Sucursal ' . $id . ': ' . $nombre);
    }

    require_once __DIR__ . '/lotes_liberados.php';
    cron_lotes_liberados($conexion, $sucursalesActivas);

    require_once __DIR__ . '/update_historico_empenos_vencidos_biciesto.php';
    cron_historico_empenos_vencidos_biciesto($conexion, $sucursalesActivas);

    require_once __DIR__ . '/update_historico_empenos_vencidos.php';
    cron_historico_empenos_vencidos($conexion, $sucursalesActivas);

    //require_once __DIR__ . '/empenos_perdidos.php';
    //cron_empenos_perdidos($conexion, $sucursalesActivas, $numeroSemanaEnvio);

    require_once __DIR__ . '/lotes_para_enviar.php';
    cron_lotes_para_enviar($conexion, $sucursalesActivas, $numeroSemanaEnvio);

    require_once __DIR__ . '/apertura_de_caja.php';
    cron_apertura_de_caja($conexion, $sucursalesActivas);

    require_once __DIR__ . '/generar_gastos_variables.php';
    cron_generar_gastos_variables($conexion);

    $sucursalesSmsEmpeno = cron_obtener_sucursales_sms_empeno($conexion);
    require_once __DIR__ . '/enviar_sms_empeno_por_vencer.php';
    cron_enviar_sms_empeno_por_vencer($conexion, $sucursalesSmsEmpeno);

    // AQUI CUANDO FINALIZA EL CRON INSERTAMOS
    $descripcionEvento = 'CRON FINALIZADO';
    registrar_tareas_cron($descripcionEvento);

    require_once __DIR__ . '/borrar_registros_cron.php';
    cron_borrar_registros_cron($conexion);

} catch (Exception $e) {
    cron_linea('ERROR: ' . $e->getMessage());
    cron_cerrar_salida();
    exit(1);
} catch (Error $e) {
    cron_linea('ERROR: ' . $e->getMessage());
    cron_cerrar_salida();
    exit(1);
}

cron_linea('Cron finalizado con la fecha y hora: ' . date('Y-m-d H:i:s'));
cron_cerrar_salida();
