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

// Verificar que sea una petición GET
if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
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

// Obtener ID del privilegio
$id_privilegio = intval($_GET['id'] ?? 0);

if ($id_privilegio <= 0) {
    http_response_code(400);
    echo json_encode(['error' => 'ID de privilegio inválido']);
    exit;
}

try {
    // Conectar a la base de datos
    $conexion = conectar_bd();
    
    if (!$conexion) {
        throw new Exception('Error de conexión a la base de datos');
    }
    
    // Consulta para obtener el privilegio
    $query = "SELECT id_privilegios, nombre_privilegio FROM privilegios_usuarios WHERE id_privilegios = ?";
    $stmt = mysqli_prepare($conexion, $query);
    
    if (!$stmt) {
        throw new Exception('Error al preparar la consulta: ' . mysqli_error($conexion));
    }
    
    mysqli_stmt_bind_param($stmt, 'i', $id_privilegio);
    $resultado = mysqli_stmt_execute($stmt);
    
    if (!$resultado) {
        throw new Exception('Error al ejecutar la consulta: ' . mysqli_stmt_error($stmt));
    }
    
    $result = mysqli_stmt_get_result($stmt);
    $privilegio = mysqli_fetch_assoc($result);
    
    if (!$privilegio) {
        throw new Exception('Privilegio no encontrado');
    }
    
    // Cerrar statement y conexión
    mysqli_stmt_close($stmt);
    mysqli_close($conexion);
    
    // Devolver respuesta JSON exitosa
    header('Content-Type: application/json');
    echo json_encode([
        'success' => true,
        'privilegio' => $privilegio
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
