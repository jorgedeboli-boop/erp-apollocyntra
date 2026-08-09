<?php
/**
 * Archivo para verificar el estado de la sesión
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
    
    // Obtener datos del JSON
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (!$input) {
        throw new Exception('Datos de entrada inválidos');
    }
    
    $last_activity = isset($input['last_activity']) ? (int)$input['last_activity'] : 0;
    $current_time = isset($input['current_time']) ? (int)$input['current_time'] : 0;
    
    // No extender la sesión en el polling: solo lectura del tiempo restante
    if (!usuario_autenticado(false)) {
        echo json_encode(array(
            'session_expired' => true,
            'warning_needed' => false,
            'time_remaining' => 0,
            'message' => 'Sesión no autenticada'
        ));
        exit;
    }
    
    // NUEVO: Verificar que el token siga activo (permite expulsión remota)
    if (!token_sesion_activo()) {
        cerrar_sesion();
        
        header('Clear-Site-Data: "cache", "storage"');  // <- solo en expulsión
        
        echo json_encode(array(
            'session_expired' => true,
            'warning_needed' => false,
            'time_remaining' => 0,
            'message' => 'Sesión cerrada remotamente'
        ));
        exit;
    }
    
    // Calcular tiempo transcurrido desde el inicio de sesión
    $tiempo_transcurrido = time() - $_SESSION['usuario_login_time'];
    $tiempo_restante = SESSION_LIFETIME - $tiempo_transcurrido;
    
    // Verificar si la sesión ha expirado
    if ($tiempo_transcurrido >= SESSION_LIFETIME) {
        cerrar_sesion();
        
        echo json_encode(array(
            'session_expired' => true,
            'warning_needed' => false,
            'time_remaining' => 0,
            'message' => 'Sesión expirada'
        ));
        exit;
    }
    
    // Verificar si se debe mostrar advertencia (30 minutos antes de expirar)
    $warning_threshold = 30 * 60;
    $warning_needed = $tiempo_restante <= $warning_threshold;
    
    // Respuesta de sesión válida
    echo json_encode(array(
        'session_expired' => false,
        'warning_needed' => $warning_needed,
        'time_remaining' => $tiempo_restante * 1000,
        'message' => 'Sesión válida',
        'session_data' => array(
            'user_id' => $_SESSION['usuario_id'] ?? null,
            'user_name' => $_SESSION['usuario_nombre'] ?? null,
            'login_time' => $_SESSION['usuario_login_time'] ?? null,
            'last_activity' => $last_activity,
            'current_time' => $current_time
        )
    ));
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(array(
        'session_expired' => true,
        'warning_needed' => false,
        'time_remaining' => 0,
        'error' => $e->getMessage()
    ));
}
?>