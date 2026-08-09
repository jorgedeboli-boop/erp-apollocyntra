<?php
/**
 * Comprueba el código de autorización de devolución y marca la autorización como autorizada.
 * No crea la fila en devoluciones (desde la ficha del artículo: insertar_devolucion_desde_articulo.php).
 * POST: id_DEVO, codigo_DEVO
 */

require_once __DIR__ . '/../../../include/session.php';
require_once __DIR__ . '/../../../include/functions.php';

header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['same_code' => 'ko']);
    exit;
}

$id_devo = isset($_POST['id_DEVO']) ? (int) $_POST['id_DEVO'] : 0;
$codigo_devo = isset($_POST['codigo_DEVO']) ? strtoupper(trim((string) $_POST['codigo_DEVO'])) : '';

if ($id_devo <= 0 || $codigo_devo === '') {
    echo json_encode(['same_code' => 'ko']);
    exit;
}

$conexion = conectar_bd();
$sql = 'SELECT id_autorizacion, codigo_autorizacion, sku_articulo_devolucion, estado_autorizacion
        FROM autorizaciones_devoluciones WHERE id_autorizacion = ? LIMIT 1';
$stmt = mysqli_prepare($conexion, $sql);
if (!$stmt) {
    mysqli_close($conexion);
    echo json_encode(['same_code' => 'ko']);
    exit;
}

mysqli_stmt_bind_param($stmt, 'i', $id_devo);
mysqli_stmt_execute($stmt);
$res = mysqli_stmt_get_result($stmt);
$row = $res ? mysqli_fetch_assoc($res) : null;
mysqli_stmt_close($stmt);

if (!$row || empty($row['id_autorizacion'])) {
    mysqli_close($conexion);
    echo json_encode(['same_code' => 'ko']);
    exit;
}

if ((string) $row['codigo_autorizacion'] !== $codigo_devo) {
    mysqli_close($conexion);
    echo json_encode(['same_code' => 'ko']);
    exit;
}

if ($row['estado_autorizacion'] !== 'pendiente') {
    mysqli_close($conexion);
    echo json_encode(['same_code' => 'ko', 'message' => 'Estado de autorización no válido']);
    exit;
}

$upd = mysqli_prepare(
    $conexion,
    'UPDATE autorizaciones_devoluciones SET estado_autorizacion = \'autorizada\' WHERE id_autorizacion = ?'
);
if (!$upd) {
    mysqli_close($conexion);
    echo json_encode(['same_code' => 'ko']);
    exit;
}
mysqli_stmt_bind_param($upd, 'i', $id_devo);
mysqli_stmt_execute($upd);
mysqli_stmt_close($upd);

$id_articulo = (int) $row['sku_articulo_devolucion'];

mysqli_close($conexion);

echo json_encode([
    'same_code' => 'ok',
    'id_autorizacion' => $id_devo,
    'id_articulo' => $id_articulo,
]);
