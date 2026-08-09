<?php
/**
 * Extiende la sesión durante migraciones largas (sin esperar a los últimos 5 min).
 */
require_once __DIR__ . '/../../../include/session.php';
require_once __DIR__ . '/../../../include/functions.php';

header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(array('success' => false, 'message' => 'Método no permitido'));
    exit;
}

if (!isset($_SESSION['usuario_autenticado']) || $_SESSION['usuario_autenticado'] !== true) {
    echo json_encode(array('success' => false, 'message' => 'Sesión no autenticada'));
    exit;
}

$_SESSION['usuario_login_time'] = time();
session_write_close();

echo json_encode(array(
    'success' => true,
    'message' => 'Sesión extendida',
    'time_remaining' => SESSION_LIFETIME * 1000,
));
