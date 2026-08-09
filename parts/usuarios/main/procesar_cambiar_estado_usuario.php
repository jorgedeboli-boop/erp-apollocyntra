<?php
/**
 * Archivo para procesar el cambio de estado de un usuario
 * Compatible con PHP 7.0
 */

require_once '../../../include/session.php';
require_once '../../../include/functions.php';

header('Content-Type: application/json');

try {
    // Verificar que sea una petición POST
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        http_response_code(405);
        echo json_encode(array('error' => 'Método no permitido'));
        exit;
    }
    
    // Verificar que el usuario esté autenticado
    if (!isset($_SESSION['usuario_autenticado']) || !$_SESSION['usuario_autenticado']) {
        http_response_code(401);
        echo json_encode(array('error' => 'Usuario no autenticado'));
        exit;
    }

    $es_usuario_root = (isset($usuario_root) && $usuario_root === 'true');
    $es_usuario_super_administrador = (isset($usuario_super_administrador) && $usuario_super_administrador === 'true');
    
    // Obtener ID del usuario
    $id_usuario = isset($_POST['id_usuario']) ? (int)$_POST['id_usuario'] : 0;
    
    if (!$id_usuario) {
        http_response_code(400);
        echo json_encode(array('error' => 'ID de usuario no válido'));
        exit;
    }
    
    // Conectar BD
    $conexion = conectar_bd();
    
    // Obtener el estado actual del usuario
    $query_estado = "SELECT estado_usuario, usuario_root, super_admin FROM usuarios WHERE id_usuario = ?";
    $stmt_estado = mysqli_prepare($conexion, $query_estado);
    mysqli_stmt_bind_param($stmt_estado, 'i', $id_usuario);
    mysqli_stmt_execute($stmt_estado);
    $result_estado = mysqli_stmt_get_result($stmt_estado);
    
    if (!$result_estado || mysqli_num_rows($result_estado) == 0) {
        mysqli_stmt_close($stmt_estado);
        mysqli_close($conexion);
        http_response_code(404);
        echo json_encode(array('error' => 'Usuario no encontrado'));
        exit;
    }
    
    $usuario_actual = mysqli_fetch_assoc($result_estado);
    mysqli_stmt_close($stmt_estado);

    if (!$es_usuario_root && ($usuario_actual['usuario_root'] ?? 'false') === 'true') {
        mysqli_close($conexion);
        http_response_code(403);
        echo json_encode(array('error' => 'No tienes permisos para modificar este usuario'));
        exit;
    }
    if (!$es_usuario_root && !$es_usuario_super_administrador && ($usuario_actual['super_admin'] ?? 'false') === 'true') {
        mysqli_close($conexion);
        http_response_code(403);
        echo json_encode(array('error' => 'No tienes permisos para modificar este usuario'));
        exit;
    }
    
    // Cambiar el estado (toggle)
    $nuevo_estado = ($usuario_actual['estado_usuario'] === 'true') ? 'false' : 'true';
    
    // Actualizar el estado del usuario
    $query_update = "UPDATE usuarios SET estado_usuario = ? WHERE id_usuario = ?";
    $stmt_update = mysqli_prepare($conexion, $query_update);
    mysqli_stmt_bind_param($stmt_update, 'si', $nuevo_estado, $id_usuario);
    
    if (mysqli_stmt_execute($stmt_update)) {
        mysqli_stmt_close($stmt_update);
        mysqli_close($conexion);
        
        // Respuesta de éxito
        echo json_encode(array(
            'success' => true,
            'message' => 'Estado del usuario actualizado correctamente',
            'nuevo_estado' => $nuevo_estado
        ));
    } else {
        mysqli_stmt_close($stmt_update);
        mysqli_close($conexion);
        
        http_response_code(500);
        echo json_encode(array('error' => 'Error al actualizar el estado del usuario'));
    }
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(array(
        'success' => false,
        'error' => $e->getMessage()
    ));
}
?>
