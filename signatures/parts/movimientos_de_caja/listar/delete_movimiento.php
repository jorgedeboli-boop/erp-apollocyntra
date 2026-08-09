<?php
/**
 * Archivo para eliminar un movimiento de caja
 */

require_once '../../../include/session.php';

header('Content-Type: application/json');

try {
    // Verificar que se reciban los parámetros necesarios
    if (!isset($_POST['id_movimiento']) || !isset($_POST['id_sucursal'])) {
        throw new Exception("Parámetros incompletos");
    }
    
    $idMovimiento = (int)$_POST['id_movimiento'];
    $idSucursal = (int)$_POST['id_sucursal'];
    
    // Validaciones
    if ($idMovimiento <= 0) {
        throw new Exception("ID de movimiento inválido");
    }
    
    if ($idSucursal <= 0) {
        throw new Exception("ID de sucursal inválido");
    }
    
    // Conectar BD
    $conexion = conectar_bd();
    
    // Nombre de la tabla
    $tableName = "movimientos_de_caja_$idSucursal";
    
    // Verificar si la tabla existe
    $checkTable = mysqli_query($conexion, "SHOW TABLES LIKE '$tableName'");
    if (mysqli_num_rows($checkTable) == 0) {
        throw new Exception("Tabla de movimientos no encontrada");
    }
    
    // Verificar que el movimiento existe antes de eliminar
    $queryCheck = "SELECT id_movimientos FROM $tableName WHERE id_movimientos = ?";
    $stmtCheck = mysqli_prepare($conexion, $queryCheck);
    mysqli_stmt_bind_param($stmtCheck, 'i', $idMovimiento);
    mysqli_stmt_execute($stmtCheck);
    $resultCheck = mysqli_stmt_get_result($stmtCheck);
    
    if (!$resultCheck || mysqli_num_rows($resultCheck) == 0) {
        throw new Exception("Movimiento no encontrado");
    }
    mysqli_stmt_close($stmtCheck);
    
    // Eliminar el movimiento
    $query = "DELETE FROM $tableName WHERE id_movimientos = ?";
    
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

