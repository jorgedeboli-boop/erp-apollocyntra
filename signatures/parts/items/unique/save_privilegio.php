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
    $nombre_privilegio = trim($_POST['nombrePrivilegio'] ?? '');
    $id_privilegio = trim($_POST['idPrivilegio'] ?? '');
    
    // Validar datos
    if (empty($nombre_privilegio)) {
        throw new Exception('El nombre del privilegio es obligatorio');
    }
    
    // Conectar a la base de datos
    $conexion = conectar_bd();
    
    if (!$conexion) {
        throw new Exception('Error de conexión a la base de datos');
    }
    
    // Verificar si es una inserción o actualización
    if (empty($id_privilegio)) {
        // INSERT - Nueva jerarquía
        $query = "INSERT INTO privilegios_usuarios (nombre_privilegio) VALUES (?)";
        $stmt = mysqli_prepare($conexion, $query);
        
        if (!$stmt) {
            throw new Exception('Error al preparar la consulta: ' . mysqli_error($conexion));
        }
        
        mysqli_stmt_bind_param($stmt, 's', $nombre_privilegio);
        $resultado = mysqli_stmt_execute($stmt);
        
        if (!$resultado) {
            throw new Exception('Error al insertar: ' . mysqli_stmt_error($stmt));
        }
        
        $nuevo_id = mysqli_insert_id($conexion);
        $mensaje = 'Jerarquía creada correctamente';
        $tipo = 'insert';
        
    } else {
        // UPDATE - Actualizar jerarquía existente
        $query = "UPDATE privilegios_usuarios SET nombre_privilegio = ? WHERE id_privilegios = ?";
        $stmt = mysqli_prepare($conexion, $query);
        
        if (!$stmt) {
            throw new Exception('Error al preparar la consulta: ' . mysqli_error($conexion));
        }
        
        mysqli_stmt_bind_param($stmt, 'si', $nombre_privilegio, $id_privilegio);
        $resultado = mysqli_stmt_execute($stmt);
        
        if (!$resultado) {
            throw new Exception('Error al actualizar: ' . mysqli_stmt_error($stmt));
        }
        
        $nuevo_id = $id_privilegio;
        $mensaje = 'Jerarquía actualizada correctamente';
        $tipo = 'update';
    }
    
    // Cerrar statement y conexión
    mysqli_stmt_close($stmt);
    mysqli_close($conexion);
    
    // Devolver respuesta JSON exitosa
    header('Content-Type: application/json');
    echo json_encode([
        'success' => true,
        'message' => $mensaje,
        'tipo' => $tipo,
        'id_privilegio' => $nuevo_id,
        'nombre_privilegio' => $nombre_privilegio
    ]);
    
} catch (Exception $e) {
    // En caso de error
    if (isset($stmt)) {
        mysqli_stmt_close($stmt);
    }
    if (isset($conexion)) {
        mysqli_close($conexion);
    }
    
    http_response_code(500);
    echo json_encode([
        'error' => 'Error del servidor: ' . $e->getMessage()
    ]);
}
?>
