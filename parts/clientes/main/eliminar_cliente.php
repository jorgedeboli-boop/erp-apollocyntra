<?php
require_once '../../../include/session.php';
require_once '../../../include/functions.php';

// Limpiar cualquier output previo
ob_start();
ob_clean();

header('Content-Type: application/json');

try {
    // Verificar que sea una petición POST
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        http_response_code(405);
        echo json_encode(array('error' => 'Método no permitido'));
        exit;
    }
    
    // Verificar que el usuario esté autenticado
    if (!usuario_autenticado()) {
        http_response_code(401);
        echo json_encode(array('error' => 'No autorizado'));
        exit;
    }
    
    // Validar campo obligatorio
    if (!isset($_POST['id_cliente']) || empty($_POST['id_cliente'])) {
        throw new Exception("El ID del cliente es obligatorio");
    }
    
    $id_cliente = (int)$_POST['id_cliente'];
    
    // Obtener datos de sesión
    $usuario_id = $_SESSION['usuario_id'];
    $usuario = $_SESSION['usuario'];
    $usuario_sucursal = $_SESSION['usuario_sucursal'];
    $id_type_Item = (int)$_POST['relItemAction'];
    
    // Conectar BD
    $conexion = conectar_bd();
    
    // Iniciar transacción
    mysqli_begin_transaction($conexion);
    
    try {
        // Obtener datos del cliente antes de eliminarlo
        $query_cliente = "SELECT nombre, apellido FROM clientes WHERE id_cliente = ?";
        $stmt_cliente = mysqli_prepare($conexion, $query_cliente);
        mysqli_stmt_bind_param($stmt_cliente, 'i', $id_cliente);
        mysqli_stmt_execute($stmt_cliente);
        $result = mysqli_stmt_get_result($stmt_cliente);
        
        if (!$result || mysqli_num_rows($result) == 0) {
            throw new Exception("Cliente no encontrado");
        }
        
        $cliente = mysqli_fetch_assoc($result);
        $nombre_completo = $cliente['nombre'] . ' ' . $cliente['apellido'];
        mysqli_stmt_close($stmt_cliente);
        
        // UPDATE: Cambiar delete_state a 'true'
        $query_update = "UPDATE clientes SET delete_state = 'true' WHERE id_cliente = ?";
        $stmt_update = mysqli_prepare($conexion, $query_update);
        
        if (!$stmt_update) {
            throw new Exception("Error preparando consulta: " . mysqli_error($conexion));
        }
        
        mysqli_stmt_bind_param($stmt_update, 'i', $id_cliente);
        
        if (!mysqli_stmt_execute($stmt_update)) {
            throw new Exception("Error al eliminar cliente: " . mysqli_stmt_error($stmt_update));
        }
        
        mysqli_stmt_close($stmt_update);
        
        // Insertar en BinList
        $descriptionBin = "El usuario $usuario ha eliminado el $id_cliente";
        $resultado_bin = insertarBinList($id_cliente, $usuario, $descriptionBin, $usuario_id, $id_type_Item);
        
        if (!$resultado_bin) {
            throw new Exception("Error al registrar en BinList");
        }
        
        // Confirmar transacción
        mysqli_commit($conexion);
        
        // Cerrar conexión
        mysqli_close($conexion);

        $texto_action_user = "$usuario eliminó el cliente Nº '$id_cliente' - $nombre_completo";
        $id_action_user = "35";
        $relItemAction = $_SESSION['relItemAction'];
        registrar_accion_usuario($usuario_id, $id_action_user, $texto_action_user, $usuario_sucursal, $relItemAction);
        $_SESSION['relItemAction'] = "false";
        
        // Respuesta de éxito
        echo json_encode(array(
            'success' => true,
            'message' => "Cliente '$nombre_completo' eliminado exitosamente"
        ));
        
    } catch (Exception $e) {
        // Revertir transacción en caso de error
        mysqli_rollback($conexion);
        mysqli_close($conexion);
        throw $e;
    }
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(array(
        'success' => false,
        'error' => $e->getMessage()
    ));
}
?>

