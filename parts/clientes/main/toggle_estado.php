<?php
/**
 * Archivo para cambiar el estado del cliente (habilitar/deshabilitar)
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
    
    // Obtener datos
    $id_cliente = isset($_POST['id_cliente']) ? (int)$_POST['id_cliente'] : 0;
    $nuevo_estado = isset($_POST['nuevo_estado']) ? trim($_POST['nuevo_estado']) : '';
    
    if (!$id_cliente) {
        throw new Exception("ID de cliente no válido");
    }
    
    if (!in_array($nuevo_estado, ['habilitado', 'deshabilitado'])) {
        throw new Exception("Estado no válido");
    }
    
    // Conectar BD
    $conexion = conectar_bd();
    
    // Obtener estado actual del cliente
    $query_actual = "SELECT nombre, apellido, estado FROM clientes WHERE id_cliente = ?";
    $stmt_actual = mysqli_prepare($conexion, $query_actual);
    mysqli_stmt_bind_param($stmt_actual, 'i', $id_cliente);
    mysqli_stmt_execute($stmt_actual);
    $result_actual = mysqli_stmt_get_result($stmt_actual);
    
    if (mysqli_num_rows($result_actual) === 0) {
        throw new Exception("Cliente no encontrado");
    }
    
    $cliente_actual = mysqli_fetch_assoc($result_actual);
    mysqli_stmt_close($stmt_actual);

    $texto_action_user = "$usuario_nombre $usuario_apellido actualizó el estado a '$nuevo_estado' del cliente Nº '$id_cliente'";
    $id_action_user = "33";
    $relItemAction = $_SESSION['relItemAction'];
    registrar_accion_usuario($usuario_id, $id_action_user, $texto_action_user, $usuario_sucursal, $relItemAction);
    $_SESSION['relItemAction'] = "false";
    
    // Si el estado es el mismo, no hacer nada
    if ($cliente_actual['estado'] === $nuevo_estado) {
        echo json_encode(array(
            'success' => true,
            'message' => "El cliente ya está " . ($nuevo_estado === 'habilitado' ? 'habilitado' : 'deshabilitado'),
            'estado_actual' => $nuevo_estado
        ));
        exit;
    }
    
    // Actualizar estado
    $query_update = "UPDATE clientes SET estado = ? WHERE id_cliente = ?";
    $stmt_update = mysqli_prepare($conexion, $query_update);
    mysqli_stmt_bind_param($stmt_update, 'si', $nuevo_estado, $id_cliente);
    
    if (!mysqli_stmt_execute($stmt_update)) {
        throw new Exception("Error al actualizar el estado: " . mysqli_stmt_error($stmt_update));
    }
    
    mysqli_stmt_close($stmt_update);

     // Actualizar estado en Figueredo (solo production)
     $mysqli_figueredoapp = get_mysqli_figueredoapp();
     if ($mysqli_figueredoapp) {
         $query_update_figueredoapp = "UPDATE clientes SET estado = ? WHERE id_cliente = ?";
         $stmt_update_figueredoapp = mysqli_prepare($mysqli_figueredoapp, $query_update_figueredoapp);
         mysqli_stmt_bind_param($stmt_update_figueredoapp, 'si', $nuevo_estado, $id_cliente);
         
         if (!mysqli_stmt_execute($stmt_update_figueredoapp)) {
             throw new Exception("Error al actualizar el estado en FigueredoApp: " . mysqli_stmt_error($stmt_update_figueredoapp));
         }
         
         mysqli_stmt_close($stmt_update_figueredoapp);
     }

     $texto_action_user = "$usuario actualizó el estado a '$nuevo_estado' del cliente Nº '$id_cliente'";
     $id_action_user = "33";
     $relItemAction = $_SESSION['relItemAction'];
     registrar_accion_usuario($usuario_id, $id_action_user, $texto_action_user, $usuario_sucursal, $relItemAction);
     $_SESSION['relItemAction'] = "false";
    
    // Respuesta de éxito
    echo json_encode(array(
        'success' => true,
        'message' => "Cliente '" . $cliente_actual['nombre'] . " " . $cliente_actual['apellido'] . "' " . ($nuevo_estado === 'habilitado' ? 'habilitado' : 'deshabilitado') . " exitosamente",
        'estado_actual' => $nuevo_estado,
        'id_cliente' => $id_cliente
    ));
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(array(
        'success' => false,
        'error' => $e->getMessage()
    ));
} finally {
    if (isset($conexion)) {
        mysqli_close($conexion);
    }
}
?>
