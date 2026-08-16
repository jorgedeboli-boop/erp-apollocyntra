<?php
require_once '../../../include/session.php';
require_once '../../../include/functions.php';
require_once __DIR__ . '/correccion_cajas_helper.php';

header('Content-Type: application/json; charset=utf-8');

if (empty($_SESSION['usuario_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'No autorizado'], JSON_UNESCAPED_UNICODE);
    exit;
}

$idTabla = isset($_POST['id_tabla']) ? (int) $_POST['id_tabla'] : 0;
$fechaApunte = isset($_POST['fecha_apunte']) ? trim($_POST['fecha_apunte']) : '';
$grupos = isset($_POST['grupos']) ? trim($_POST['grupos']) : '';
$concepto = isset($_POST['concepto']) ? trim($_POST['concepto']) : '';
$salida = isset($_POST['salida']) ? (float) $_POST['salida'] : 0;
$entrada = isset($_POST['entrada']) ? (float) $_POST['entrada'] : 0;
$usuarioId = (int) $_SESSION['usuario_id'];

if ($idTabla <= 0) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Caja inválida'], JSON_UNESCAPED_UNICODE);
    exit;
}

if ($fechaApunte === '' || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $fechaApunte)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Fecha inválida'], JSON_UNESCAPED_UNICODE);
    exit;
}

if ($grupos === '') {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'El grupo es requerido'], JSON_UNESCAPED_UNICODE);
    exit;
}

if ($concepto === '') {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'El concepto es requerido'], JSON_UNESCAPED_UNICODE);
    exit;
}

if ($salida == 0.0 && $entrada == 0.0) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Debe ingresar un valor en Salida o Entrada'], JSON_UNESCAPED_UNICODE);
    exit;
}

if (correccion_cajas_es_apertura($grupos) || trim($grupos) === 'CAJA FINAL') {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Use las opciones de corrección para apertura o cierre'], JSON_UNESCAPED_UNICODE);
    exit;
}

try {
    $conexion = conectar_bd();
    if (!$conexion) {
        throw new Exception('Error de conexión a la base de datos');
    }

    $tabla = correccion_cajas_tabla_movimientos($idTabla);
    if (!correccion_cajas_tabla_existe($conexion, $tabla)) {
        throw new Exception('No existe tabla de movimientos para esta caja');
    }

    mysqli_begin_transaction($conexion);

    $idInsercion = correccion_cajas_obtener_id_insercion_tras_apertura($conexion, $tabla, $fechaApunte);
    $horaInsercion = correccion_cajas_obtener_hora_insercion_tras_apertura($conexion, $tabla, $fechaApunte);

    correccion_cajas_insertar_movimiento(
        $conexion,
        $tabla,
        $idInsercion,
        $grupos,
        $concepto,
        $entrada,
        $salida,
        $usuarioId,
        $fechaApunte,
        $horaInsercion,
        'false'
    );

    mysqli_commit($conexion);
    mysqli_close($conexion);

    echo json_encode([
        'success' => true,
        'message' => 'Apunte creado correctamente',
        'id_movimiento' => $idInsercion,
    ], JSON_UNESCAPED_UNICODE);
} catch (Exception $e) {
    if (isset($conexion) && mysqli_ping($conexion)) {
        mysqli_rollback($conexion);
        mysqli_close($conexion);
    }

    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage(),
    ], JSON_UNESCAPED_UNICODE);
}
