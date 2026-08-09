<?php
require_once '../../../include/session.php';
require_once '../../../include/functions.php';

// Verificar que el usuario esté autenticado
if (!isset($_SESSION['usuario_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'No autorizado']);
    exit;
}

// Verificar que se haya enviado el ID
if (!isset($_POST['id_tipo_gasto']) || empty(trim($_POST['id_tipo_gasto']))) {
    echo json_encode(['success' => false, 'message' => 'ID del tipo de gasto es obligatorio']);
    exit;
}

$id_tipo_gasto = intval($_POST['id_tipo_gasto']);

try {
    $conexion = conectar_bd();
    
    if (!$conexion) {
        throw new Exception('Error de conexión a la base de datos');
    }
    
    // Verificar que el tipo de gasto existe
    $query_exists = "SELECT id_tipo_gasto, nombre_tipo_gasto FROM tipo_de_gasto WHERE id_tipo_gasto = ?";
    $stmt_exists = mysqli_prepare($conexion, $query_exists);
    mysqli_stmt_bind_param($stmt_exists, 'i', $id_tipo_gasto);
    mysqli_stmt_execute($stmt_exists);
    $result_exists = mysqli_stmt_get_result($stmt_exists);
    
    if (mysqli_num_rows($result_exists) == 0) {
        mysqli_stmt_close($stmt_exists);
        mysqli_close($conexion);
        echo json_encode(['success' => false, 'message' => 'El tipo de gasto no existe']);
        exit;
    }
    
    $tipo_gasto = mysqli_fetch_assoc($result_exists);
    mysqli_stmt_close($stmt_exists);
    
    // Verificar si hay gastos que usen este tipo (opcional, dependiendo de la estructura de la BD)
    // Si tienes una tabla de gastos, puedes agregar esta verificación aquí
    /*
    $query_check_usage = "SELECT COUNT(*) as total FROM gastos WHERE tipo_gasto_id = ?";
    $stmt_check_usage = mysqli_prepare($conexion, $query_check_usage);
    mysqli_stmt_bind_param($stmt_check_usage, 'i', $id_tipo_gasto);
    mysqli_stmt_execute($stmt_check_usage);
    $result_check_usage = mysqli_stmt_get_result($stmt_check_usage);
    $total_usage = mysqli_fetch_assoc($result_check_usage)['total'];
    mysqli_stmt_close($stmt_check_usage);
    
    if ($total_usage > 0) {
        mysqli_close($conexion);
        echo json_encode(['success' => false, 'message' => 'No se puede eliminar el tipo de gasto porque está siendo usado por ' . $total_usage . ' gasto(s)']);
        exit;
    }
    */
    
    // Eliminar el tipo de gasto
    $query_delete = "DELETE FROM tipo_de_gasto WHERE id_tipo_gasto = ?";
    $stmt_delete = mysqli_prepare($conexion, $query_delete);
    mysqli_stmt_bind_param($stmt_delete, 'i', $id_tipo_gasto);
    
    if (mysqli_stmt_execute($stmt_delete)) {
        mysqli_stmt_close($stmt_delete);
        mysqli_close($conexion);
        
        echo json_encode([
            'success' => true, 
            'message' => 'Tipo de gasto "' . $tipo_gasto['nombre_tipo_gasto'] . '" eliminado exitosamente'
        ]);
    } else {
        throw new Exception('Error al eliminar de la base de datos');
    }
    
} catch (Exception $e) {
    error_log("Error en eliminar_tipo_gasto: " . $e->getMessage());
    
    if (isset($stmt_delete)) {
        mysqli_stmt_close($stmt_delete);
    }
    if (isset($conexion)) {
        mysqli_close($conexion);
    }
    
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Error del servidor: ' . $e->getMessage()
    ]);
}
?>
