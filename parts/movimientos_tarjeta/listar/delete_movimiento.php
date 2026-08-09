<?php
/**
 * Archivo para eliminar un movimiento con tarjeta
 */

require_once '../../../include/session.php';

header('Content-Type: application/json');

try {
    // Verificar que se reciban los parámetros necesarios
    if (!isset($_POST['id_movimiento'])) {
        throw new Exception("Parámetros incompletos");
    }
    
    $idMovimiento = (int)$_POST['id_movimiento'];
    
    // Validaciones
    if ($idMovimiento <= 0) {
        throw new Exception("ID de movimiento inválido");
    }
    
    // Conectar BD
    $conexion = conectar_bd();
    
    // Verificar que el movimiento existe antes de eliminar
    $queryCheck = "SELECT id FROM movimientos_tarjeta WHERE id = ?";
    $stmtCheck = mysqli_prepare($conexion, $queryCheck);
    mysqli_stmt_bind_param($stmtCheck, 'i', $idMovimiento);
    mysqli_stmt_execute($stmtCheck);
    $resultCheck = mysqli_stmt_get_result($stmtCheck);
    
    if (!$resultCheck || mysqli_num_rows($resultCheck) == 0) {
        throw new Exception("Movimiento no encontrado");
    }
    mysqli_stmt_close($stmtCheck);
    
    // Eliminar el movimiento
    $query = "DELETE FROM movimientos_tarjeta WHERE id = ?";
    
    $stmt = mysqli_prepare($conexion, $query);
    mysqli_stmt_bind_param($stmt, 'i', $idMovimiento);
    
    if (!mysqli_stmt_execute($stmt)) {
        throw new Exception("Error al eliminar el movimiento: " . mysqli_error($conexion));
    }
    
    $affectedRows = mysqli_stmt_affected_rows($stmt);
    
    mysqli_stmt_close($stmt);
    mysqli_close($conexion);
    
    if ($affectedRows === 0) {
        throw new Exception("No se pudo eliminar el movimiento");
    }
    
    // Respuesta exitosa
    echo json_encode([
        'success' => true,
        'message' => 'Movimiento eliminado correctamente'
    ]);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
?>

