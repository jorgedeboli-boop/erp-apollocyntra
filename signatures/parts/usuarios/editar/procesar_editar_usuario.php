<?php
/**
 * Archivo para procesar la edición de usuario
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
    $usuario = isset($_POST['usuario']) ? trim($_POST['usuario']) : '';
    $nombre_usuario = isset($_POST['nombre_usuario']) ? trim($_POST['nombre_usuario']) : '';
    $apellido_usuario = isset($_POST['apellido_usuario']) ? trim($_POST['apellido_usuario']) : '';
    $email = isset($_POST['email']) ? trim($_POST['email']) : '';
    $estado_usuario = isset($_POST['estado_usuario']) ? $_POST['estado_usuario'] : '';
    $telefono_usuario = isset($_POST['telefono_usuario']) ? trim($_POST['telefono_usuario']) : '';
    $sucursal_usuario = isset($_POST['sucursal_usuario']) ? (int)$_POST['sucursal_usuario'] : 0;
    $privilegio_usuario = isset($_POST['privilegio_usuario']) ? (int)$_POST['privilegio_usuario'] : 0;
    $observaciones_usuario = isset($_POST['observaciones_usuario']) ? trim($_POST['observaciones_usuario']) : '';
    

    
    // Validaciones básicas
    if (!$id_usuario) {
        throw new Exception("ID de usuario no válido");
    }
    
    if (empty($usuario)) {
        throw new Exception("El nombre de usuario es obligatorio");
    }
    
    if (empty($nombre_usuario)) {
        throw new Exception("El nombre es obligatorio");
    }
    
    if (empty($apellido_usuario)) {
        throw new Exception("El apellido es obligatorio");
    }
    
    if ($email !== '' && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        throw new Exception("El formato del email no es válido");
    }
    
    if (empty($estado_usuario)) {
        throw new Exception("El estado de usuario es obligatorio");
    }
    
    if (!$sucursal_usuario) {
        throw new Exception("La sucursal es obligatoria");
    }
    
    if (!$privilegio_usuario) {
        throw new Exception("El privilegio es obligatorio");
    }
    

    
    // Conectar BD
    $conexion = conectar_bd();
    
    // Verificar que el usuario existe y permisos de edición
    $query_check = "SELECT id_usuario, usuario_root, super_admin FROM usuarios WHERE id_usuario = ?";
    $stmt_check = mysqli_prepare($conexion, $query_check);
    mysqli_stmt_bind_param($stmt_check, 'i', $id_usuario);
    mysqli_stmt_execute($stmt_check);
    $result_check = mysqli_stmt_get_result($stmt_check);
    
    if (mysqli_num_rows($result_check) === 0) {
        throw new Exception("Usuario no encontrado");
    }

    $usuario_actual = mysqli_fetch_assoc($result_check);
    mysqli_stmt_close($stmt_check);

    if (!$es_usuario_root && ($usuario_actual['usuario_root'] ?? 'false') === 'true') {
        throw new Exception('No tienes permisos para editar este usuario');
    }
    if (!$es_usuario_root && !$es_usuario_super_administrador && ($usuario_actual['super_admin'] ?? 'false') === 'true') {
        throw new Exception('No tienes permisos para editar este usuario');
    }
    
    // Verificar que el nombre de usuario no esté duplicado (excluyendo el usuario actual)
    $query_duplicate = "SELECT id_usuario FROM usuarios WHERE usuario = ? AND id_usuario != ?";
    $stmt_duplicate = mysqli_prepare($conexion, $query_duplicate);
    mysqli_stmt_bind_param($stmt_duplicate, 'si', $usuario, $id_usuario);
    mysqli_stmt_execute($stmt_duplicate);
    $result_duplicate = mysqli_stmt_get_result($stmt_duplicate);
    
    if (mysqli_num_rows($result_duplicate) > 0) {
        throw new Exception("El nombre de usuario ya está en uso");
    }
    mysqli_stmt_close($stmt_duplicate);
    
    // Verificar que el email no esté duplicado (excluyendo el usuario actual)
    if ($email !== '') {
        $query_email = "SELECT id_usuario FROM usuarios WHERE email = ? AND id_usuario != ?";
        $stmt_email = mysqli_prepare($conexion, $query_email);
        mysqli_stmt_bind_param($stmt_email, 'si', $email, $id_usuario);
        mysqli_stmt_execute($stmt_email);
        $result_email = mysqli_stmt_get_result($stmt_email);

        if (mysqli_num_rows($result_email) > 0) {
            throw new Exception("El email ya está en uso");
        }
        mysqli_stmt_close($stmt_email);
    }
    
    // Construir consulta de actualización
    if ($es_usuario_root) {
        $usuario_root_valor = (isset($_POST['usuario_root']) && $_POST['usuario_root'] === 'true') ? 'true' : 'false';
        $query_update = "
            UPDATE usuarios SET 
                usuario = ?, 
                nombre_usuario = ?, 
                apellido_usuario = ?, 
                email = ?, 
                estado_usuario = ?, 
                telefono_usuario = ?, 
                sucursal_usuario = ?, 
                privilegio_usuario = ?, 
                observaciones_usuario = ?,
                usuario_root = ?
            WHERE id_usuario = ?
        ";
        $stmt_update = mysqli_prepare($conexion, $query_update);
        mysqli_stmt_bind_param($stmt_update, 'ssssssiissi',
            $usuario, $nombre_usuario, $apellido_usuario, $email, $estado_usuario,
            $telefono_usuario, $sucursal_usuario, $privilegio_usuario, $observaciones_usuario,
            $usuario_root_valor,
            $id_usuario
        );
    } else {
        $query_update = "
            UPDATE usuarios SET 
                usuario = ?, 
                nombre_usuario = ?, 
                apellido_usuario = ?, 
                email = ?, 
                estado_usuario = ?, 
                telefono_usuario = ?, 
                sucursal_usuario = ?, 
                privilegio_usuario = ?, 
                observaciones_usuario = ?
            WHERE id_usuario = ?
        ";
        $stmt_update = mysqli_prepare($conexion, $query_update);
        mysqli_stmt_bind_param($stmt_update, 'ssssssiisi',
            $usuario, $nombre_usuario, $apellido_usuario, $email, $estado_usuario,
            $telefono_usuario, $sucursal_usuario, $privilegio_usuario, $observaciones_usuario,
            $id_usuario
        );
    }
    
    // Ejecutar actualización
    if (!mysqli_stmt_execute($stmt_update)) {
        throw new Exception("Error al actualizar usuario: " . mysqli_stmt_error($stmt_update));
    }
    
    mysqli_stmt_close($stmt_update);
    
    // Registrar la acción en el log
    if (function_exists('registrar_accion_usuario')) {
        $texto_accion = "Usuario actualizado: $nombre_usuario $apellido_usuario";
        registrar_accion_usuario(
            $_SESSION['usuario_id'] ?? 0,
            'editar_usuario',
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
        'message' => 'Usuario actualizado correctamente',
        'id_usuario' => $id_usuario
    ));
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(array(
        'success' => false,
        'error' => $e->getMessage()
    ));
}
?>
