<?php
/**
 * Inserta una solicitud de autorización para cambiar el precio de un artículo (descuento).
 * Devuelve el id insertado en `autorizaciones_descuento_articulo_venta`.
 */
require_once __DIR__ . '/../../../include/session.php';
require_once __DIR__ . '/../../../include/functions.php';

header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Método no permitido']);
    exit;
}

$idArticulo = isset($_POST['id_articulo']) ? (int)$_POST['id_articulo'] : 0;
$sucursal = isset($_POST['sucursal']) ? (int)$_POST['sucursal'] : 0;
$precioOriginal = isset($_POST['precio_original']) ? (float)$_POST['precio_original'] : null;

$usuario = $_SESSION['usuario_nombre'] ?? '';
$usuario = is_string($usuario) ? trim($usuario) : '';

if ($idArticulo <= 0 || $sucursal <= 0 || $precioOriginal === null || $precioOriginal < 0 || $usuario === '') {
    echo json_encode(['success' => false, 'message' => 'Faltan datos']);
    exit;
}

try {
    $conexion = conectar_bd();

    $codigo = generar_clave(5);

    $query = "INSERT INTO autorizaciones_descuento_articulo_venta (sucursal, usuario, codigo, id_articulo, precio_original)
              VALUES (?, ?, ?, ?, ?)";

    $stmt = mysqli_prepare($conexion, $query);
    if (!$stmt) {
        throw new Exception('Error al preparar consulta: ' . mysqli_error($conexion));
    }

    mysqli_stmt_bind_param($stmt, 'issid', $sucursal, $usuario, $codigo, $idArticulo, $precioOriginal);

    if (!mysqli_stmt_execute($stmt)) {
        throw new Exception('Error al insertar autorización: ' . mysqli_stmt_error($stmt));
    }

    $idAuto = (int)mysqli_insert_id($conexion);

    mysqli_stmt_close($stmt);
    mysqli_close($conexion);

    echo json_encode(['success' => true, 'id_autorizacion' => $idAuto]);
} catch (Throwable $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}

