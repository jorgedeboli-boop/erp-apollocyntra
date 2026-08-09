<?php
/**
 * Archivo para obtener los datos de un movimiento específico
 */

require_once '../../../include/session.php';

header('Content-Type: application/json');

try {
    // Verificar que se reciban los parámetros necesarios
    if (!isset($_POST['id_movimiento']) || !isset($_POST['sucursal_nombre'])) {
        throw new Exception("Parámetros incompletos");
    }
    
    $idMovimiento = (int)$_POST['id_movimiento'];
    $sucursalNombre = trim($_POST['sucursal_nombre']);
    
    // Conectar BD
    $conexion = conectar_bd();
    
    // Obtener el ID de la sucursal
    $querySucursal = "SELECT id_sucursal FROM sucursal WHERE nombre_sucursal = ?";
    $stmtSucursal = mysqli_prepare($conexion, $querySucursal);
    mysqli_stmt_bind_param($stmtSucursal, 's', $sucursalNombre);
    mysqli_stmt_execute($stmtSucursal);
    $resultSucursal = mysqli_stmt_get_result($stmtSucursal);
    
    if (!$resultSucursal || mysqli_num_rows($resultSucursal) == 0) {
        throw new Exception("Sucursal no encontrada");
    }
    
    $rowSucursal = mysqli_fetch_assoc($resultSucursal);
    $idSucursal = $rowSucursal['id_sucursal'];
    mysqli_stmt_close($stmtSucursal);
    
    // Nombre de la tabla
    $tableName = "movimientos_de_caja_$idSucursal";
    
    // Verificar si la tabla existe
    $checkTable = mysqli_query($conexion, "SHOW TABLES LIKE '$tableName'");
    if (mysqli_num_rows($checkTable) == 0) {
        throw new Exception("Tabla de movimientos no encontrada");
    }
    
    // Obtener los datos del movimiento
    $query = "SELECT id_movimientos, fecha_apunte, hora_de_apunte, grupos, concepto, salida, entrada, usuario 
              FROM $tableName 
              WHERE id_movimientos = ?";
    
    $stmt = mysqli_prepare($conexion, $query);
    mysqli_stmt_bind_param($stmt, 'i', $idMovimiento);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    
    if (!$result || mysqli_num_rows($result) == 0) {
        throw new Exception("Movimiento no encontrado");
    }
    
    $movimiento = mysqli_fetch_assoc($result);
    $movimiento['id_sucursal'] = $idSucursal;
    
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

