<?php
/**
 * Archivo para procesar solo el cambio de contraseña del usuario
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

    $es_usuario_root = (isset($usuario_root) && $usuario_root === 'true');
    $es_usuario_super_administrador = (isset($usuario_super_administrador) && $usuario_super_administrador === 'true');
    
    // Obtener datos del formulario
    $id_usuario = isset($_POST['id_usuario']) ? (int)$_POST['id_usuario'] : 0;
    $nueva_password = isset($_POST['nueva_password']) ? trim($_POST['nueva_password']) : '';
    $confirmar_password = isset($_POST['confirmar_password']) ? trim($_POST['confirmar_password']) : '';
    
    // Validaciones básicas
    if (!$id_usuario) {
        throw new Exception("ID de usuario no válido");
    }
    
    if (empty($nueva_password)) {
        throw new Exception("La nueva contraseña es obligatoria");
    }
    
    if (empty($confirmar_password)) {
        throw new Exception("La confirmación de contraseña es obligatoria");
    }
    
    if ($nueva_password !== $confirmar_password) {
        throw new Exception("Las contraseñas no coinciden");
    }
    
    if (strlen($nueva_password) < 6) {
        throw new Exception("La contraseña debe tener al menos 6 caracteres");
    }
    
    // Conectar BD
    $conexion = conectar_bd();
    
    // Verificar que el usuario existe y permisos de edición
    $query_check = "SELECT id_usuario, usuario, nombre_usuario, apellido_usuario, usuario_root, super_admin FROM usuarios WHERE id_usuario = ?";
    $stmt_check = mysqli_prepare($conexion, $query_check);
    mysqli_stmt_bind_param($stmt_check, 'i', $id_usuario);
    mysqli_stmt_execute($stmt_check);
    $result_check = mysqli_stmt_get_result($stmt_check);
    
    if (mysqli_num_rows($result_check) === 0) {
        throw new Exception("Usuario no encontrado");
    }
    
    $usuario_info = mysqli_fetch_assoc($result_check);
    mysqli_stmt_close($stmt_check);

    if (!$es_usuario_root && ($usuario_info['usuario_root'] ?? 'false') === 'true') {
        throw new Exception('No tienes permisos para editar este usuario');
    }
    if (!$es_usuario_root && !$es_usuario_super_administrador && ($usuario_info['super_admin'] ?? 'false') === 'true') {
        throw new Exception('No tienes permisos para editar este usuario');
    }
    
    // Generar hash de la nueva contraseña
    $password_hash = password_hash($nueva_password, PASSWORD_DEFAULT);
    
    // Actualizar solo la contraseña
    $query_update = "UPDATE usuarios SET password = ? WHERE id_usuario = ?";
    $stmt_update = mysqli_prepare($conexion, $query_update);
    mysqli_stmt_bind_param($stmt_update, 'si', $password_hash, $id_usuario);
    
    if (!mysqli_stmt_execute($stmt_update)) {
        throw new Exception("Error al actualizar contraseña: " . mysqli_stmt_error($stmt_update));
    }
    
    mysqli_stmt_close($stmt_update);
    
    // Registrar la acción en el log
    if (function_exists('registrar_accion_usuario')) {
        $texto_accion = "Contraseña actualizada para usuario: " . $usuario_info['nombre_usuario'] . " " . $usuario_info['apellido_usuario'];
        registrar_accion_usuario(
            $_SESSION['usuario_id'] ?? 0,
            'cambiar_contrasena',
            $texto_accion,
            $_SESSION['usuario_sucursal'] ?? 0,
            $id_usuario,
            $_SERVER['REQUEST_URI'] ?? ''
        );
    }
    
    mysqli_close($conexion);
    
    // Respuesta de éxito
    echo json_encode(array(
        'success' => true,
        'message' => 'Contraseña actualizada correctamente',
        'id_usuario' => $id_usuario,
        'usuario_nombre' => $usuario_info['nombre_usuario'] . ' ' . $usuario_info['apellido_usuario']
    ));
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(array(
        'success' => false,
        'error' => $e->getMessage()
    ));
}
?>
