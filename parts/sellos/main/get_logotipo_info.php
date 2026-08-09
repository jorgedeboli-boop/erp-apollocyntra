<?php
require_once '../../../include/session.php';
require_once '../../../include/functions.php';

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-cache, must-revalidate');

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Método no permitido']);
    exit;
}

$id_sello = isset($_GET['id']) ? (int) $_GET['id'] : 0;
if ($id_sello <= 0) {
    echo json_encode(['success' => false, 'message' => 'ID de sello no válido']);
    exit;
}

try {
    $conexion = conectar_bd();
    $stmt = mysqli_prepare($conexion, 'SELECT imagen_logotipo FROM sellos WHERE id_sello = ? LIMIT 1');
    if (!$stmt) {
        throw new Exception(mysqli_error($conexion));
    }
    mysqli_stmt_bind_param($stmt, 'i', $id_sello);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);

    if ($result && mysqli_num_rows($result) > 0) {
        $row = mysqli_fetch_assoc($result);
        $imagen = $row['imagen_logotipo'] ?? '';

        if (!empty($imagen)) {
            $ruta = '../../../photos/' . $imagen;
            if (file_exists($ruta)) {
                echo json_encode([
                    'success' => true,
                    'logotipo' => $imagen,
                    'id_sello' => $id_sello,
                ], JSON_UNESCAPED_UNICODE);
            } else {
                echo json_encode([
                    'success' => false,
                    'message' => 'Archivo de logotipo no encontrado en el servidor',
                ], JSON_UNESCAPED_UNICODE);
            }
        } else {
            echo json_encode([
                'success' => false,
                'message' => 'No hay logotipo configurado para este sello',
            ], JSON_UNESCAPED_UNICODE);
        }
    } else {
        echo json_encode(['success' => false, 'message' => 'Sello no encontrado'], JSON_UNESCAPED_UNICODE);
    }

    mysqli_stmt_close($stmt);
    mysqli_close($conexion);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Error interno: ' . $e->getMessage(),
    ], JSON_UNESCAPED_UNICODE);
}
