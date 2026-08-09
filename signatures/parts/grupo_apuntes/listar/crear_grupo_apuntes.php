<?php
require_once '../../../../include/config.php';
require_once '../../../../include/check_session.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Método no permitido']);
    exit;
}

$nombre_grupo = trim($_POST['nombre_grupo'] ?? '');
$tipo_grupo = trim($_POST['tipo_grupo'] ?? '');

// Validaciones
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
    // Verificar si ya existe un grupo con el mismo nombre
    $stmt_check = $conn->prepare("SELECT id_grupo FROM grupos_movimientos WHERE nombre_grupo = ?");
    $stmt_check->bind_param('s', $nombre_grupo);
    $stmt_check->execute();
    
    if ($stmt_check->get_result()->num_rows > 0) {
        echo json_encode(['success' => false, 'message' => 'Ya existe un grupo con ese nombre']);
        exit;
    }
    
    // Insertar nuevo grupo
    $stmt_insert = $conn->prepare("INSERT INTO grupos_movimientos (nombre_grupo, tipo_grupo) VALUES (?, ?)");
    $stmt_insert->bind_param('ss', $nombre_grupo, $tipo_grupo);
    
    if ($stmt_insert->execute()) {
        echo json_encode(['success' => true, 'message' => 'Grupo de apuntes creado exitosamente']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Error al crear el grupo de apuntes']);
    }
    
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Error interno del servidor']);
}
?>
