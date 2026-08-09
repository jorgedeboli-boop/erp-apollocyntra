<?php
/**
 * Utilidades para scripts de migración de larga duración (sesión + salida).
 */

function migracion_preparar_ejecucion_larga()
{
    if (session_status() === PHP_SESSION_ACTIVE) {
        if (isset($_SESSION['usuario_autenticado']) && $_SESSION['usuario_autenticado'] === true) {
            $_SESSION['usuario_login_time'] = time();
        }
        session_write_close();
    }

    set_time_limit(0);
    ini_set('max_execution_time', '0');
    ini_set('memory_limit', '512M');
    @ignore_user_abort(true);

    if (function_exists('apache_setenv')) {
        @apache_setenv('no-gzip', '1');
    }
    @ini_set('zlib.output_compression', '0');

    while (ob_get_level() > 0) {
        @ob_end_flush();
    }
    @ob_implicit_flush(true);
}

function migracion_refrescar_sesion()
{
    if (session_status() === PHP_SESSION_ACTIVE) {
        session_write_close();
    }

    session_name(SESSION_NAME);
    session_start();

    if (!isset($_SESSION['usuario_autenticado']) || $_SESSION['usuario_autenticado'] !== true) {
        session_write_close();
        return false;
    }

    $_SESSION['usuario_login_time'] = time();
    session_write_close();

    return true;
}

function migracion_flush_salida($linea = '')
{
    if ($linea !== '') {
        echo $linea;
        if (substr($linea, -1) !== "\n") {
            echo "\n";
        }
    }

    if (function_exists('ob_flush')) {
        @ob_flush();
    }
    @flush();
}

function migracion_iniciar_respuesta_http_larga()
{
    header('Cache-Control: no-cache, no-store, must-revalidate');
    header('X-Accel-Buffering: no');

    if (!headers_sent()) {
        echo str_repeat(' ', 2048) . "\n";
        migracion_flush_salida();
    }
}

/**
 * @param bool  $json
 * @param bool  $ok
 * @param array $datos
 */
function migracion_responder_paso($json, $ok, $datos = array())
{
    $payload = array_merge(array('ok' => $ok), $datos);

    if ($json) {
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($payload, JSON_UNESCAPED_UNICODE);
        exit($ok ? 0 : 1);
    }

    if (!$ok) {
        echo 'ERROR: ' . (isset($datos['message']) ? $datos['message'] : 'Error en el paso') . "\n";
        exit(1);
    }

    if (isset($datos['message'])) {
        echo $datos['message'] . "\n";
    }
    exit(0);
}
