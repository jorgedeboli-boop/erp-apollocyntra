<?php

/**
 * Cron cada 10 minutos: procesa facturas nuevas subidas por FTP en la raíz.
 *
 * Crontab ejemplo (cada 10 min):
 * 0,10,20,30,40,50 * * * * /usr/bin/php /ruta/al/proyecto/CRON/control_files_upload.php >> /ruta/logs/ftp_facturas.log 2>&1
 */

require_once __DIR__ . '/cron_state_guard.php';

$esCli = (PHP_SAPI === 'cli');

function cron_ftp_linea($mensaje)
{
    global $esCli;
    $linea = $mensaje . "\n";
    if ($esCli) {
        echo $linea;
        return;
    }
    echo htmlspecialchars($linea, ENT_QUOTES, 'UTF-8');
}

if (!$esCli) {
    header('Content-Type: text/html; charset=utf-8');
    echo '<!DOCTYPE html><html lang="es"><head><meta charset="utf-8"><title>CRON FTP facturas</title></head><body><pre>';
}

if ($esCli) {
    $_SERVER['HTTP_HOST'] = $_SERVER['HTTP_HOST'] ?? 'localhost';
    $_SERVER['REQUEST_URI'] = $_SERVER['REQUEST_URI'] ?? '/CRON/control_files_upload.php';
    $_SERVER['REMOTE_ADDR'] = $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';
    $_SERVER['HTTP_USER_AGENT'] = $_SERVER['HTTP_USER_AGENT'] ?? 'cron';
}

cron_ftp_linea('========================================');
cron_ftp_linea('CRON FTP facturas: ' . date('Y-m-d H:i:s'));
cron_ftp_linea('========================================');

$conexion = null;
$ftp = null;

