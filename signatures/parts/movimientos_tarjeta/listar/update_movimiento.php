<?php
/**
 * Archivo para actualizar un movimiento con tarjeta
 */

require_once '../../../include/session.php';

header('Content-Type: application/json');

try {
    // Verificar que se reciban los parámetros necesarios
    if (!isset($_POST['id'])) {
        throw new Exception("Parámetros incompletos");
    }
    
    $id = (int)$_POST['id'];
    $grupos = trim($_POST['grupos']);
    $descripcion = trim($_POST['descripcion']);
    $importe = floatval($_POST['importe']);
    
    // Validaciones
    if (empty($grupos)) {
        throw new Exception("El grupo es requerido");
    }
    
    if (empty($descripcion)) {
        throw new Exception("La descripción es requerida");
    }
    
    // Conectar BD
    $conexion = conectar_bd();
    
    // Actualizar el movimiento
    $query = "UPDATE movimientos_tarjeta 
              SET grupos = ?, 
                  descripcion = ?, 
                  importe = ?
              WHERE id = ?";
    
    $stmt = mysqli_prepare($conexion, $query);
    mysqli_stmt_bind_param($stmt, 'ssdi', $grupos, $descripcion, $importe, $id);
    
    if (!mysqli_stmt_execute($stmt)) {
        throw new Exception("Error al actualizar el movimiento: " . mysqli_error($conexion));
    }
    
    $affectedRows = mysqli_stmt_affected_rows($stmt);
    
    mysqli_stmt_close($stmt);
    mysqli_close($conexion);
    
    if ($affectedRows === 0) {
        throw new Exception("No se realizaron cambios en el movimiento");
    }
    
    // Respuesta exitosa
    echo json_encode([
        'success' => true,
        'message' => 'Movimiento actualizado correctamente'
    ]);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
?>

