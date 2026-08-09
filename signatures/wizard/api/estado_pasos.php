<?php
require_once __DIR__ . '/_wizard_api_bootstrap.php';

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    echo json_encode(array('ok' => false, 'error' => 'metodo'));
    exit;
}

$id_usuario = (int) ($_SESSION['usuario_id'] ?? 0);
if ($id_usuario < 1) {
    http_response_code(400);
    echo json_encode(array('ok' => false, 'error' => 'usuario'));
    exit;
}

$conexion = conectar_bd();
$sql = 'SELECT codigo_paso FROM formacion_wizard_pasos WHERE id_usuario = ? ORDER BY fecha_completado ASC';
$stmt = mysqli_prepare($conexion, $sql);
if (!$stmt) {
    $detalle = mysqli_error($conexion);
    mysqli_close($conexion);
    http_response_code(500);
    echo json_encode(array('ok' => false, 'error' => 'bd', 'detalle' => $detalle));
    exit;
}

mysqli_stmt_bind_param($stmt, 'i', $id_usuario);
mysqli_stmt_execute($stmt);
$res = mysqli_stmt_get_result($stmt);
$pasos = array();
if ($res) {
    while ($row = mysqli_fetch_assoc($res)) {
        $pasos[] = $row['codigo_paso'];
    }
}
mysqli_stmt_close($stmt);
mysqli_close($conexion);

echo json_encode(array('ok' => true, 'pasos' => $pasos));
