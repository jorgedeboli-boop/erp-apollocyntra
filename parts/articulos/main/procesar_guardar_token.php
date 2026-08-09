<?php
require_once '../../../include/session.php';
require_once '../../../include/functions.php';

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

    if (!isset($data['tipo_qr']) || !isset($data['id_item']) || !isset($data['token']) || !isset($data['id_sucursal'])) {
        throw new Exception('Faltan parámetros obligatorios');
    }

    $tipo_qr = trim($data['tipo_qr']);
    $id_item = (int) $data['id_item'];
    $token = trim($data['token']);
    $id_sucursal = (int) $data['id_sucursal'];

    if (!in_array($tipo_qr, array('articulo'), true)) {
        throw new Exception('Tipo no válido para esta ruta');
    }

    if ($id_item <= 0) {
        throw new Exception('ID de item no válido');
    }

    if (empty($token)) {
        throw new Exception('Token no puede estar vacío');
    }

    if ($id_sucursal <= 0) {
        throw new Exception('ID de sucursal no válido');
    }

    $conexion = conectar_bd();

    $state_token = 'true';
    $query = "
        INSERT INTO tokens_actions 
        (token_string, state_token, id_item, type_item, sucursal_token, fecha_token) 
        VALUES (?, ?, ?, ?, ?, CURDATE())
    ";

    $stmt = mysqli_prepare($conexion, $query);
    mysqli_stmt_bind_param($stmt, 'ssisi', $token, $state_token, $id_item, $tipo_qr, $id_sucursal);

    if (!mysqli_stmt_execute($stmt)) {
        throw new Exception('Error al guardar el token: ' . mysqli_stmt_error($stmt));
    }

    $id_token_insertado = mysqli_insert_id($conexion);

    mysqli_stmt_close($stmt);
    mysqli_close($conexion);

    echo json_encode(array(
        'success' => true,
        'message' => 'Token guardado exitosamente',
        'id_token' => $id_token_insertado,
        'token' => $token
    ));
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(array(
        'success' => false,
        'error' => $e->getMessage()
    ));
}
