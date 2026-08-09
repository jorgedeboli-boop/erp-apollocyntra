<?php
// Asegurar que no haya salida antes del JSON
ob_clean();

require_once '../../../include/session.php';
require_once '../../../include/functions.php';

header('Content-Type: application/json');

try {
    // Verificar que se haya enviado el ID del usuario
    if (!isset($_POST['id_usuario']) || !is_numeric($_POST['id_usuario'])) {
        throw new Exception("ID de usuario no válido");
    }
    
    $id_usuario = (int)$_POST['id_usuario'];
    
    // Conectar BD
    $conexion = conectar_bd();
    
    // Obtener el estado actual del usuario
    $query = "SELECT estado_usuario FROM usuarios WHERE id_usuario = ?";
    $stmt = mysqli_prepare($conexion, $query);
    mysqli_stmt_bind_param($stmt, "i", $id_usuario);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    
    if (mysqli_num_rows($result) == 0) {
        throw new Exception("Usuario no encontrado");
    }
    
    $usuario = mysqli_fetch_assoc($result);
    $estado_actual = $usuario['estado_usuario'];
    
    // Cambiar el estado (toggle)
    $nuevo_estado = ($estado_actual === 'true') ? 'false' : 'true';
    
    // Actualizar el estado
    $update_query = "UPDATE usuarios SET estado_usuario = ? WHERE id_usuario = ?";
    $update_stmt = mysqli_prepare($conexion, $update_query);
    mysqli_stmt_bind_param($update_stmt, "si", $nuevo_estado, $id_usuario);
    
    if (mysqli_stmt_execute($update_stmt)) {
        // Respuesta exitosa
        echo json_encode([
            'success' => true,
            'message' => $nuevo_estado === 'true' ? 'Usuario habilitado' : 'Usuario bloqueado',
            'nuevo_estado' => $nuevo_estado,
            'estado_texto' => $nuevo_estado === 'true' ? 'Habilitado' : 'Sin acceso',
            'boton_texto' => $nuevo_estado === 'true' ? 'Bloquear' : 'Desbloquear',
            'boton_icono' => $nuevo_estado === 'true' ? 'ri-lock-line' : 'ri-lock-unlock-line'
        ]);
    } else {
        throw new Exception("Error al actualizar el estado: " . mysqli_error($conexion));
    }
    
} catch (Exception $e) {
    // Respuesta de error
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}

mysqli_close($conexion);
?>
