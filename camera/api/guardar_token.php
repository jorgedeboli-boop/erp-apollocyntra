<?php
require_once __DIR__ . '/../../include/session.php';
require_once __DIR__ . '/../../include/functions.php';
require_once __DIR__ . '/../lib/camera_qr_multifoto.php';

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

    if (!isset($data['tipo_qr']) || !isset($data['id_item']) || !isset($data['token'])) {
        if (!isset($data['tipo_qr'])) {
            throw new Exception('Falta el parámetro tipo_qr');
        }
        if (!isset($data['id_item'])) {
            throw new Exception('Falta el parámetro id_item');
        }
        throw new Exception('Falta el parámetro token');
    }

    $tipo_qr = trim($data['tipo_qr']);
    $id_item = (int) $data['id_item'];
    $token = trim($data['token']);

    $tipos_validos = array('cliente', 'lote', 'gasto', 'gasto_prueba', 'renovacion', 'adelanto', 'articulo', 'venta', 'articulo_venta', 'adelanto_venta', 'plazo_venta', 'autorizar_gasto', 'ia_chat', 'documento_ocr', 'factura_ocr', 'traspaso');
    if (!in_array($tipo_qr, $tipos_validos, true)) {
        throw new Exception('Tipo no válido');
    }

    if ($id_item <= 0) {
        throw new Exception('ID de item no válido');
    }
    if ($token === '') {
        throw new Exception('Token no puede estar vacío');
    }

    $conexion = conectar_bd();
    if (!$conexion) {
        throw new Exception('Sin conexión a la base de datos');
    }
    camera_token_ensure_type_item_column($conexion);

    $rel_id_empresa = 0;
    if ($tipo_qr === 'cliente') {
        $stmt_cli = mysqli_prepare($conexion, 'SELECT rel_id_empresa FROM clientes WHERE id_cliente = ? LIMIT 1');
        if ($stmt_cli) {
            mysqli_stmt_bind_param($stmt_cli, 'i', $id_item);
            mysqli_stmt_execute($stmt_cli);
            $res_cli = mysqli_stmt_get_result($stmt_cli);
            $row_cli = $res_cli ? mysqli_fetch_assoc($res_cli) : null;
            mysqli_stmt_close($stmt_cli);
            $rel_id_empresa = (int) ($row_cli['rel_id_empresa'] ?? 0);
        }
    } elseif ($tipo_qr === 'articulo') {
        $stmt_art = mysqli_prepare($conexion, 'SELECT empresa_id_rel FROM articulos WHERE sku = ? LIMIT 1');
        if ($stmt_art) {
            mysqli_stmt_bind_param($stmt_art, 'i', $id_item);
            mysqli_stmt_execute($stmt_art);
            $res_art = mysqli_stmt_get_result($stmt_art);
            $row_art = $res_art ? mysqli_fetch_assoc($res_art) : null;
            mysqli_stmt_close($stmt_art);
            $rel_id_empresa = (int) ($row_art['empresa_id_rel'] ?? 0);
        }
    }
    if ($rel_id_empresa <= 0 && defined('APP_ID')) {
        $rel_id_empresa = (int) APP_ID;
    }
    if ($rel_id_empresa <= 0) {
        throw new Exception('No se pudo determinar la empresa del token');
    }

    $state_token = 'true';
    $query = '
        INSERT INTO tokens_actions
        (token_string, state_token, id_item, type_item, rel_id_empresa, fecha_token)
        VALUES (?, ?, ?, ?, ?, CURDATE())
    ';

    $stmt = mysqli_prepare($conexion, $query);
    if (!$stmt) {
        throw new Exception('Error al preparar el token: ' . mysqli_error($conexion));
    }
    mysqli_stmt_bind_param($stmt, 'ssisi', $token, $state_token, $id_item, $tipo_qr, $rel_id_empresa);

    if (!mysqli_stmt_execute($stmt)) {
        $err = mysqli_stmt_error($stmt);
        if ($err === '') {
            $err = mysqli_error($conexion);
        }
        mysqli_stmt_close($stmt);
        throw new Exception('Error al guardar el token: ' . $err);
    }

    $id_token_insertado = mysqli_insert_id($conexion);

    mysqli_stmt_close($stmt);
    mysqli_close($conexion);

    echo json_encode(array(
        'success' => true,
        'message' => 'Token guardado exitosamente',
        'id_token' => $id_token_insertado,
        'token' => $token,
    ));
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(array(
        'success' => false,
        'error' => $e->getMessage(),
    ));
}
