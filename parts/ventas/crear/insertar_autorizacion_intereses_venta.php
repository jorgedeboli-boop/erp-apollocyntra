<?php
/**
 * Solicitud de autorización para cambiar el interés de una venta a plazos.
 */
require_once __DIR__ . '/../../../include/session.php';
require_once __DIR__ . '/../../../include/functions.php';

header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Método no permitido']);
    exit;
}

$sucursal = isset($_POST['sucursal']) ? (int) $_POST['sucursal'] : 0;
$idsArticulos = isset($_POST['ids_articulos']) ? trim((string) $_POST['ids_articulos']) : '';
$interesesOriginales = isset($_POST['intereses_originales']) ? (float) $_POST['intereses_originales'] : 0.0;
$precioOriginal = isset($_POST['precio_original']) ? (float) $_POST['precio_original'] : 0.0;
$usuarioId = isset($_SESSION['usuario_id']) ? (int) $_SESSION['usuario_id'] : 0;

if ($idsArticulos === '' || $usuarioId <= 0) {
    echo json_encode(['success' => false, 'message' => 'Faltan datos']);
    exit;
}

if ($precioOriginal < 0 || $interesesOriginales < 0) {
    echo json_encode(['success' => false, 'message' => 'Datos de importe no válidos']);
    exit;
}

try {
    $conexion = conectar_bd();
    if (!$conexion) {
        throw new Exception('Sin conexión a la base de datos');
    }

    $codigo = generar_clave(5);
    $usuarioStr = (string) $usuarioId;

    $query = "INSERT INTO autorizaciones_porcentajes_ventas (
                sucursal,
                usuario,
                codigo,
                id_articulo,
                intereses_originales,
                precio_original,
                estado,
                fecha
              ) VALUES (?, ?, ?, ?, ?, ?, 'pendiente', NOW())";

    $stmt = mysqli_prepare($conexion, $query);
    if (!$stmt) {
        throw new Exception('Error al preparar consulta: ' . mysqli_error($conexion));
    }

    mysqli_stmt_bind_param(
        $stmt,
        'isssdd',
        $sucursal,
        $usuarioStr,
        $codigo,
        $idsArticulos,
        $interesesOriginales,
        $precioOriginal
    );

    if (!mysqli_stmt_execute($stmt)) {
        throw new Exception('Error al insertar autorización: ' . mysqli_stmt_error($stmt));
    }

    $idAuto = (int) mysqli_insert_id($conexion);
    mysqli_stmt_close($stmt);
    mysqli_close($conexion);

    echo json_encode([
        'success' => true,
        'id_autorizacion' => $idAuto,
        'autorizacion' => $idAuto,
        'codigo' => $codigo,
    ]);
} catch (Throwable $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
