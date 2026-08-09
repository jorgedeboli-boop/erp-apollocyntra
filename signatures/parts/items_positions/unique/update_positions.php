<?php
// Incluir archivos necesarios
require_once '../../../include/session.php';
require_once '../../../include/functions.php';

// Verificar que el usuario esté autenticado
if (!isset($_SESSION['usuario_id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'No autorizado']);
    exit;
}

// Verificar que sea una petición POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Método no permitido']);
    exit;
}

// Verificar que sea una petición AJAX
if (!isset($_SERVER['HTTP_X_REQUESTED_WITH']) || strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) != 'xmlhttprequest') {
    http_response_code(400);
    echo json_encode(['error' => 'Petición inválida']);
    exit;
}

try {
    // Obtener datos JSON del cuerpo de la petición
    $input = file_get_contents('php://input');
    $data = json_decode($input, true);
    
    if (!$data || !isset($data['posiciones']) || !is_array($data['posiciones'])) {
        throw new Exception('Datos de posiciones inválidos');
    }
    
    $posiciones = $data['posiciones'];
    
    // Validar que hay posiciones para actualizar
    if (empty($posiciones)) {
        throw new Exception('No se proporcionaron posiciones para actualizar');
    }
    
    // Conectar a la base de datos
    $conexion = conectar_bd();
    
    if (!$conexion) {
        throw new Exception('Error de conexión a la base de datos');
    }
    
    // Iniciar transacción
    mysqli_autocommit($conexion, false);
    
    try {
        // Preparar la consulta de actualización
        $query = "UPDATE itemsSections SET position_menu = ? WHERE id_type_Item = ?";
        $stmt = mysqli_prepare($conexion, $query);
        
        if (!$stmt) {
            throw new Exception('Error al preparar la consulta: ' . mysqli_error($conexion));
        }
        
        // Actualizar cada posición
        $actualizaciones = 0;
        foreach ($posiciones as $posicion) {
            $id = intval($posicion['id']);
            $nueva_posicion = intval($posicion['position']);
            
            // Validar datos
            if ($id <= 0 || $nueva_posicion <= 0) {
                continue; // Saltar posiciones inválidas
            }
            
            mysqli_stmt_bind_param($stmt, 'ii', $nueva_posicion, $id);
            $resultado = mysqli_stmt_execute($stmt);
            
            if ($resultado) {
                $actualizaciones++;
            } else {
                error_log("Error al actualizar posición para ID $id: " . mysqli_stmt_error($stmt));
            }
        }
        
        // Cerrar statement
        mysqli_stmt_close($stmt);
        
        // Confirmar transacción
        mysqli_commit($conexion);
        
        // Cerrar conexión
        mysqli_close($conexion);
        
        // Devolver respuesta JSON exitosa
        header('Content-Type: application/json');
        echo json_encode([
            'success' => true,
            'message' => 'Posiciones actualizadas correctamente',
            'actualizaciones' => $actualizaciones,
            'total_posiciones' => count($posiciones)
        ]);
        
    } catch (Exception $e) {
        // Revertir transacción en caso de error
        mysqli_rollback($conexion);
        throw $e;
    }
    
} catch (Exception $e) {
    // En caso de error
    if (isset($conexion)) {
        mysqli_close($conexion);
    }
    
    error_log("Error en update_positions.php: " . $e->getMessage());
    
    http_response_code(500);
    echo json_encode([
        'error' => 'Error del servidor: ' . $e->getMessage()
    ]);
}
?>
