<?php
/**
 * Archivo para cambiar el estado de un cliente
 * Cambia entre 'habilitado' y 'deshabilitado'
 */

require_once '../../../include/session.php';
require_once '../../../include/functions.php';

header('Content-Type: application/json');

try {
    // Verificar que se hayan enviado los parámetros necesarios
    if (!isset($_POST['id_cliente']) ) {
        throw new Exception("Parámetros incompletos");
    }
    
    $id_cliente = intval($_POST['id_cliente']);

    // Conectar a la base de datos
    $conexion = conectar_bd();
    
    // Verificar que el cliente existe
    $query_check = "SELECT id_cliente, nombre, apellido, estado FROM clientes WHERE id_cliente = ?";
    $stmt_check = mysqli_prepare($conexion, $query_check);
    mysqli_stmt_bind_param($stmt_check, 'i', $id_cliente);
    mysqli_stmt_execute($stmt_check);
    $result_check = mysqli_stmt_get_result($stmt_check);
    
    if (mysqli_num_rows($result_check) == 0) {
        throw new Exception("Cliente no encontrado");
    }
    
    $cliente = mysqli_fetch_assoc($result_check);
    $estado_anterior = $cliente['estado'];

    if ($estado_anterior == 'habilitado') {
        $nuevo_estado = 'deshabilitado';
    } else {
        $nuevo_estado = 'habilitado';
    }
    
    // Actualizar el estado del cliente
    $query_update = "UPDATE clientes SET estado = ? WHERE id_cliente = ?";
    $stmt_update = mysqli_prepare($conexion, $query_update);
    mysqli_stmt_bind_param($stmt_update, 'si', $nuevo_estado, $id_cliente);
    
    if (!mysqli_stmt_execute($stmt_update)) {
        throw new Exception("Error al actualizar el estado: " . mysqli_error($conexion));
    }
    
    // Verificar que se actualizó correctamente
    if (mysqli_stmt_affected_rows($stmt_update) == 0) {
        throw new Exception("No se pudo actualizar el estado del cliente");
    }
    
    // Registrar la acción del usuario
    $accion = $nuevo_estado === 'habilitado' ? 'Cliente habilitado' : 'Cliente deshabilitado';
    if (function_exists('registrar_accion_usuario')) {
        $texto_action_user = $accion;   
        $id_action_user = "26";
        $url_completa = APP_URL.'/clientes/listar/content.php?id=' . $id_cliente;
        $relItemAction = "44";
        registrar_accion_usuario($usuario_id, $id_action_user, $texto_action_user, $usuario_sucursal, $relItemAction, $url_completa);
    }
    
    // Cerrar statements
    mysqli_stmt_close($stmt_check);
    mysqli_stmt_close($stmt_update);
    mysqli_close($conexion);
    
    // Respuesta exitosa
    echo json_encode([
        'success' => true,
        'message' => "Estado del cliente actualizado correctamente de '{$estado_anterior}' a '{$nuevo_estado}'",
        'estado_anterior' => $estado_anterior,
        'estado_texto' => $nuevo_estado === 'habilitado' ? 'Habilitado' : 'Deshabilitado',
        'boton_texto' => $nuevo_estado === 'habilitado' ? 'Bloquear' : 'Desbloquear',
        'boton_icono' => $nuevo_estado === 'habilitado' ? 'ri-user-follow-line' : 'ri-user-forbid-line',
        'nuevo_estado' => $nuevo_estado,
        'id_cliente' => $id_cliente
    ]);
    
} catch (Exception $e) {
    // Respuesta de error
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
} finally {
    if (isset($conexion)) {
        mysqli_close($conexion);
    }
}
?>
