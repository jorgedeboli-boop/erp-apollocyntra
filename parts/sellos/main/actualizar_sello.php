<?php
require_once '../../../include/session.php';
require_once '../../../include/functions.php';

header('Content-Type: application/json; charset=utf-8');

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

$id_sello = isset($_POST['id_sello']) ? (int) $_POST['id_sello'] : 0;
$nombre_sello = isset($_POST['nombre_sello']) ? trim((string) $_POST['nombre_sello']) : '';
$sello_logotipo = isset($_POST['sello_logotipo']) ? trim((string) $_POST['sello_logotipo']) : 'false';

$nombre_sello = substr($nombre_sello, 0, 164);

if ($id_sello <= 0) {
    echo json_encode(['success' => false, 'message' => 'ID de sello no válido']);
    exit;
}

if ($nombre_sello === '') {
    echo json_encode(['success' => false, 'message' => 'El nombre del sello es obligatorio']);
    exit;
}

if ($sello_logotipo !== 'true' && $sello_logotipo !== 'false') {
    $sello_logotipo = 'false';
}

$conexion = conectar_bd();
if (!$conexion) {
    echo json_encode(['success' => false, 'message' => 'Error de conexión']);
    exit;
}

mysqli_begin_transaction($conexion);

try {
    $stmt = mysqli_prepare(
        $conexion,
        'UPDATE sellos SET nombre_sello = ?, sello_logotipo = ? WHERE id_sello = ?'
    );
    if (!$stmt) {
        throw new Exception(mysqli_error($conexion));
    }

    mysqli_stmt_bind_param($stmt, 'ssi', $nombre_sello, $sello_logotipo, $id_sello);
    if (!mysqli_stmt_execute($stmt)) {
        throw new Exception(mysqli_stmt_error($stmt));
    }
    mysqli_stmt_close($stmt);

    if (function_exists('get_mysqli_figueredoapp')) {
        $mysqli_figueredoapp = get_mysqli_figueredoapp();
        if ($mysqli_figueredoapp) {
            $stmtFig = mysqli_prepare(
                $mysqli_figueredoapp,
                'UPDATE sellos SET nombre_sello = ?, sello_logotipo = ? WHERE id_sello = ?'
            );
            if ($stmtFig) {
                mysqli_stmt_bind_param($stmtFig, 'ssi', $nombre_sello, $sello_logotipo, $id_sello);
                mysqli_stmt_execute($stmtFig);
                mysqli_stmt_close($stmtFig);
            }
        }
    }

    mysqli_commit($conexion);
    mysqli_close($conexion);

    echo json_encode([
        'success' => true,
        'message' => 'Sello actualizado correctamente',
        'sello_logotipo' => $sello_logotipo,
        'nombre_sello' => $nombre_sello,
    ], JSON_UNESCAPED_UNICODE);
} catch (Exception $e) {
    mysqli_rollback($conexion);
    mysqli_close($conexion);
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Error al actualizar el sello: ' . $e->getMessage(),
    ], JSON_UNESCAPED_UNICODE);
}
