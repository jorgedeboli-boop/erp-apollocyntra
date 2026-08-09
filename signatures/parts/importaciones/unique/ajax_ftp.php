<?php
require_once '../../../include/session.php';
require_once '../../../include/functions.php';

/**
 * Conexión FTP para importaciones.
 * NOTA: credenciales aquí son las que el usuario indicó para el servidor.
 */
function importaciones_ftp_connect() {
    $host = 'quintagracia-app.espacioseguro.com';
    $user = 'quintagracia';
    $pass = defined('DB_PASS') ? DB_PASS : '';

    if (!function_exists('ftp_connect')) {
        throw new Exception('FTP no disponible en este servidor (extensión ftp).');
    }

    $conn = function_exists('ftp_ssl_connect') ? @ftp_ssl_connect($host, 21, 20) : false;
    if (!$conn) {
        $conn = @ftp_connect($host, 21, 20);
    }
    if (!$conn) {
        throw new Exception('No se pudo conectar al FTP.');
    }

    if (!@ftp_login($conn, $user, $pass)) {
        @ftp_close($conn);
        throw new Exception('No se pudo autenticar en el FTP.');
    }

    @ftp_pasv($conn, true);
    return $conn;
}

function importaciones_ftp_ensure_dir($conn, $dir) {
    $dir = trim((string)$dir);
    if ($dir === '' || $dir === '/') return;

    $parts = array_values(array_filter(explode('/', $dir)));
    $path = '';
    foreach ($parts as $p) {
        $path .= '/' . $p;
        if (@ftp_chdir($conn, $path)) {
            @ftp_chdir($conn, '/');
            continue;
        }
        if (!@ftp_mkdir($conn, $path)) {
            // puede existir pero sin permisos de listar, reintento chdir
            if (!@ftp_chdir($conn, $path)) {
                throw new Exception('No se pudo crear/acceder al directorio FTP: ' . $path);
            }
            @ftp_chdir($conn, '/');
        }
    }
}

function importaciones_sanitize_sql_filename($name) {
    $base = basename((string)$name);
    $base = preg_replace('/[^A-Za-z0-9._-]/', '_', $base);
    if (!preg_match('/\.sql$/i', $base)) {
        throw new Exception('Nombre de fichero inválido. Solo se permiten .sql');
    }
    return $base;
}

?>
