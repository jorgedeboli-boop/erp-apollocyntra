<?php
require_once '../../../include/session.php';
require_once '../../../include/functions.php';

// Headers para JSON
header('Content-Type: application/json');
header('Cache-Control: no-cache, must-revalidate');

try {
    // Verificar permisos de proveedores
    if (!puede_acceder_a('proveedores')) {
        http_response_code(403);
        echo json_encode(array('success' => false, 'message' => 'Acceso denegado al módulo proveedores'));
        exit;
    }
    
    // Verificar que el usuario sea usuario_root
    if (!isset($_SESSION['usuario_root']) || $_SESSION['usuario_root'] !== true) {
        http_response_code(403);
        echo json_encode(array('success' => false, 'message' => 'Solo los usuarios administradores pueden eliminar proveedores'));
        exit;
    }
    
    // Verificar método POST
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        http_response_code(405);
        echo json_encode(array('success' => false, 'message' => 'Método no permitido'));
        exit;
    }
    
    // Obtener ID del proveedor
    $id_proveedor = isset($_POST['id_proveedor']) ? (int)$_POST['id_proveedor'] : 0;
    
    if (!$id_proveedor) {
        echo json_encode(array('success' => false, 'message' => 'ID de proveedor no válido'));
        exit;
    }
    
    // Conectar a la base de datos
    $conexion = conectar_bd();
    
    // Verificar que el proveedor existe
    $query_verificar = "SELECT nombre_proveedor FROM proveedores WHERE id_proveedor = ?";
    $stmt_verificar = mysqli_prepare($conexion, $query_verificar);
    mysqli_stmt_bind_param($stmt_verificar, 'i', $id_proveedor);
    mysqli_stmt_execute($stmt_verificar);
    $result_verificar = mysqli_stmt_get_result($stmt_verificar);
    
    if (!$result_verificar || mysqli_num_rows($result_verificar) === 0) {
        mysqli_stmt_close($stmt_verificar);
        mysqli_close($conexion);
        echo json_encode(array('success' => false, 'message' => 'Proveedor no encontrado'));
        exit;
    }
    
    $proveedor = mysqli_fetch_assoc($result_verificar);
    mysqli_stmt_close($stmt_verificar);
    
    // Iniciar transacción
    mysqli_begin_transaction($conexion);
    
    try {
        // Eliminar el proveedor
        $query_eliminar_proveedor = "DELETE FROM proveedores WHERE id_proveedor = ?";
        $stmt_eliminar_proveedor = mysqli_prepare($conexion, $query_eliminar_proveedor);
        mysqli_stmt_bind_param($stmt_eliminar_proveedor, 'i', $id_proveedor);
        mysqli_stmt_execute($stmt_eliminar_proveedor);
        mysqli_stmt_close($stmt_eliminar_proveedor);
        
        // Confirmar transacción
        mysqli_commit($conexion);
        
        echo json_encode(array(
            'success' => true,
            'message' => 'Proveedor "' . $proveedor['nombre_proveedor'] . '" eliminado correctamente'
        ));
        
    } catch (Exception $e) {
        // Revertir transacción en caso de error
        mysqli_rollback($conexion);
        throw $e;
    }
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(array(
        'success' => false,
        'message' => 'Error interno del servidor: ' . $e->getMessage()
    ));
} finally {
    if (isset($conexion)) {
        mysqli_close($conexion);
    }
}
?>
