<?php
/**
 * Archivo para actualizar un movimiento de caja
 */

require_once '../../../include/session.php';

header('Content-Type: application/json');

try {
    // Verificar que se reciban los parámetros necesarios
    $idTabla = isset($_POST['id_tabla']) ? (int)$_POST['id_tabla'] : 0;
    if (!isset($_POST['id_movimiento']) || $idTabla <= 0) {
        throw new Exception("Parámetros incompletos");
    }
    
    $idMovimiento = (int)$_POST['id_movimiento'];
    $grupos = trim($_POST['grupos']);
    $concepto = trim($_POST['concepto']);
    $salida = floatval($_POST['salida']);
    $entrada = floatval($_POST['entrada']);

    
    
    // Validaciones
    if (empty($grupos)) {
        throw new Exception("El grupo es requerido");
    }
    if($grupos == "CAJA FINAL"){
        $cierre_caja = 'true';
    }else{
        $cierre_caja = 'false';
    }
    
    
    if (empty($concepto)) {
        throw new Exception("El concepto es requerido");
    }
    
    // Conectar BD
    $conexion = conectar_bd();
    
    // Nombre de la tabla
    $tableName = "movimientos_de_caja_$idTabla";
    
    // Verificar si la tabla existe
    $checkTable = mysqli_query($conexion, "SHOW TABLES LIKE '$tableName'");
    if (mysqli_num_rows($checkTable) == 0) {
        throw new Exception("Tabla de movimientos no encontrada");
    }
    
    // Actualizar el movimiento
    $query = "UPDATE $tableName 
              SET grupos = ?, 
                  concepto = ?, 
                  salida = ?, 
                  entrada = ? ,
                  cierre_caja = ?
              WHERE id_movimientos = ?";
    
    $stmt = mysqli_prepare($conexion, $query);
    mysqli_stmt_bind_param($stmt, 'ssddsi', $grupos, $concepto, $salida, $entrada, $cierre_caja, $idMovimiento);
    
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

