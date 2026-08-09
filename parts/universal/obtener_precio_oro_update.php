<?php
/**
 * Devuelve el último precio del oro (tabla precio_oro) para actualizar el navbar.
 */

require_once __DIR__ . '/../../include/session.php';
require_once __DIR__ . '/../../include/functions.php';

header('Content-Type: application/json; charset=utf-8');

if (!isset($_SESSION['usuario_autenticado']) || $_SESSION['usuario_autenticado'] !== true) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'No autorizado']);
    exit;
}

$datos = obtener_datos_precio_oro_navbar();

echo json_encode([
    'success' => true,
    'id_precio_oro' => (int) ($datos['id_precio_oro'] ?? 0),
    'precio_oro' => $datos['precio'],
    'precio_oro_fmt' => number_format($datos['precio'], 2, ',', '.'),
    'vigencia_fmt' => $datos['vigencia_fmt'],
    'last_update' => $datos['vigencia_fmt'],
    'fecha_registro_fmt' => $datos['fecha_registro_fmt'] ?? '—',
], JSON_UNESCAPED_UNICODE);
