<?php
/**
 * Archivo para extender la sesión del usuario
 * Compatible con PHP 7.0
 */

require_once 'functions.php';

header('Content-Type: application/json');

try {
    // Verificar que sea una petición POST
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        http_response_code(405);
        echo json_encode(array('error' => 'Método no permitido'));
        exit;
    }
    
    if (!usuario_autenticado(false)) {
        echo json_encode(array(
            'success' => false,
            'error' => 'Sesión no autenticada'
        ));
        exit;
    }
    
    // Verificar si la sesión está por expirar (más de 30 minutos antes)
    $tiempo_transcurrido = time() - $_SESSION['usuario_login_time'];
    $tiempo_restante = SESSION_LIFETIME - $tiempo_transcurrido;
    
    if ($tiempo_restante > (30 * 60)) {
        echo json_encode(array(
            'success' => false,
            'error' => 'La sesión no necesita ser extendida aún'
        ));
        exit;
    }
    
    // Extender la sesión actualizando el tiempo de login
    $_SESSION['usuario_login_time'] = time();
    
    // Registrar la extensión de sesión
    if (function_exists('registrar_accion_usuario')) {
        registrar_accion_usuario(
            $_SESSION['usuario_id'] ?? 0,
            'extender_sesion',
            'Sesión extendida por el usuario',
            $_SESSION['usuario_sucursal'] ?? 0,
            $_SESSION['usuario_id'] ?? 0,
            $_SERVER['REQUEST_URI'] ?? ''
        );
    }
    
    echo json_encode(array(
        'success' => true,
        'message' => 'Sesión extendida correctamente',
        'new_login_time' => $_SESSION['usuario_login_time'],
        'time_remaining' => SESSION_LIFETIME * 1000
    ));
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(array(
        'success' => false,
        'error' => $e->getMessage()
    ));
}
?>
