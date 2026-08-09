<?php
/**
 * Crea un registro cache para foto desde móvil (comprobante cobro plazo venta).
 * Usa fotos_app_adelantos_cache con id_venta + id_plazo_venta + id_sucursal.
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

    $id_venta = isset($json['id_venta']) ? (int) $json['id_venta'] : 0;
    $id_plazo = isset($json['id_plazo']) ? (int) $json['id_plazo'] : 0;
    $id_sucursal = isset($json['id_sucursal']) ? (int) $json['id_sucursal'] : 0;

    if ($id_venta <= 0 || $id_plazo <= 0 || $id_sucursal <= 0) {
        throw new Exception('Parámetros inválidos');
    }

    $conexion = conectar_bd();
    if (!$conexion) {
        throw new Exception('Sin conexión');
    }

    $stmt = mysqli_prepare(
        $conexion,
        'INSERT INTO fotos_app_adelantos_cache (
            nombre_foto,
            id_lote,
            id_sucursal,
            fecha_adelanto,
            id_venta,
            id_adelanto_venta,
            id_adelanto_empeno,
            id_renovacion_empeno,
            id_plazo_venta
        ) VALUES ("", 0, ?, CURDATE(), ?, 0, 0, 0, ?)'
    );
    if (!$stmt) {
        throw new Exception(mysqli_error($conexion));
    }
    mysqli_stmt_bind_param($stmt, 'iii', $id_sucursal, $id_venta, $id_plazo);
    if (!mysqli_stmt_execute($stmt)) {
        $err = mysqli_stmt_error($stmt);
        mysqli_stmt_close($stmt);
        throw new Exception($err);
    }
    $id_foto = (int) mysqli_insert_id($conexion);
    mysqli_stmt_close($stmt);
    mysqli_close($conexion);

    echo json_encode(['success' => true, 'id_foto' => $id_foto], JSON_UNESCAPED_UNICODE);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => $e->getMessage()], JSON_UNESCAPED_UNICODE);
}

