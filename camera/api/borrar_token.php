<?php
require_once __DIR__ . '/../../include/session.php';
require_once __DIR__ . '/../../include/functions.php';

ob_start();
ob_clean();

header('Content-Type: application/json');

try {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        http_response_code(405);
        echo json_encode(array('error' => 'Método no permitido'));
        exit;
    }

    $json = file_get_contents('php://input');
    $data = json_decode($json, true);

    if (!$data) {
        throw new Exception('No se pudieron leer los datos JSON');
    }

    if (empty($data['id_token'])) {
        throw new Exception('ID de token no proporcionado');
    }
    if (empty($data['tipo_qr'])) {
        throw new Exception('Tipo de QR no proporcionado');
    }
    if (empty($data['id_item'])) {
        throw new Exception('ID de item no proporcionado');
    }
    if (!isset($data['id_sucursal']) && trim((string) ($data['tipo_qr'] ?? '')) !== 'cliente') {
        throw new Exception('ID de sucursal no proporcionado');
    }

    $id_token = (int) $data['id_token'];
    $tipo_qr = trim($data['tipo_qr']);
    $id_item = (int) $data['id_item'];
    $id_sucursal = isset($data['id_sucursal']) ? (int) $data['id_sucursal'] : 0;

    if ($id_token <= 0 || $id_item <= 0) {
        throw new Exception('Parámetros no válidos');
    }
    if ($tipo_qr === 'cliente') {
        $id_sucursal = 0;
    } elseif ($id_sucursal <= 0) {
        throw new Exception('Parámetros no válidos');
    }

    $conexion = conectar_bd();

    if ($tipo_qr === 'adelanto') {
        $query_delete_foto_cache = 'DELETE FROM fotos_app_adelantos_cache WHERE id_foto = ? AND id_sucursal = ?';
        $stmt_cache = mysqli_prepare($conexion, $query_delete_foto_cache);
        mysqli_stmt_bind_param($stmt_cache, 'ii', $id_item, $id_sucursal);
        mysqli_stmt_execute($stmt_cache);
        mysqli_stmt_close($stmt_cache);
    }

    $query = 'DELETE FROM tokens_actions WHERE id_token = ?';
    $stmt = mysqli_prepare($conexion, $query);
    mysqli_stmt_bind_param($stmt, 'i', $id_token);

    if (!mysqli_stmt_execute($stmt)) {
        throw new Exception('Error al borrar el token: ' . mysqli_stmt_error($stmt));
    }

    $filas_afectadas = mysqli_stmt_affected_rows($stmt);

    mysqli_stmt_close($stmt);
    mysqli_close($conexion);

    echo json_encode(array(
        'success' => true,
        'message' => 'Token borrado exitosamente',
        'filas_afectadas' => $filas_afectadas,
    ));
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(array(
        'success' => false,
        'error' => $e->getMessage(),
    ));
}
