<?php
/**
 * Actualiza estado y resultado de una migración.
 */

require_once __DIR__ . '/../../../include/session.php';
require_once __DIR__ . '/../../../include/functions.php';

header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(array('success' => false, 'message' => 'Método no permitido'));
    exit;
}

$id = isset($_POST['id_migracion']) ? (int) $_POST['id_migracion'] : 0;
$estado = isset($_POST['estado_migracion']) ? trim($_POST['estado_migracion']) : '';
$mensaje = isset($_POST['mensaje_resultado']) ? trim($_POST['mensaje_resultado']) : '';
$procesados = isset($_POST['registros_procesados']) ? (int) $_POST['registros_procesados'] : 0;
$total = isset($_POST['registros_total']) ? (int) $_POST['registros_total'] : 0;

$estadosValidos = array('pendiente', 'en_proceso', 'migrado', 'error');
if ($id <= 0 || !in_array($estado, $estadosValidos, true)) {
    echo json_encode(array('success' => false, 'message' => 'Datos inválidos.'));
    exit;
}

$conexion = conectar_bd();
if (!$conexion) {
    echo json_encode(array('success' => false, 'message' => 'No se pudo conectar a la base de datos.'));
    exit;
}

$ejecutadoPor = isset($usuario_id) ? (int) $usuario_id : 0;
$ahora = date('Y-m-d H:i:s');

if ($estado === 'migrado') {
    $sql = "UPDATE migraciones SET
        estado_migracion = ?,
        mensaje_resultado = ?,
        registros_procesados = ?,
        registros_total = ?,
        ejecutado_por = ?,
        fecha_ultimo_intento = ?,
        fecha_ejecucion = ?
    WHERE id_migracion = ?";
    $stmt = mysqli_prepare($conexion, $sql);
    if (!$stmt) {
        mysqli_close($conexion);
        echo json_encode(array('success' => false, 'message' => 'Error al preparar UPDATE.'));
        exit;
    }
    mysqli_stmt_bind_param($stmt, 'ssiiissi', $estado, $mensaje, $procesados, $total, $ejecutadoPor, $ahora, $ahora, $id);
} else {
    $sql = "UPDATE migraciones SET
        estado_migracion = ?,
        mensaje_resultado = ?,
        registros_procesados = ?,
        registros_total = ?,
        ejecutado_por = ?,
        fecha_ultimo_intento = ?
    WHERE id_migracion = ?";
    $stmt = mysqli_prepare($conexion, $sql);
    if (!$stmt) {
        mysqli_close($conexion);
        echo json_encode(array('success' => false, 'message' => 'Error al preparar UPDATE.'));
        exit;
    }
    mysqli_stmt_bind_param($stmt, 'ssiiisi', $estado, $mensaje, $procesados, $total, $ejecutadoPor, $ahora, $id);
}

$ok = mysqli_stmt_execute($stmt);
$err = mysqli_stmt_error($stmt);
mysqli_stmt_close($stmt);
mysqli_close($conexion);

if (!$ok) {
    echo json_encode(array('success' => false, 'message' => $err ? $err : 'No se pudo actualizar el estado.'));
    exit;
}

echo json_encode(array('success' => true, 'message' => 'Estado actualizado.'));
