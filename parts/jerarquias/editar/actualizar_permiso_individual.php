<?php
// Verificar que el usuario esté autenticado
require_once '../../../include/session.php';

// Verificar que sea una petición POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Método no permitido']);
    exit();
}

// Obtener datos del formulario
$id_jerarquia = isset($_POST['id_jerarquia']) ? (int)$_POST['id_jerarquia'] : 0;
$item_id = isset($_POST['item_id']) ? (int)$_POST['item_id'] : 0;
$estado = isset($_POST['estado']) ? $_POST['estado'] : '';

if ($id_jerarquia <= 0 || $item_id <= 0 || empty($estado)) {
    echo json_encode(['success' => false, 'error' => 'Datos incompletos']);
    exit();
}

// Incluir funciones de base de datos
require_once '../../../include/functions.php';

try {
    $conexion = conectar_bd();
    
    if ($estado === 'activo') {
        // Verificar si ya existe el permiso
        $query_check = "SELECT COUNT(*) as total FROM relItemsLevel WHERE relIdItems = ? AND relIdUsersLevel = ?";
        $stmt = mysqli_prepare($conexion, $query_check);
        
        if (!$stmt) {
            throw new Exception('Error al preparar consulta: ' . mysqli_error($conexion));
        }
        
        mysqli_stmt_bind_param($stmt, "ii", $item_id, $id_jerarquia);
        mysqli_stmt_execute($stmt);
        
        // Compatible con PHP 7.0
        mysqli_stmt_store_result($stmt);
        mysqli_stmt_bind_result($stmt, $total);
        mysqli_stmt_fetch($stmt);
        
        mysqli_stmt_close($stmt);
        
        // Si no existe, crearlo
        if ($total == 0) {
            $query_insert = "INSERT INTO relItemsLevel (relIdItems, relIdUsersLevel) VALUES (?, ?)";
            $stmt = mysqli_prepare($conexion, $query_insert);
            
            if (!$stmt) {
                throw new Exception('Error al preparar inserción: ' . mysqli_error($conexion));
            }
            
            mysqli_stmt_bind_param($stmt, "ii", $item_id, $id_jerarquia);
            $resultado = mysqli_stmt_execute($stmt);
            
            if (!$resultado) {
                throw new Exception('Error al insertar permiso: ' . mysqli_stmt_error($stmt));
            }
            
            mysqli_stmt_close($stmt);
        }
        
    } else {
        // Eliminar el permiso si existe
        $query_delete = "DELETE FROM relItemsLevel WHERE relIdItems = ? AND relIdUsersLevel = ?";
        $stmt = mysqli_prepare($conexion, $query_delete);
        
        if (!$stmt) {
            throw new Exception('Error al preparar eliminación: ' . mysqli_error($conexion));
        }
        
        mysqli_stmt_bind_param($stmt, "ii", $item_id, $id_jerarquia);
        $resultado = mysqli_stmt_execute($stmt);
        
        if (!$resultado) {
            throw new Exception('Error al eliminar permiso: ' . mysqli_stmt_error($stmt));
        }
        
        mysqli_stmt_close($stmt);
    }
    
    mysqli_close($conexion);
    
    // Respuesta exitosa
    echo json_encode([
        'success' => true,
        'message' => 'Permiso actualizado correctamente'
    ]);
    
} catch (Exception $e) {
    // Log del error
    error_log("Error en actualizar_permiso_individual: " . $e->getMessage());
    
    echo json_encode([
        'success' => false, 
        'error' => 'Error al actualizar permiso: ' . $e->getMessage()
    ]);
}
?>
