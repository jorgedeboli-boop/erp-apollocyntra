<?php
/**
 * Conexión FTP reutilizable (config en include/config.php).
 */

if (!defined('FTP_HOST')) {
    require_once __DIR__ . '/config.php';
}

/**
 * Abre conexión FTP con las credenciales de config.php.
 *
 * @param bool $passive Usar modo pasivo (recomendado en hosting compartido)
 * @return resource|FTP\Connection|false
 */
function conectar_ftp($passive = true)
{
    if (!function_exists('ftp_connect')) {
        trigger_error('La extensión FTP de PHP no está disponible', E_USER_WARNING);
        return false;
    }

    $conn = @ftp_connect(FTP_HOST, (int) FTP_PORT, 30);
    if (!$conn) {
        return false;
    }

    if (!@ftp_login($conn, FTP_USER, FTP_PASS)) {
        @ftp_close($conn);
        return false;
    }

    if ($passive) {
        @ftp_pasv($conn, true);
    }

    $remoteDir = rtrim((string) FTP_REMOTE_DIR, '/');
    if ($remoteDir !== '') {
        if (!@ftp_chdir($conn, $remoteDir)) {
            @ftp_close($conn);
            return false;
        }
    }

    return $conn;
}

/**
 * Cierra una conexión FTP abierta con conectar_ftp().
 *
 * @param resource|FTP\Connection|null $conn
 * @return void
 */
function cerrar_ftp($conn)
{
    if ($conn === null || $conn === false) {
        return;
    }

    if (is_resource($conn) || (class_exists('FTP\\Connection', false) && $conn instanceof FTP\Connection)) {
        @ftp_close($conn);
    }
}

/**
 * Directorio remoto base configurado (sin barra final).
 *
 * @return string
 */
function ftp_remote_dir_base()
{
    return rtrim((string) FTP_REMOTE_DIR, '/');
}

/**
 * Construye ruta remota absoluta a partir de un segmento relativo.
 *
 * @param string $relativePath Ej: "gastos/2026/foto.jpg"
 * @return string
 */
function ftp_remote_path($relativePath)
{
    $relativePath = ltrim(str_replace('\\', '/', (string) $relativePath), '/');
    $base = ftp_remote_dir_base();

    if ($base === '') {
        return '/' . $relativePath;
    }

    return $base . '/' . $relativePath;
}

/**
 * Lista archivos (no directorios) de un directorio remoto.
 *
 * @param resource|FTP\Connection $conn
 * @param string $directorio
 * @return array<int, string>
 */
function ftp_listar_archivos($conn, $directorio = '.')
{
    $lista = @ftp_nlist($conn, $directorio);
    if (!is_array($lista)) {
        return array();
    }

    $archivos = array();
    foreach ($lista as $item) {
        $nombre = basename(str_replace('\\', '/', (string) $item));
        if ($nombre === '' || $nombre === '.' || $nombre === '..') {
            continue;
        }

        $rutaRemota = ($directorio === '.' || $directorio === './')
            ? $nombre
            : rtrim(str_replace('\\', '/', $directorio), '/') . '/' . $nombre;

        if (@ftp_size($conn, $rutaRemota) === -1) {
            continue;
        }

        $archivos[] = $nombre;
    }

    sort($archivos, SORT_STRING);
    return $archivos;
}

/**
 * Descarga un archivo remoto a una ruta local.
 *
 * @param resource|FTP\Connection $conn
 * @param string $remoteFile
 * @param string $localFile
 * @return bool
 */
function ftp_descargar_archivo($conn, $remoteFile, $localFile)
{
    return @ftp_get($conn, $localFile, $remoteFile, FTP_BINARY);
}

/**
 * Elimina un archivo remoto.
 *
 * @param resource|FTP\Connection $conn
 * @param string $remoteFile
 * @return bool
 */
function ftp_eliminar_archivo($conn, $remoteFile)
{
    return @ftp_delete($conn, $remoteFile);
}

/**
 * Mueve/renombra un archivo remoto.
 *
 * @param resource|FTP\Connection $conn
 * @param string $origen
 * @param string $destino
 * @return bool
 */
function ftp_mover_archivo($conn, $origen, $destino)
{
    return @ftp_rename($conn, $origen, $destino);
}

/**
 * Crea un directorio remoto si no existe.
 *
 * @param resource|FTP\Connection $conn
 * @param string $directorio
 * @return bool
 */
function ftp_crear_directorio($conn, $directorio)
{
    $directorio = trim(str_replace('\\', '/', (string) $directorio), '/');
    if ($directorio === '') {
        return true;
    }

    $pwd = @ftp_pwd($conn);
    $partes = explode('/', $directorio);
    $actual = '';

    foreach ($partes as $parte) {
        if ($parte === '') {
            continue;
        }
        $actual = ($actual === '') ? $parte : $actual . '/' . $parte;
        if (!@ftp_chdir($conn, $actual)) {
            if (!@ftp_mkdir($conn, $actual)) {
                if ($pwd) {
                    @ftp_chdir($conn, $pwd);
                }
                return false;
            }
            @ftp_chdir($conn, $actual);
        }
    }

    if ($pwd) {
        @ftp_chdir($conn, $pwd);
    }

    return true;
}
