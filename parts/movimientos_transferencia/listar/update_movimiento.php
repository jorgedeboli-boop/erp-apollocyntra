<?php
/**
 * Archivo para actualizar un movimiento por transferencia
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
    $salida = floatval($_POST['salida']);
    $entrada = floatval($_POST['entrada']);
    
    // Validaciones
    if (empty($grupos)) {
        throw new Exception("El grupo es requerido");
    }
    
    if (empty($descripcion)) {
        throw new Exception("La descripción es requerida");
    }
    
    // Validar que solo uno de los campos tenga valor
    if ($salida > 0 && $entrada > 0) {
        throw new Exception("Solo uno de los campos (Salida o Entrada) debe tener valor");
    }
    
    if ($salida === 0.0 && $entrada === 0.0) {
        throw new Exception("Debe ingresar un valor en Salida o Entrada");
    }
    
    // Conectar BD
    $conexion = conectar_bd();
    
    // Actualizar el movimiento
    $query = "UPDATE movimientos_transferencia 
              SET grupos = ?, 
                  descripcion = ?, 
                  salida = ?, 
                  entrada = ?
              WHERE id = ?";
    
    $stmt = mysqli_prepare($conexion, $query);
    mysqli_stmt_bind_param($stmt, 'ssddi', $grupos, $descripcion, $salida, $entrada, $id);
    
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

