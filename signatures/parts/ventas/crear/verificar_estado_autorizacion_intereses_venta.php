<?php
/**
 * Consulta el estado de una autorización de cambio de intereses (polling desde nueva venta).
 * POST: id_autorizacion (int)
 */
require_once __DIR__ . '/../../../include/session.php';
require_once __DIR__ . '/../../../include/functions.php';

header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'autorizada' => false]);
    exit;
}

$idAutorizacion = isset($_POST['id_autorizacion']) ? (int) $_POST['id_autorizacion'] : 0;
if ($idAutorizacion <= 0) {
    echo json_encode(['success' => false, 'autorizada' => false]);
    exit;
}

$conexion = conectar_bd();
if (!$conexion) {
    echo json_encode(['success' => false, 'autorizada' => false, 'message' => 'Error de conexión']);
    exit;
}

$sql = 'SELECT id, estado, intereses_originales, intereses_nuevos, precio_nuevo
        FROM autorizaciones_porcentajes_ventas
        WHERE id = ?
        LIMIT 1';
$stmt = mysqli_prepare($conexion, $sql);
if (!$stmt) {
    mysqli_close($conexion);
    echo json_encode(['success' => false, 'autorizada' => false]);
    exit;
}

mysqli_stmt_bind_param($stmt, 'i', $idAutorizacion);
mysqli_stmt_execute($stmt);
$res = mysqli_stmt_get_result($stmt);
$row = $res ? mysqli_fetch_assoc($res) : null;
mysqli_stmt_close($stmt);
mysqli_close($conexion);

if (!$row) {
    echo json_encode(['success' => false, 'autorizada' => false, 'message' => 'Autorización no encontrada']);
    exit;
}

$estado = (string) ($row['estado'] ?? '');
$autorizada = (strtolower($estado) === 'autorizada');

echo json_encode([
    'success' => true,
    'autorizada' => $autorizada,
    'estado' => $estado,
    'id_autorizacion' => (int) $row['id'],
    'intereses_nuevos' => isset($row['intereses_nuevos']) ? (float) $row['intereses_nuevos'] : null,
    'precio_nuevo' => isset($row['precio_nuevo']) ? (float) $row['precio_nuevo'] : null,
]);
