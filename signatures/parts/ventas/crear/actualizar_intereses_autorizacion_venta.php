<?php
/**
 * Registra el nuevo interés autorizado y el precio resultante de la venta a plazos.
 */
require_once __DIR__ . '/../../../include/session.php';
require_once __DIR__ . '/../../../include/functions.php';

header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Método no permitido']);
    exit;
}

$idAutorizacion = isset($_POST['id_autorizacion']) ? (int) $_POST['id_autorizacion'] : 0;
$interesNuevo = isset($_POST['interes_nuevo']) ? (float) $_POST['interes_nuevo'] : null;
$precioNuevo = isset($_POST['precio_nuevo']) ? (float) $_POST['precio_nuevo'] : null;

if ($idAutorizacion <= 0 || $interesNuevo === null || $precioNuevo === null) {
    echo json_encode(['success' => false, 'message' => 'Faltan datos']);
    exit;
}

if ($interesNuevo < 0 || $interesNuevo > 100 || $precioNuevo < 0) {
    echo json_encode(['success' => false, 'message' => 'Valores no válidos']);
    exit;
}

try {
    $conexion = conectar_bd();
    if (!$conexion) {
        throw new Exception('Sin conexión');
    }

    $query = "UPDATE autorizaciones_porcentajes_ventas
              SET intereses_nuevos = ?, precio_nuevo = ?, estado = 'autorizada'
              WHERE id = ?";
    $stmt = mysqli_prepare($conexion, $query);
    if (!$stmt) {
        throw new Exception('Error al preparar consulta: ' . mysqli_error($conexion));
    }

    mysqli_stmt_bind_param($stmt, 'ddi', $interesNuevo, $precioNuevo, $idAutorizacion);
    if (!mysqli_stmt_execute($stmt)) {
        throw new Exception('Error al actualizar: ' . mysqli_stmt_error($stmt));
    }

    if (mysqli_stmt_affected_rows($stmt) !== 1) {
        mysqli_stmt_close($stmt);
        mysqli_close($conexion);
        echo json_encode(['success' => false, 'message' => 'No se encontró la autorización']);
        exit;
    }

    mysqli_stmt_close($stmt);
    mysqli_close($conexion);

    echo json_encode(['success' => true]);
} catch (Throwable $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
