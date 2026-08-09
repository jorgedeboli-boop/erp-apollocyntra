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
    // Obtener datos del formulario
    $id = trim($_POST['id'] ?? '');
    $direccion = trim($_POST['direccion'] ?? ''); // 'up' o 'down'
    
    // Validar datos obligatorios
    if (empty($id)) {
        throw new Exception('El ID del item es obligatorio');
    }
    
    if (!in_array($direccion, ['up', 'down'])) {
        throw new Exception('La dirección debe ser "up" o "down"');
    }
    
    // Conectar a la base de datos
    $conexion = conectar_bd();
    
    if (!$conexion) {
        throw new Exception('Error de conexión a la base de datos');
    }
    
    // Obtener la posición actual del item
    $query_actual = "SELECT position_menu FROM itemsSections WHERE id_type_Item = ?";
    $stmt_actual = mysqli_prepare($conexion, $query_actual);
    
    if (!$stmt_actual) {
        throw new Exception('Error al preparar la consulta: ' . mysqli_error($conexion));
    }
    
    mysqli_stmt_bind_param($stmt_actual, 'i', $id);
    mysqli_stmt_execute($stmt_actual);
    mysqli_stmt_bind_result($stmt_actual, $posicion_actual);
    mysqli_stmt_fetch($stmt_actual);
    mysqli_stmt_close($stmt_actual);
    
    if ($posicion_actual === null) {
        throw new Exception('El item especificado no existe');
    }
    
    // Calcular la nueva posición
    $nueva_posicion = ($direccion === 'up') ? $posicion_actual - 1 : $posicion_actual + 1;
    
    // Verificar que la nueva posición sea válida (mayor que 0)
    if ($nueva_posicion < 1) {
        throw new Exception('No se puede subir más la posición');
    }
    
    // Buscar si ya existe un item con la nueva posición
    $query_conflicto = "SELECT id_type_Item FROM itemsSections WHERE position_menu = ?";
    $stmt_conflicto = mysqli_prepare($conexion, $query_conflicto);
    
    if (!$stmt_conflicto) {
        throw new Exception('Error al preparar la consulta de conflicto: ' . mysqli_error($conexion));
    }
    
    mysqli_stmt_bind_param($stmt_conflicto, 'i', $nueva_posicion);
    mysqli_stmt_execute($stmt_conflicto);
    mysqli_stmt_store_result($stmt_conflicto);
    
    $hay_conflicto = mysqli_stmt_num_rows($stmt_conflicto) > 0;
    mysqli_stmt_close($stmt_conflicto);
    
    // Si hay conflicto, intercambiar posiciones
    if ($hay_conflicto) {
        // Obtener el ID del item que tiene la posición de destino
        $query_item_conflicto = "SELECT id_type_Item FROM itemsSections WHERE position_menu = ?";
        $stmt_item_conflicto = mysqli_prepare($conexion, $query_item_conflicto);
        
        if (!$stmt_item_conflicto) {
            throw new Exception('Error al preparar la consulta del item en conflicto: ' . mysqli_error($conexion));
        }
        
        mysqli_stmt_bind_param($stmt_item_conflicto, 'i', $nueva_posicion);
        mysqli_stmt_execute($stmt_item_conflicto);
        mysqli_stmt_bind_result($stmt_item_conflicto, $id_conflicto);
        mysqli_stmt_fetch($stmt_item_conflicto);
        mysqli_stmt_close($stmt_item_conflicto);
        
        // Iniciar transacción
        mysqli_autocommit($conexion, false);
        
        try {
            // Actualizar el item actual con la nueva posición
            $query_update_actual = "UPDATE itemsSections SET position_menu = ? WHERE id_type_Item = ?";
            $stmt_update_actual = mysqli_prepare($conexion, $query_update_actual);
            
            if (!$stmt_update_actual) {
                throw new Exception('Error al preparar la actualización del item actual: ' . mysqli_error($conexion));
            }
            
            mysqli_stmt_bind_param($stmt_update_actual, 'ii', $nueva_posicion, $id);
            $resultado_actual = mysqli_stmt_execute($stmt_update_actual);
            mysqli_stmt_close($stmt_update_actual);
            
            if (!$resultado_actual) {
                throw new Exception('Error al actualizar la posición del item actual');
            }
            
            // Actualizar el item en conflicto con la posición anterior
            $query_update_conflicto = "UPDATE itemsSections SET position_menu = ? WHERE id_type_Item = ?";
            $stmt_update_conflicto = mysqli_prepare($conexion, $query_update_conflicto);
            
            if (!$stmt_update_conflicto) {
                throw new Exception('Error al preparar la actualización del item en conflicto: ' . mysqli_error($conexion));
            }
            
            mysqli_stmt_bind_param($stmt_update_conflicto, 'ii', $posicion_actual, $id_conflicto);
            $resultado_conflicto = mysqli_stmt_execute($stmt_update_conflicto);
            mysqli_stmt_close($stmt_update_conflicto);
            
            if (!$resultado_conflicto) {
                throw new Exception('Error al actualizar la posición del item en conflicto');
            }
            
            // Confirmar transacción
            mysqli_commit($conexion);
            
        } catch (Exception $e) {
            // Revertir transacción en caso de error
            mysqli_rollback($conexion);
            throw $e;
        }
        
    } else {
        // No hay conflicto, simplemente actualizar la posición
        $query_update = "UPDATE itemsSections SET position_menu = ? WHERE id_type_Item = ?";
        $stmt_update = mysqli_prepare($conexion, $query_update);
        
        if (!$stmt_update) {
            throw new Exception('Error al preparar la actualización: ' . mysqli_error($conexion));
        }
        
        mysqli_stmt_bind_param($stmt_update, 'ii', $nueva_posicion, $id);
        $resultado = mysqli_stmt_execute($stmt_update);
        mysqli_stmt_close($stmt_update);
        
        if (!$resultado) {
            throw new Exception('Error al actualizar la posición del item');
        }
    }
    
    // Cerrar conexión
    mysqli_close($conexion);
    
    // Devolver respuesta JSON exitosa
    header('Content-Type: application/json');
    echo json_encode([
        'success' => true,
        'message' => 'Posición actualizada correctamente',
        'id' => $id,
        'posicion_anterior' => $posicion_actual,
        'posicion_nueva' => $nueva_posicion,
        'direccion' => $direccion
    ]);
    
} catch (Exception $e) {
    // En caso de error
    if (isset($conexion)) {
        mysqli_close($conexion);
    }
    
    http_response_code(500);
    echo json_encode([
        'error' => 'Error del servidor: ' . $e->getMessage()
    ]);
}
?>

