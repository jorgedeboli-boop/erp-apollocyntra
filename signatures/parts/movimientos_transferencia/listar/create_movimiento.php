<?php
/**
 * Archivo para crear un nuevo movimiento por transferencia
 */

require_once '../../../include/session.php';

header('Content-Type: application/json');

try {
    // Verificar que se reciban los parámetros necesarios
    if (!isset($_POST['sucursal'])) {
        throw new Exception("Sucursal es requerida");
    }
    
    $sucursal = (int)$_POST['sucursal'];
    $fecha = trim($_POST['fecha']);
    $grupos = trim($_POST['grupos']);
    $descripcion = trim($_POST['descripcion']);
    $salida = floatval($_POST['salida']);
    $entrada = floatval($_POST['entrada']);
    
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
    
    // Validar que solo uno de los campos tenga valor
    if ($salida > 0 && $entrada > 0) {
        throw new Exception("Solo uno de los campos (Salida o Entrada) debe tener valor");
    }
    
    if ($salida === 0.0 && $entrada === 0.0) {
        throw new Exception("Debe ingresar un valor en Salida o Entrada");
    }
    
    // Conectar BD
    $conexion = conectar_bd();
    
    // Obtener el ID del usuario de la sesión
    $usuario = isset($usuario_id) ? $usuario_id : 'Sistema';
    
    // Insertar el nuevo movimiento
    $query = "INSERT INTO movimientos_transferencia (fecha, grupos, descripcion, salida, entrada, usuario, sucursal) 
              VALUES (?, ?, ?, ?, ?, ?, ?)";
    
    $stmt = mysqli_prepare($conexion, $query);
    mysqli_stmt_bind_param($stmt, 'sssddsi', $fecha, $grupos, $descripcion, $salida, $entrada, $usuario, $sucursal);
    
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

