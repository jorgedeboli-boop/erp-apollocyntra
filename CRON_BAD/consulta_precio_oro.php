<?php
declare(strict_types=1);

/**
 * Entrada para consultar el precio del oro (cron o prueba manual).
 *
 * Cron (hora del servidor):
 *   15 9 * * 1-5 /usr/bin/php /ruta/al/proyecto/CRON/consulta_precio_oro.php
 *
 * Prueba manual en navegador:
 *   /CRON/consulta_precio_oro.php?forzar=1
 */

$esCli = (PHP_SAPI === 'cli');

if (!$esCli) {
    ob_start();
}

$_SERVER['HTTP_HOST'] = $_SERVER['HTTP_HOST'] ?? 'localhost';
$_SERVER['REQUEST_URI'] = $_SERVER['REQUEST_URI'] ?? '/CRON/consulta_precio_oro.php';
$_SERVER['REMOTE_ADDR'] = $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';
$_SERVER['HTTP_USER_AGENT'] = $_SERVER['HTTP_USER_AGENT'] ?? 'cron';

require_once __DIR__ . '/../include/functions.php';
require_once __DIR__ . '/../parts/precio_oro/unique/consulta_precio_oro.php';

if (!$esCli) {
    ob_end_clean();
}

$forzar = in_array('--forzar', $argv ?? [], true)
    || (isset($_GET['forzar']) && $_GET['forzar'] !== '0' && $_GET['forzar'] !== 'false');

$conexion = conectar_bd();
if (!$conexion) {
    $mensaje = 'No se pudo conectar a la base de datos.';
    if ($esCli) {
        fwrite(STDERR, "Error: {$mensaje}\n");
        exit(1);
    }
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(['ok' => false, 'error' => $mensaje], JSON_UNESCAPED_UNICODE);
    exit;
}

try {
    $resultado = consulta_precio_oro_ejecutar($conexion, $forzar);
} catch (Throwable $e) {
    mysqli_close($conexion);
    if ($esCli) {
        fwrite(STDERR, 'Error en consulta_precio_oro: ' . $e->getMessage() . "\n");
        exit(1);
    }
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(['ok' => false, 'error' => $e->getMessage()], JSON_UNESCAPED_UNICODE);
    exit;
}

mysqli_close($conexion);

if ($esCli) {
    if (!empty($resultado['skipped'])) {
        echo 'SKIP: ' . ($resultado['motivo'] ?? 'Ejecución omitida.') . "\n";
        exit(0);
    }
    if (empty($resultado['ok'])) {
        fwrite(STDERR, 'Error: ' . ($resultado['error'] ?? 'Error desconocido.') . "\n");
        exit(1);
    }
    $datos = $resultado['datos'] ?? [];
    echo 'OK: consulta_precio_oro (id=' . ($resultado['id'] ?? 0)
        . ', gramo_24k=' . number_format((float) ($datos['precio_gramo_24k'] ?? 0), 4, '.', '')
        . " EUR)\n";
    exit(0);
}

header('Content-Type: application/json; charset=utf-8');
echo json_encode($resultado, JSON_UNESCAPED_UNICODE);
exit;
