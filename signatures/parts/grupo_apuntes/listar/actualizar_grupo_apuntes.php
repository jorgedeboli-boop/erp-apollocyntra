<?php
require_once '../../../../include/config.php';
require_once '../../../../include/check_session.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Método no permitido']);
    exit;
}

$id_grupo = intval($_POST['id_grupo'] ?? 0);
$nombre_grupo = trim($_POST['nombre_grupo'] ?? '');
$tipo_grupo = trim($_POST['tipo_grupo'] ?? '');

// Validaciones
if ($id_grupo <= 0) {
    echo json_encode(['success' => false, 'message' => 'ID de grupo no válido']);
    exit;
}

if (empty($nombre_grupo)) {
    echo json_encode(['success' => false, 'message' => 'El nombre del grupo es obligatorio']);
    exit;
}

if (empty($tipo_grupo)) {
    echo json_encode(['success' => false, 'message' => 'El tipo de grupo es obligatorio']);
    exit;
}

// Validar que el tipo sea uno de los permitidos
$tipos_permitidos = ['Entrada y salida', 'Entrada o Salida'];
if (!in_array($tipo_grupo, $tipos_permitidos)) {
    echo json_encode(['success' => false, 'message' => 'Tipo de grupo no válido']);
    exit;
}

try {
    // Verificar si ya existe otro grupo con el mismo nombre (excluyendo el actual)
    $stmt_check = $conn->prepare("SELECT id_grupo FROM grupos_movimientos WHERE nombre_grupo = ? AND id_grupo != ?");
    $stmt_check->bind_param('si', $nombre_grupo, $id_grupo);
    $stmt_check->execute();
    
    if ($stmt_check->get_result()->num_rows > 0) {
        echo json_encode(['success' => false, 'message' => 'Ya existe otro grupo con ese nombre']);
        exit;
    }
    
    // Verificar que el grupo existe
    $stmt_exists = $conn->prepare("SELECT id_grupo FROM grupos_movimientos WHERE id_grupo = ?");
    $stmt_exists->bind_param('i', $id_grupo);
    $stmt_exists->execute();
    
    if ($stmt_exists->get_result()->num_rows === 0) {
        echo json_encode(['success' => false, 'message' => 'El grupo no existe']);
        exit;
    }
    
    // Actualizar grupo
    $stmt_update = $conn->prepare("UPDATE grupos_movimientos SET nombre_grupo = ?, tipo_grupo = ? WHERE id_grupo = ?");
    $stmt_update->bind_param('ssi', $nombre_grupo, $tipo_grupo, $id_grupo);
    
    if ($stmt_update->execute()) {
        echo json_encode(['success' => true, 'message' => 'Grupo de apuntes actualizado exitosamente']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Error al actualizar el grupo de apuntes']);
    }
    
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Error interno del servidor']);
}
?>
