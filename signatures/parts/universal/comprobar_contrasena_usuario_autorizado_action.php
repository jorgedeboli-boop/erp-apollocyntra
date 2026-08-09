<?php
require_once __DIR__ . '/../../include/session.php';
require_once __DIR__ . '/../../include/functions.php';

header('Content-Type: application/json; charset=utf-8');

try {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception('Método no permitido');
    }

    $contrasena = isset($_POST['contrasena']) ? (string) $_POST['contrasena'] : '';
    $sucursal = isset($_POST['sucursal']) ? (int) $_POST['sucursal'] : 0;
    $id_item = isset($_POST['id_item']) ? (int) $_POST['id_item'] : 0;
    $usuario_id_sesion = (int) $usuario_id;

    $resultado = comprobar_contrasena_usuario_autorizado_action(
        $usuario_id_sesion,
        $contrasena,
        $sucursal,
        $id_item
    );

    $autorizado = ($resultado['estado'] ?? '') === 'autorizado';

    echo json_encode([
        'success' => $autorizado,
        'autorizado' => $resultado['estado'] ?? 'no autorizado',
        'mensaje' => $resultado['mensaje'] ?? '',
    ], JSON_UNESCAPED_UNICODE);
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'autorizado' => 'no autorizado',
        'mensaje' => $e->getMessage(),
    ], JSON_UNESCAPED_UNICODE);
}
