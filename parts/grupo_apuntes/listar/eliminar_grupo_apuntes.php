<?php
require_once '../../../../include/config.php';
require_once '../../../../include/check_session.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Método no permitido']);
    exit;
}

$id_grupo = intval($_POST['id_grupo'] ?? 0);

// Validaciones
if ($id_grupo <= 0) {
    echo json_encode(['success' => false, 'message' => 'ID de grupo no válido']);
    exit;
}

try {
    // Verificar que el grupo existe
    $stmt_exists = $conn->prepare("SELECT id_grupo, nombre_grupo FROM grupos_movimientos WHERE id_grupo = ?");
    $stmt_exists->bind_param('i', $id_grupo);
    $stmt_exists->execute();
    $result = $stmt_exists->get_result();
    
    if ($result->num_rows === 0) {
        echo json_encode(['success' => false, 'message' => 'El grupo no existe']);
        exit;
    }
    
    $grupo = $result->fetch_assoc();
    
    // Aquí podrías agregar validaciones adicionales
    // Por ejemplo, verificar si el grupo está siendo usado en otras tablas
    
    // Eliminar grupo
    $stmt_delete = $conn->prepare("DELETE FROM grupos_movimientos WHERE id_grupo = ?");
    $stmt_delete->bind_param('i', $id_grupo);
    
    if ($stmt_delete->execute()) {
        echo json_encode([
            'success' => true, 
            'message' => "Grupo '{$grupo['nombre_grupo']}' eliminado exitosamente"
        ]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Error al eliminar el grupo de apuntes']);
    }
    
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Error interno del servidor']);
}
?>
