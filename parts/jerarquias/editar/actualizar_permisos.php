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
$permisos_json = isset($_POST['permisos']) ? $_POST['permisos'] : '';

if ($id_jerarquia <= 0 || empty($permisos_json)) {
    echo json_encode(['success' => false, 'error' => 'Datos incompletos']);
    exit();
}

// Decodificar permisos
$permisos = json_decode($permisos_json, true);

if (!$permisos) {
    echo json_encode(['success' => false, 'error' => 'Formato de permisos inválido']);
    exit();
}

// Incluir funciones de base de datos
require_once '../../../include/functions.php';

try {
    $conexion = conectar_bd();
    
    // Iniciar transacción
    mysqli_autocommit($conexion, false);
    
    // 1. Obtener permisos actuales de esta jerarquía
    $query_actuales = "SELECT relIdItems FROM relItemsLevel WHERE relIdUsersLevel = ?";
    $stmt = mysqli_prepare($conexion, $query_actuales);
    
    if (!$stmt) {
        throw new Exception('Error al preparar consulta: ' . mysqli_error($conexion));
    }
    
    mysqli_stmt_bind_param($stmt, "i", $id_jerarquia);
    mysqli_stmt_execute($stmt);
    
    // Compatible con PHP 7.0
    mysqli_stmt_store_result($stmt);
    mysqli_stmt_bind_result($stmt, $relIdItems);
    
    $permisos_actuales = [];
    while (mysqli_stmt_fetch($stmt)) {
        $permisos_actuales[] = $relIdItems;
    }
    
    mysqli_stmt_close($stmt);
    
    // 2. Procesar cada item
    foreach ($permisos as $item_id => $estado) {
        $item_id = (int)$item_id;
        
        if ($estado === 'activo') {
            // Si no existe el permiso, crearlo
            if (!in_array($item_id, $permisos_actuales)) {
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
            // Si existe el permiso y se marca como no activo, eliminarlo
            if (in_array($item_id, $permisos_actuales)) {
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
        }
    }
    
    // Confirmar transacción
    mysqli_commit($conexion);
    mysqli_close($conexion);
    
    // Respuesta exitosa
    echo json_encode([
        'success' => true,
        'message' => 'Permisos actualizados correctamente'
    ]);
    
} catch (Exception $e) {
    // Revertir transacción en caso de error
    if (isset($conexion)) {
        mysqli_rollback($conexion);
        mysqli_close($conexion);
    }
    
    // Log del error
    error_log("Error en actualizar_permisos: " . $e->getMessage());
    
    echo json_encode([
        'success' => false, 
        'error' => 'Error al actualizar permisos: ' . $e->getMessage()
    ]);
}
?>
