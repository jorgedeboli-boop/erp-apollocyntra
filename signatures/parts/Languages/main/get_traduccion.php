<?php
// Verificar que se ejecute solo por AJAX
if (!isset($_SERVER['HTTP_X_REQUESTED_WITH']) || strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) !== 'xmlhttprequest') {
    http_response_code(403);
    exit('Acceso denegado');
}

// Incluir archivos necesarios
require_once '../../../include/session.php';
require_once '../../../include/functions.php';

// Verificar sesión
if (!isset($_SESSION['usuario_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'No autorizado']);
    exit;
}

// Verificar que se pase el ID de la traducción
if (!isset($_POST['id']) || !is_numeric($_POST['id'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'ID de traducción requerido']);
    exit;
}

$id_traduccion = (int)$_POST['id'];

// Establecer conexión
$conexion = conectar_bd();
if (!$conexion) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Error de conexión a la base de datos']);
    exit;
}

try {
    // Consulta para obtener la traducción
    $query = "SELECT id_translations, entry_translate, exit_translate FROM Translations WHERE id_translations = ?";
    $stmt = mysqli_prepare($conexion, $query);
    
    if (!$stmt) {
        throw new Exception('Error en la consulta SQL');
    }
    
    mysqli_stmt_bind_param($stmt, 'i', $id_traduccion);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    
    if ($result && mysqli_num_rows($result) > 0) {
        $traduccion = mysqli_fetch_assoc($result);
        mysqli_stmt_close($stmt);
        mysqli_close($conexion);
        
        echo json_encode([
            'success' => true,
            'data' => $traduccion
        ]);
    } else {
        mysqli_stmt_close($stmt);
        mysqli_close($conexion);
        echo json_encode([
            'success' => false,
            'message' => 'Traducción no encontrada'
        ]);
    }
    
} catch (Exception $e) {
    if (isset($stmt)) {
        mysqli_stmt_close($stmt);
    }
    mysqli_close($conexion);
    
    error_log("Error al obtener traducción: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'Error interno del servidor'
    ]);
}
?>
