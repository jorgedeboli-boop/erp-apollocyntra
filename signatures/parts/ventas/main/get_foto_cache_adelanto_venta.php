<?php
/**
 * Devuelve nombre_foto del cache (adelanto capital venta) por id_foto + id_sucursal + id_venta.
 */
require_once __DIR__ . '/../../../include/session.php';
require_once __DIR__ . '/../../../include/functions.php';

header('Content-Type: application/json; charset=utf-8');

try {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        http_response_code(405);
        echo json_encode(['success' => false, 'error' => 'Método no permitido'], JSON_UNESCAPED_UNICODE);
        exit;
    }

    $raw = file_get_contents('php://input');
    $json = $raw ? json_decode($raw, true) : null;
    if (!is_array($json)) {
        throw new Exception('JSON inválido');
    }

    $id_foto = isset($json['id_foto']) ? (int) $json['id_foto'] : 0;
    $id_sucursal = isset($json['id_sucursal']) ? (int) $json['id_sucursal'] : 0;
    $id_venta = isset($json['id_venta']) ? (int) $json['id_venta'] : 0;

    if ($id_foto <= 0 || $id_sucursal <= 0 || $id_venta <= 0) {
        throw new Exception('Parámetros inválidos');
    }

    $conexion = conectar_bd();
    if (!$conexion) {
        throw new Exception('Sin conexión');
    }

    $stmt = mysqli_prepare(
        $conexion,
        'SELECT nombre_foto FROM fotos_app_adelantos_cache WHERE id_foto = ? AND id_sucursal = ? AND id_venta = ? LIMIT 1'
    );
    if (!$stmt) {
        throw new Exception(mysqli_error($conexion));
    }
    mysqli_stmt_bind_param($stmt, 'iii', $id_foto, $id_sucursal, $id_venta);
    mysqli_stmt_execute($stmt);
    $res = mysqli_stmt_get_result($stmt);
    $row = $res ? mysqli_fetch_assoc($res) : null;
    mysqli_stmt_close($stmt);
    mysqli_close($conexion);

    $nombre = $row && isset($row['nombre_foto']) ? trim((string) $row['nombre_foto']) : '';

    echo json_encode(
        [
            'success' => true,
            'nombre_foto' => $nombre,
            'tiene_foto' => $nombre !== '',
        ],
        JSON_UNESCAPED_UNICODE
    );
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => $e->getMessage()], JSON_UNESCAPED_UNICODE);
}

