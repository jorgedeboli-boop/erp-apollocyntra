<?php
/**
 * Cantidad de fotos del artículo (polling QR móvil).
 */
require_once __DIR__ . '/../../../include/session.php';
require_once __DIR__ . '/../../../include/functions.php';

header('Content-Type: application/json; charset=utf-8');

try {
    $tipo = isset($_GET['tipo']) ? trim((string) $_GET['tipo']) : '';
    $id_item = isset($_GET['id_item']) ? (int) $_GET['id_item'] : 0;

    if ($tipo !== 'articulo' || $id_item <= 0) {
        throw new Exception('Parámetros insuficientes');
    }

    $conexion = conectar_bd();
    if (!$conexion) {
        throw new Exception('Sin conexión a la base de datos');
    }

    $query = 'SELECT COUNT(*) AS cantidad FROM articulos_venta_imagenes WHERE rel_sku_articulo = ?';
    $stmt = mysqli_prepare($conexion, $query);
    if (!$stmt) {
        throw new Exception(mysqli_error($conexion));
    }
    mysqli_stmt_bind_param($stmt, 'i', $id_item);
    mysqli_stmt_execute($stmt);
    $cantidad = 0;
    if (function_exists('mysqli_stmt_get_result')) {
        $resultado = mysqli_stmt_get_result($stmt);
        $row = $resultado ? mysqli_fetch_assoc($resultado) : null;
        $cantidad = (int) ($row['cantidad'] ?? 0);
    } else {
        mysqli_stmt_bind_result($stmt, $cantidad);
        mysqli_stmt_fetch($stmt);
        $cantidad = (int) $cantidad;
    }
    mysqli_stmt_close($stmt);
    mysqli_close($conexion);

    echo json_encode(array(
        'success' => true,
        'cantidad' => $cantidad,
        'tipo' => $tipo,
    ));
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(array(
        'success' => false,
        'error' => $e->getMessage(),
    ));
}
