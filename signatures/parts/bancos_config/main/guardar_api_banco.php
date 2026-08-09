<?php
require_once '../../../include/session.php';
require_once '../../../include/functions.php';

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-cache, must-revalidate');

try {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        http_response_code(405);
        echo json_encode(['success' => false, 'message' => 'Método no permitido']);
        exit;
    }

    if (!isset($_SESSION['usuario_id'])) {
        http_response_code(401);
        echo json_encode(['success' => false, 'message' => 'No autorizado']);
        exit;
    }

    $id_api = isset($_POST['id_api']) ? (int) $_POST['id_api'] : 0;
    $rel_id_banco = isset($_POST['rel_id_banco']) ? (int) $_POST['rel_id_banco'] : 0;
    $id_comercio_api = isset($_POST['id_comercio_api']) ? (int) $_POST['id_comercio_api'] : 0;
    $api_key = isset($_POST['api_key']) ? trim((string) $_POST['api_key']) : '';
    $token_value = isset($_POST['token_value']) ? substr(trim((string) $_POST['token_value']), 0, 168) : '';
    $secret_api_key = isset($_POST['secret_api_key']) ? trim((string) $_POST['secret_api_key']) : '';
    $url_api = isset($_POST['url_api']) ? trim((string) $_POST['url_api']) : '';
    $estado_api = isset($_POST['estado_api']) && $_POST['estado_api'] === 'true' ? 'true' : 'false';

    if ($rel_id_banco <= 0) {
        echo json_encode(['success' => false, 'message' => 'Banco no válido']);
        exit;
    }
    if ($id_comercio_api <= 0) {
        echo json_encode(['success' => false, 'message' => 'El ID cliente es obligatorio']);
        exit;
    }
    if ($api_key === '' || $token_value === '' || $secret_api_key === '' || $url_api === '') {
        echo json_encode(['success' => false, 'message' => 'API Key, Token, Secret y URL son obligatorios']);
        exit;
    }

    $conexion = conectar_bd();

    $stmtBanco = mysqli_prepare($conexion, 'SELECT id_banco FROM bancos_config WHERE id_banco = ? LIMIT 1');
    mysqli_stmt_bind_param($stmtBanco, 'i', $rel_id_banco);
    mysqli_stmt_execute($stmtBanco);
    if (!mysqli_fetch_assoc(mysqli_stmt_get_result($stmtBanco))) {
        mysqli_stmt_close($stmtBanco);
        mysqli_close($conexion);
        echo json_encode(['success' => false, 'message' => 'El banco no existe']);
        exit;
    }
    mysqli_stmt_close($stmtBanco);

    if ($id_api > 0) {
        $stmtCheck = mysqli_prepare(
            $conexion,
            'SELECT id_api FROM apis_bancos WHERE id_api = ? AND rel_id_banco = ? LIMIT 1'
        );
        mysqli_stmt_bind_param($stmtCheck, 'ii', $id_api, $rel_id_banco);
        mysqli_stmt_execute($stmtCheck);
        $existe = mysqli_fetch_assoc(mysqli_stmt_get_result($stmtCheck));
        mysqli_stmt_close($stmtCheck);

        if (!$existe) {
            mysqli_close($conexion);
            echo json_encode(['success' => false, 'message' => 'Configuración API no encontrada']);
            exit;
        }

        $stmt = mysqli_prepare(
            $conexion,
            'UPDATE apis_bancos SET
                api_key = ?,
                token_value = ?,
                secret_api_key = ?,
                url_api = ?,
                estado_api = ?,
                id_comercio_api = ?
             WHERE id_api = ? AND rel_id_banco = ?'
        );
        mysqli_stmt_bind_param(
            $stmt,
            'sssssiii',
            $api_key,
            $token_value,
            $secret_api_key,
            $url_api,
            $estado_api,
            $id_comercio_api,
            $id_api,
            $rel_id_banco
        );
        if (!mysqli_stmt_execute($stmt)) {
            throw new Exception(mysqli_stmt_error($stmt));
        }
        mysqli_stmt_close($stmt);
        mysqli_close($conexion);

        echo json_encode(['success' => true, 'message' => 'API actualizada correctamente', 'id_api' => $id_api]);
        exit;
    }

    $stmtInsert = mysqli_prepare(
        $conexion,
        'INSERT INTO apis_bancos
            (api_key, token_value, secret_api_key, url_api, rel_id_banco, estado_api, id_comercio_api, fecha_creacion)
         VALUES (?, ?, ?, ?, ?, ?, ?, CURDATE())'
    );
    mysqli_stmt_bind_param(
        $stmtInsert,
        'ssssisi',
        $api_key,
        $token_value,
        $secret_api_key,
        $url_api,
        $rel_id_banco,
        $estado_api,
        $id_comercio_api
    );
    if (!mysqli_stmt_execute($stmtInsert)) {
        throw new Exception(mysqli_stmt_error($stmtInsert));
    }
    $nuevo_id = (int) mysqli_insert_id($conexion);
    mysqli_stmt_close($stmtInsert);
    mysqli_close($conexion);

    echo json_encode(['success' => true, 'message' => 'API creada correctamente', 'id_api' => $nuevo_id]);
} catch (Exception $e) {
    if (isset($conexion) && $conexion instanceof mysqli) {
        mysqli_close($conexion);
    }
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Error al guardar la API: ' . $e->getMessage()]);
}
