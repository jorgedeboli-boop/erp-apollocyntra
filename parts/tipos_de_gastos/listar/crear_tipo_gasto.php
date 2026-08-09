<?php
require_once '../../../include/session.php';
require_once '../../../include/functions.php';

// Verificar que el usuario esté autenticado
if (!isset($_SESSION['usuario_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'No autorizado']);
    exit;
}

// Verificar que se haya enviado el nombre
if (!isset($_POST['nombre_tipo_gasto']) || empty(trim($_POST['nombre_tipo_gasto']))) {
    echo json_encode(['success' => false, 'message' => 'El nombre del tipo de gasto es obligatorio']);
    exit;
}

$nombre_tipo_gasto = trim($_POST['nombre_tipo_gasto']);

try {
    $conexion = conectar_bd();
    
    if (!$conexion) {
        throw new Exception('Error de conexión a la base de datos');
    }
    
    // Verificar si ya existe un tipo de gasto con el mismo nombre
    $query_check = "SELECT id_tipo_gasto FROM tipo_de_gasto WHERE nombre_tipo_gasto = ?";
    $stmt_check = mysqli_prepare($conexion, $query_check);
    mysqli_stmt_bind_param($stmt_check, 's', $nombre_tipo_gasto);
    mysqli_stmt_execute($stmt_check);
    $result_check = mysqli_stmt_get_result($stmt_check);
    
    if (mysqli_num_rows($result_check) > 0) {
        mysqli_stmt_close($stmt_check);
        mysqli_close($conexion);
        echo json_encode(['success' => false, 'message' => 'Ya existe un tipo de gasto con ese nombre']);
        exit;
    }
    
    mysqli_stmt_close($stmt_check);
    
    // Insertar nuevo tipo de gasto
    $query_insert = "INSERT INTO tipo_de_gasto (nombre_tipo_gasto, fecha_alta, usuario_alta_gasto) VALUES (?, NOW(), ?)";
    $stmt_insert = mysqli_prepare($conexion, $query_insert);
    mysqli_stmt_bind_param($stmt_insert, 'si', $nombre_tipo_gasto, $_SESSION['usuario_id']);
    
    if (mysqli_stmt_execute($stmt_insert)) {
        $id_nuevo = mysqli_insert_id($conexion);
        mysqli_stmt_close($stmt_insert);
        mysqli_close($conexion);
        
        echo json_encode([
            'success' => true, 
            'message' => 'Tipo de gasto creado exitosamente',
            'id' => $id_nuevo
        ]);
    } else {
        throw new Exception('Error al insertar en la base de datos');
    }
    
} catch (Exception $e) {
    error_log("Error en crear_tipo_gasto: " . $e->getMessage());
    
    if (isset($stmt_insert)) {
        mysqli_stmt_close($stmt_insert);
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
