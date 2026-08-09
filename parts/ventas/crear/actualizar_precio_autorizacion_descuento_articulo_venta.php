<?php
/**
 * Actualiza `precio_nuevo` y marca la autorización como `autorizada`.
 */
require_once __DIR__ . '/../../../include/session.php';
require_once __DIR__ . '/../../../include/functions.php';

header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Método no permitido']);
    exit;
}

$idAutorizacion = isset($_POST['id_autorizacion']) ? (int)$_POST['id_autorizacion'] : 0;
$precioNuevo = isset($_POST['precio_nuevo']) ? (float)$_POST['precio_nuevo'] : null;

if ($idAutorizacion <= 0 || $precioNuevo === null || $precioNuevo < 0) {
    echo json_encode(['success' => false, 'message' => 'Faltan datos']);
    exit;
}

try {
    $conexion = conectar_bd();

    $query = "UPDATE autorizaciones_descuento_articulo_venta
              SET precio_nuevo = ?, estado = 'autorizada'
              WHERE id = ?";
    $stmt = mysqli_prepare($conexion, $query);
    if (!$stmt) {
        throw new Exception('Error al preparar consulta: ' . mysqli_error($conexion));
    }

    mysqli_stmt_bind_param($stmt, 'di', $precioNuevo, $idAutorizacion);
    if (!mysqli_stmt_execute($stmt)) {
        throw new Exception('Error al actualizar: ' . mysqli_stmt_error($stmt));
    }

    mysqli_stmt_close($stmt);
    mysqli_close($conexion);

    echo json_encode(['success' => true]);
} catch (Throwable $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}

