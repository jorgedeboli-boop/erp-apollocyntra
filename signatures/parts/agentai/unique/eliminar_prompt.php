<?php
require_once __DIR__ . '/../../../include/session.php';
require_once __DIR__ . '/../../../include/functions.php';

header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(array('success' => false, 'message' => 'Método no permitido'));
    exit;
}

$id_prompt = isset($_POST['id_prompt']) ? (int) $_POST['id_prompt'] : 0;
if ($id_prompt <= 0) {
    echo json_encode(array('success' => false, 'message' => 'ID no válido'));
    exit;
}

$conexion = conectar_bd();
if (!$conexion) {
    echo json_encode(array('success' => false, 'message' => 'Sin conexión BD'));
    exit;
}

$stmt = mysqli_prepare($conexion, 'DELETE FROM ia_agent_prompts WHERE id_prompt = ? LIMIT 1');
if (!$stmt) {
    echo json_encode(array('success' => false, 'message' => mysqli_error($conexion)));
    mysqli_close($conexion);
    exit;
}
mysqli_stmt_bind_param($stmt, 'i', $id_prompt);
$ok = mysqli_stmt_execute($stmt);
$affected = mysqli_stmt_affected_rows($stmt);
mysqli_stmt_close($stmt);
mysqli_close($conexion);

if (!$ok || $affected < 1) {
    echo json_encode(array('success' => false, 'message' => 'No se eliminó el prompt'));
    exit;
}

echo json_encode(array('success' => true, 'message' => 'Prompt eliminado'));
