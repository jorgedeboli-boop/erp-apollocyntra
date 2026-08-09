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

    if (!isset($data['id_token']) || empty($data['id_token'])) {
        throw new Exception('ID de token no proporcionado');
    }

    $id_token = (int) $data['id_token'];

    if ($id_token <= 0) {
        throw new Exception('ID de token no válido');
    }

    $conexion = conectar_bd();

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
        'filas_afectadas' => $filas_afectadas
    ));
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(array(
        'success' => false,
        'error' => $e->getMessage()
    ));
}
