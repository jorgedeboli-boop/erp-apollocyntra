<?php
require_once '../../../include/session.php';
require_once '../../../include/functions.php';

// Verificar que el usuario esté autenticado
if (!isset($_SESSION['usuario_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'No autorizado']);
    exit;
}

// Verificar que se hayan enviado los datos necesarios
if (!isset($_POST['id_tipo_gasto']) || !isset($_POST['nombre_tipo_gasto']) || 
    empty(trim($_POST['id_tipo_gasto'])) || empty(trim($_POST['nombre_tipo_gasto']))) {
    echo json_encode(['success' => false, 'message' => 'Todos los campos son obligatorios']);
    exit;
}

$id_tipo_gasto = intval($_POST['id_tipo_gasto']);
$nombre_tipo_gasto = trim($_POST['nombre_tipo_gasto']);

try {
    $conexion = conectar_bd();
    
    if (!$conexion) {
        throw new Exception('Error de conexión a la base de datos');
    }
    
    // Verificar si ya existe otro tipo de gasto con el mismo nombre (excluyendo el actual)
    $query_check = "SELECT id_tipo_gasto FROM tipo_de_gasto WHERE nombre_tipo_gasto = ? AND id_tipo_gasto != ?";
    $stmt_check = mysqli_prepare($conexion, $query_check);
    mysqli_stmt_bind_param($stmt_check, 'si', $nombre_tipo_gasto, $id_tipo_gasto);
    mysqli_stmt_execute($stmt_check);
    $result_check = mysqli_stmt_get_result($stmt_check);
    
    if (mysqli_num_rows($result_check) > 0) {
        mysqli_stmt_close($stmt_check);
        mysqli_close($conexion);
        echo json_encode(['success' => false, 'message' => 'Ya existe otro tipo de gasto con ese nombre']);
        exit;
    }
    
    mysqli_stmt_close($stmt_check);
    
    // Verificar que el tipo de gasto existe
    $query_exists = "SELECT id_tipo_gasto FROM tipo_de_gasto WHERE id_tipo_gasto = ?";
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
    
    mysqli_stmt_close($stmt_exists);
    
    // Actualizar el tipo de gasto
    $query_update = "UPDATE tipo_de_gasto SET nombre_tipo_gasto = ? WHERE id_tipo_gasto = ?";
    $stmt_update = mysqli_prepare($conexion, $query_update);
    mysqli_stmt_bind_param($stmt_update, 'si', $nombre_tipo_gasto, $id_tipo_gasto);
    
    if (mysqli_stmt_execute($stmt_update)) {
        mysqli_stmt_close($stmt_update);
        mysqli_close($conexion);
        
        echo json_encode([
            'success' => true, 
            'message' => 'Tipo de gasto actualizado exitosamente'
        ]);
    } else {
        throw new Exception('Error al actualizar en la base de datos');
    }
    
} catch (Exception $e) {
    error_log("Error en actualizar_tipo_gasto: " . $e->getMessage());
    
    if (isset($stmt_update)) {
        mysqli_stmt_close($stmt_update);
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
