<?php
/**
 * Comprueba el código de autorización de cambio de intereses (venta a plazos).
 */
require_once __DIR__ . '/../../../include/session.php';
require_once __DIR__ . '/../../../include/functions.php';

header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Método no permitido']);
    exit;
}

$idAutorizacion = isset($_POST['id_autorizacion']) ? (int) $_POST['id_autorizacion'] : 0;
$codigo = isset($_POST['codigo']) ? trim((string) $_POST['codigo']) : '';

if ($idAutorizacion <= 0 || $codigo === '') {
    echo json_encode(['success' => false, 'message' => 'Faltan datos']);
    exit;
}

try {
    $conexion = conectar_bd();
    if (!$conexion) {
        throw new Exception('Sin conexión');
    }

    $query = "SELECT id, codigo, estado
              FROM autorizaciones_porcentajes_ventas
              WHERE id = ? AND codigo = ? LIMIT 1";
    $stmt = mysqli_prepare($conexion, $query);
    if (!$stmt) {
        throw new Exception('Error al preparar consulta: ' . mysqli_error($conexion));
    }

    mysqli_stmt_bind_param($stmt, 'is', $idAutorizacion, $codigo);
    mysqli_stmt_execute($stmt);
    $res = mysqli_stmt_get_result($stmt);
    $row = $res ? mysqli_fetch_assoc($res) : null;
    mysqli_stmt_close($stmt);
    mysqli_close($conexion);

    if (!$row) {
        echo json_encode(['success' => false, 'message' => 'Código incorrecto']);
        exit;
    }

    if (strtolower((string) ($row['estado'] ?? '')) === 'usada') {
        echo json_encode(['success' => false, 'message' => 'La autorización ya fue utilizada']);
        exit;
    }

    echo json_encode(['success' => true]);
} catch (Throwable $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
