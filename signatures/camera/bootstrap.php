<?php
/**
 * Raíz del módulo cámara / QR móvil.
 * Ajusta CAMERA_HTTP_BASE si la app no está en el servidor en /camera.
 */
if (!defined('CAMERA_ROOT')) {
    define('CAMERA_ROOT', __DIR__);
}

if (!defined('CAMERA_HTTP_BASE')) {
    define('CAMERA_HTTP_BASE', '/camera');
}

/**
 * URL absoluta de la página de captura (index.php) para los códigos QR.
 */
function camera_capture_url_absolute(): string
{
    $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
    $https = !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off';
    $scheme = $https ? 'https' : 'http';
    $base = rtrim(str_replace('\\', '/', CAMERA_HTTP_BASE), '/');
    return $scheme . '://' . $host . $base . '/index.php';
}

/**
 * Config para window.CAMERA_QR en el TPV (endpoints relativos al origen del sitio).
 *
 * @param array $overrides p. ej. ['callbacks' => [...]] solo en JS; aquí rutas y capturePageBase
 */
function camera_qr_default_js_config(array $overrides = []): array
{
    $base = rtrim(str_replace('\\', '/', CAMERA_HTTP_BASE), '/');
    $defaults = [
        'capturePageBase' => camera_capture_url_absolute(),
        'endpoints' => [
            'guardarToken' => $base . '/api/guardar_token.php',
            'consultarToken' => $base . '/api/consultar_token.php',
            'borrarToken' => $base . '/api/borrar_token.php',
        ],
        // Más rápido para que cliente/lote reaccionen antes al guardar la foto.
        'pollIntervalMs' => 2000,
        'pollDurationMs' => 60000,
        'modalSuffixByType' => [
            'cliente' => 'Cliente',
            'lote' => 'Lote',
            'renovacion' => 'Renovacion',
            'adelanto' => 'Adelanto',
            'articulo' => 'Articulo',
            'venta' => 'Venta',
            'articulo_venta' => 'ArticulosVenta',
            'adelanto_venta' => 'AdelantoVenta',
            'plazo_venta' => 'CobrarPlazoVenta',
        ],
    ];
    return array_replace_recursive($defaults, $overrides);
}
