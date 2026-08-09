<?php
/**
 * Consulta si el token QR del artículo fue utilizado (misma lógica que
 * parts/lotes/main/procesar_consultar_token.php, solo tokens type_item = articulo).
 */
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

    if (!isset($data['token']) || $data['token'] === '') {
        throw new Exception('Token no proporcionado');
    }
    if (!isset($data['id_token']) || $data['id_token'] === '') {
        throw new Exception('ID de token no proporcionado');
    }

    $id_token = (int) $data['id_token'];
    $token = trim($data['token']);

    $conexion = conectar_bd();

    $query = "
        SELECT *
        FROM tokens_actions 
        WHERE id_token = ? AND state_token = 'false' AND type_item = 'articulo'
    ";

    $stmt = mysqli_prepare($conexion, $query);
    mysqli_stmt_bind_param($stmt, 'i', $id_token);
    mysqli_stmt_execute($stmt);
    $resultado = mysqli_stmt_get_result($stmt);

    if ($row = mysqli_fetch_assoc($resultado)) {
        $token_data = array(
            'id_token' => $id_token,
            'id_item' => $row['id_item'],
            'type_item' => $row['type_item'],
            'fecha_token' => $row['fecha_token']
        );

        mysqli_stmt_close($stmt);

        $query_delete = 'DELETE FROM tokens_actions WHERE id_token = ?';
        $stmt_delete = mysqli_prepare($conexion, $query_delete);
        mysqli_stmt_bind_param($stmt_delete, 'i', $id_token);
        mysqli_stmt_execute($stmt_delete);
        mysqli_stmt_close($stmt_delete);
        mysqli_close($conexion);

        echo json_encode(array(
            'success' => true,
            'utilizado' => true,
            'mensaje' => 'El token ya fue utilizado',
            'token_data' => $token_data
        ));
    } else {
        mysqli_stmt_close($stmt);
        mysqli_close($conexion);

        echo json_encode(array(
            'success' => true,
            'utilizado' => false,
            'mensaje' => 'El token aún no ha sido utilizado'
        ));
    }
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(array(
        'success' => false,
        'error' => $e->getMessage()
    ));
}
