<?php
/**
 * Archivo para crear un nuevo movimiento bizum
 */

require_once '../../../include/session.php';

header('Content-Type: application/json');

try {
    // Verificar que se reciban los parámetros necesarios
    $fecha = trim($_POST['fecha']);
    $grupos = trim($_POST['grupos']);
    $descripcion = trim($_POST['descripcion']);
    $importe = floatval($_POST['importe']);
    
    // Validaciones
    if (empty($fecha)) {
        throw new Exception("La fecha es requerida");
    }
    
    if (empty($grupos)) {
        throw new Exception("El grupo es requerido");
    }
    
    if (empty($descripcion)) {
        throw new Exception("La descripción es requerida");
    }
    
    // Conectar BD
    $conexion = conectar_bd();
    
    // Obtener el ID del usuario de la sesión
    $usuario = isset($usuario_id) ? $usuario_id : 'Sistema';
    
    // Insertar el nuevo movimiento
    $query = "INSERT INTO movimientos_bizum (fecha, grupos, descripcion, importe, usuario) 
              VALUES (?, ?, ?, ?, ?)";
    
    $stmt = mysqli_prepare($conexion, $query);
    mysqli_stmt_bind_param($stmt, 'sssds', $fecha, $grupos, $descripcion, $importe, $usuario);
    
    if (!mysqli_stmt_execute($stmt)) {
        throw new Exception("Error al crear el movimiento: " . mysqli_error($conexion));
    }
    
    $nuevoId = mysqli_insert_id($conexion);
    
    mysqli_stmt_close($stmt);
    mysqli_close($conexion);
    
    // Respuesta exitosa
    echo json_encode([
        'success' => true,
        'message' => 'Movimiento creado correctamente',
        'id_movimiento' => $nuevoId
    ]);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
?>