try {
    chdir(__DIR__ . '/../include');
    require_once __DIR__ . '/../include/functions.php';
    require_once __DIR__ . '/../include/gemini.php';
    require_once __DIR__ . '/../camera/lib/imagenes_catalogo.php';
    require_once __DIR__ . '/../parts/gastos_pruebas/listar/ocr_helpers.php';
    require_once __DIR__ . '/helpers_factura_ftp.php';
    require_once __DIR__ . '/functions_cron.php';

    if (!$esCli && function_exists('environment_is_production') && environment_is_production()) {
        cron_ftp_linea('ERROR: ejecución manual no permitida en producción.');
        exit(1);
    }

    $conexion = conectar_bd();
    if (!$conexion) {
        throw new Exception('No se pudo conectar a la base de datos');
    }

    cron_establecer_conexion($conexion);
    cron_establecer_only_view(false);

    $usuarioId = 1;
    $sucursalId = 0;

    $ftp = conectar_ftp(true);
    if (!$ftp) {
        throw new Exception('No se pudo conectar al FTP');
    }

    cron_ftp_linea('OK: conexión FTP establecida en ' . FTP_HOST);

    $archivos = ftp_listar_archivos($ftp, '.');
    cron_ftp_linea('Archivos en raíz FTP: ' . count($archivos));

    $procesados = 0;
    $omitidos = 0;
    $errores = 0;
    $tempDir = sys_get_temp_dir() . '/tpv_ftp_' . getmypid();
    if (!is_dir($tempDir) && !mkdir($tempDir, 0755, true)) {
        throw new Exception('No se pudo crear directorio temporal');
    }

    foreach ($archivos as $nombreArchivo) {
        if (!cron_ftp_es_factura_permitida($nombreArchivo)) {
            cron_ftp_linea('  - Omitido (extensión no válida): ' . $nombreArchivo);
            continue;
        }

        $size = (int) @ftp_size($ftp, $nombreArchivo);
        if ($size <= 0) {
            cron_ftp_linea('  - Omitido (tamaño inválido): ' . $nombreArchivo);
            continue;
        }

        if (cron_ftp_ya_procesado($nombreArchivo, $size)) {
            $omitidos++;
            cron_ftp_linea('  - Ya procesado: ' . $nombreArchivo);
            continue;
        }

        cron_ftp_linea('>> Procesando: ' . $nombreArchivo . ' (' . $size . ' bytes)');

        $tempFile = $tempDir . '/' . basename($nombreArchivo);
        if (!ftp_descargar_archivo($ftp, $nombreArchivo, $tempFile)) {
            $errores++;
            cron_ftp_linea('  ERROR descargando: ' . $nombreArchivo);
            @unlink($tempFile);
            continue;
        }

        try {
            $resultado = cron_procesar_factura_ftp(
                $conexion,
                $tempFile,
                $nombreArchivo,
                $usuarioId,
                $sucursalId
            );

            if (!empty($resultado['success'])) {
                $procesados++;
                cron_ftp_marcar_procesado($nombreArchivo, $size, array(
                    'estado' => $resultado['estado_digitalizado'] ?? 'procesado',
                    'id_gasto' => (int) ($resultado['id_gasto'] ?? 0),
                    'id_digitalizacion' => (int) ($resultado['id_digitalizacion'] ?? 0),
                    'numero_factura' => (string) ($resultado['numero_factura'] ?? ''),
                ));

                if (cron_ftp_mover_a_carpeta($ftp, $nombreArchivo, 'procesados')) {
                    cron_ftp_linea('  OK gasto #' . (int) ($resultado['id_gasto'] ?? 0) . ' — movido a procesados/');
                } else {
                    ftp_eliminar_archivo($ftp, $nombreArchivo);
                    cron_ftp_linea('  OK gasto #' . (int) ($resultado['id_gasto'] ?? 0) . ' — eliminado de raíz FTP');
                }

                if (function_exists('registrar_tareas_cron')) {
                    registrar_tareas_cron(
                        'FTP factura procesada: ' . $nombreArchivo .
                        ' -> gasto ' . (int) ($resultado['id_gasto'] ?? 0)
                    );
                }
            } else {
                $errores++;
                $mensaje = (string) ($resultado['message'] ?? 'Error desconocido');
                cron_ftp_linea('  ERROR OCR/proceso: ' . $mensaje);

                cron_ftp_marcar_procesado($nombreArchivo, $size, array(
                    'estado' => $resultado['estado_digitalizado'] ?? 'noprocesado',
                    'id_gasto' => 0,
                    'id_digitalizacion' => (int) ($resultado['id_digitalizacion'] ?? 0),
                    'error' => substr($mensaje, 0, 200),
                ));

                if (cron_ftp_mover_a_carpeta($ftp, $nombreArchivo, 'errores')) {
                    cron_ftp_linea('  Movido a errores/');
                }
            }
        } catch (Throwable $e) {
            $errores++;
            cron_ftp_linea('  EXCEPCIÓN: ' . $e->getMessage());
            cron_ftp_mover_a_carpeta($ftp, $nombreArchivo, 'errores');
        } finally {
            if (is_file($tempFile)) {
                @unlink($tempFile);
            }
        }
    }

    if (is_dir($tempDir)) {
        @rmdir($tempDir);
    }

    cron_ftp_linea('----------------------------------------');
    cron_ftp_linea('Resumen: procesados=' . $procesados . ', omitidos=' . $omitidos . ', errores=' . $errores);
    cron_ftp_linea('Finalizado: ' . date('Y-m-d H:i:s'));

} catch (Throwable $e) {
    cron_ftp_linea('ERROR FATAL: ' . $e->getMessage());
    if (!$esCli) {
        echo '</pre></body></html>';
    }
    exit(1);
} finally {
    if ($ftp) {
        cerrar_ftp($ftp);
    }
    if ($conexion) {
        mysqli_close($conexion);
    }
}

if (!$esCli) {
    echo '</pre></body></html>';
}

exit(0);
