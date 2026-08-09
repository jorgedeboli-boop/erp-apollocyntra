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
    $id = intval($_POST['id'] ?? 0);
    $state_item = $_POST['state_item'] ?? '';
    
    // Validar datos
    if ($id <= 0) {
        throw new Exception('ID de item inválido');
    }
    
    if (!in_array($state_item, ['true', 'false'])) {
        throw new Exception('Estado inválido');
    }
    
    // Conectar a la base de datos
    $conexion = conectar_bd();
    
    if (!$conexion) {
        throw new Exception('Error de conexión a la base de datos');
    }
    
    // Actualizar el estado del item
    $query = "UPDATE itemsSections SET state_item = ? WHERE id_type_Item = ?";
    $stmt = mysqli_prepare($conexion, $query);
    
    if (!$stmt) {
        throw new Exception('Error al preparar la consulta: ' . mysqli_error($conexion));
    }
    
    mysqli_stmt_bind_param($stmt, 'si', $state_item, $id);
    $resultado = mysqli_stmt_execute($stmt);
    
    if (!$resultado) {
        throw new Exception('Error al actualizar: ' . mysqli_stmt_error($stmt));
    }
    
    // Verificar que se haya actualizado al menos una fila
    if (mysqli_stmt_affected_rows($stmt) == 0) {
        throw new Exception('No se encontró el item especificado');
    }
    
    // Cerrar statement y conexión
    mysqli_stmt_close($stmt);
    mysqli_close($conexion);
    
    // Devolver respuesta JSON exitosa
    header('Content-Type: application/json');
    echo json_encode([
        'success' => true,
        'message' => 'Estado del item actualizado correctamente',
        'state_item' => $state_item
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
