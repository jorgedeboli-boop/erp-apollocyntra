<?php
/**
 * API JSON: listar / aprobar / rechazar sugerencias IA de nacionalidades.
 */
require_once __DIR__ . '/../../../include/session.php';
require_once __DIR__ . '/../../../include/functions.php';
require_once __DIR__ . '/migrar_nacionalidades_helpers.php';

header('Content-Type: application/json; charset=utf-8');

$mysqli = conectar_bd();
if (!$mysqli) {
    echo json_encode(array('success' => false, 'message' => 'No se pudo conectar a la base de datos.'));
    exit;
}

if (!migracion_nacionalidades_tabla_existe($mysqli)) {
    mysqli_close($mysqli);
    echo json_encode(array(
        'success' => false,
        'message' => 'La tabla migraciones_nacionalidades_mapeo no existe.',
        'items' => array(),
    ));
    exit;
}

$usuarioId = isset($usuario_id) ? (int) $usuario_id : 0;
$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'GET') {
    $estado = isset($_GET['estado']) ? trim($_GET['estado']) : 'pendiente';
    $items = migracion_ia_listar($mysqli, $estado);
    mysqli_close($mysqli);
    echo json_encode(array(
        'success' => true,
        'items' => $items,
        'total' => count($items),
    ));
    exit;
}

if ($method === 'POST') {
    $accion = isset($_POST['accion']) ? trim($_POST['accion']) : '';
    $idMapeo = isset($_POST['id_mapeo']) ? (int) $_POST['id_mapeo'] : 0;

    if ($accion === 'aprobar') {
        $res = migracion_ia_cambiar_estado($mysqli, $idMapeo, 'aprobado', $usuarioId);
    } elseif ($accion === 'rechazar') {
        $res = migracion_ia_cambiar_estado($mysqli, $idMapeo, 'rechazado', $usuarioId);
    } else {
        mysqli_close($mysqli);
        echo json_encode(array('success' => false, 'message' => 'Acción no válida.'));
        exit;
    }

    $pendientes = migracion_ia_listar($mysqli, 'pendiente');
    mysqli_close($mysqli);

    echo json_encode(array(
        'success' => $res['success'],
        'message' => $res['message'],
        'pendientes' => count($pendientes),
    ));
    exit;
}

mysqli_close($mysqli);
http_response_code(405);
echo json_encode(array('success' => false, 'message' => 'Método no permitido'));
