<?php
/**
 * Archivo para crear un nuevo movimiento de caja
 */

require_once '../../../include/session.php';

header('Content-Type: application/json');

try {
    // Verificar que se reciban los parámetros necesarios
    if (!isset($_POST['id_sucursal'])) {
        throw new Exception("ID de sucursal es requerido");
    }
    
    $idSucursal = (int)$_POST['id_sucursal'];
    $fechaApunte = trim($_POST['fecha_apunte']);
    $grupos = trim($_POST['grupos']);
    $concepto = trim($_POST['concepto']);
    $salida = floatval($_POST['salida']);
    $entrada = floatval($_POST['entrada']);
    
    // Validaciones
    if (empty($fechaApunte)) {
        throw new Exception("La fecha es requerida");
    }
    
    if (empty($grupos)) {
        throw new Exception("El grupo es requerido");
    }
    
    if (empty($concepto)) {
        throw new Exception("El concepto es requerido");
    }
    
    if ($salida === 0.0 && $entrada === 0.0) {
        throw new Exception("Debe ingresar un valor en Salida o Entrada");
    }
    
    // Conectar BD
    $conexion = conectar_bd();
    
    // Verificar que la sucursal existe
    $querySucursal = "SELECT id_sucursal FROM sucursal WHERE id_sucursal = ?";
    $stmtSucursal = mysqli_prepare($conexion, $querySucursal);
    mysqli_stmt_bind_param($stmtSucursal, 'i', $idSucursal);
    mysqli_stmt_execute($stmtSucursal);
    $resultSucursal = mysqli_stmt_get_result($stmtSucursal);
    
    if (!$resultSucursal || mysqli_num_rows($resultSucursal) == 0) {
        throw new Exception("Sucursal no encontrada");
    }
    mysqli_stmt_close($stmtSucursal);
    
    // Nombre de la tabla
    $tableName = "movimientos_de_caja_$idSucursal";
    
    // Verificar si la tabla existe
    $checkTable = mysqli_query($conexion, "SHOW TABLES LIKE '$tableName'");
    if (mysqli_num_rows($checkTable) == 0) {
        throw new Exception("Tabla de movimientos no encontrada");
    }
    
    // Obtener el ID del usuario de la sesión
    $usuario = isset($usuario_id) ? $usuario_id : 'Sistema';
    
    // Insertar el nuevo movimiento
    $query = "INSERT INTO $tableName (fecha_apunte, grupos, concepto, salida, entrada, usuario) 
              VALUES (?, ?, ?, ?, ?, ?)";
    
    $stmt = mysqli_prepare($conexion, $query);
    mysqli_stmt_bind_param($stmt, 'sssdds', $fechaApunte, $grupos, $concepto, $salida, $entrada, $usuario);
    
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

