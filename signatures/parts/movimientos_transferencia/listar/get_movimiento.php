<?php
/**
 * Archivo para obtener los datos de un movimiento específico
 */

require_once '../../../include/session.php';

header('Content-Type: application/json');

try {
    // Verificar que se reciban los parámetros necesarios
    if (!isset($_POST['id_movimiento'])) {
        throw new Exception("Parámetros incompletos");
    }
    
    $idMovimiento = (int)$_POST['id_movimiento'];
    
    // Conectar BD
    $conexion = conectar_bd();
    
    // Obtener los datos del movimiento
    $query = "SELECT id, fecha, grupos, descripcion, salida, entrada, usuario, sucursal
              FROM movimientos_transferencia 
              WHERE id = ?";
    
    $stmt = mysqli_prepare($conexion, $query);
    mysqli_stmt_bind_param($stmt, 'i', $idMovimiento);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    
    if (!$result || mysqli_num_rows($result) == 0) {
        throw new Exception("Movimiento no encontrado");
    }
    
    $movimiento = mysqli_fetch_assoc($result);
    
    mysqli_stmt_close($stmt);
    mysqli_close($conexion);
    
    // Respuesta exitosa
    echo json_encode([
        'success' => true,
        'movimiento' => $movimiento
    ]);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
?>

