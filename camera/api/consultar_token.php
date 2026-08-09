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

    if (empty($data['token'])) {
        throw new Exception('Token no proporcionado');
    }
    if (empty($data['id_token'])) {
        throw new Exception('ID de token no proporcionado');
    }

    $id_token = (int) $data['id_token'];
    $token = trim($data['token']);

    $conexion = conectar_bd();

    // Tras subir la foto, state_token puede ser 'false', '0', 0, etc. según columna/BD.
    $query = 'SELECT * FROM tokens_actions WHERE id_token = ? LIMIT 1';

    $stmt = mysqli_prepare($conexion, $query);
    mysqli_stmt_bind_param($stmt, 'i', $id_token);
    mysqli_stmt_execute($stmt);
    $resultado = mysqli_stmt_get_result($stmt);

    if ($row = mysqli_fetch_assoc($resultado)) {
        if (trim((string) ($row['token_string'] ?? '')) !== $token) {
            mysqli_stmt_close($stmt);
            mysqli_close($conexion);
            echo json_encode(array(
                'success' => true,
                'utilizado' => false,
                'mensaje' => 'El token aún no ha sido utilizado',
            ));
            exit;
        }

        $st = strtolower(trim((string) ($row['state_token'] ?? '')));
        $ya_usado = ($st === 'false' || $st === '0');
        if (!$ya_usado) {
            mysqli_stmt_close($stmt);
            mysqli_close($conexion);
            echo json_encode(array(
                'success' => true,
                'utilizado' => false,
                'mensaje' => 'El token aún no ha sido utilizado',
            ));
            exit;
        }

        $token_data = array(
            'id_token' => $id_token,
            'id_item' => $row['id_item'],
            'type_item' => $row['type_item'],
            'fecha_token' => $row['fecha_token'],
        );

        // Meta ia_chat / documento_ocr: no depender de type_item (ENUM/truncado en BD).
        $meta_paths = array(
            __DIR__ . '/../tmp_ia_chat/' . $id_token . '.json',
            __DIR__ . '/../tmp_documento_ocr/' . $id_token . '.json',
            __DIR__ . '/../tmp_factura_ocr/' . $id_token . '.json',
        );
        foreach ($meta_paths as $meta_path) {
            if (!is_file($meta_path)) {
                continue;
            }
            $meta = json_decode((string) file_get_contents($meta_path), true);
            if (is_array($meta) && !empty($meta['nombre_foto'])) {
                $nom = basename((string) $meta['nombre_foto']);
                $token_data['nombre_foto'] = $nom;
                $base = defined('APP_URL') ? rtrim((string) APP_URL, '/') : '';
                $token_data['foto_url'] = ($base !== '' ? $base : '') . '/photos/' . rawurlencode($nom);
            }
            @unlink($meta_path);
            break;
        }

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
            'token_data' => $token_data,
        ));
    } else {
        mysqli_stmt_close($stmt);
        mysqli_close($conexion);

        echo json_encode(array(
            'success' => true,
            'utilizado' => false,
            'mensaje' => 'El token aún no ha sido utilizado',
        ));
    }
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(array(
        'success' => false,
        'error' => $e->getMessage(),
    ));
}
