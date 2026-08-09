<?php
/**
 * Crea una solicitud de autorización de devolución (código pendiente).
 * POST: sku_articulo_devolucion (int), opcional tipo_devolucion (normal|web).
 */

require_once __DIR__ . '/../../../include/session.php';
require_once __DIR__ . '/../../../include/functions.php';

header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['statelogdevo' => 'error', 'message' => 'Método no permitido']);
    exit;
}

$sku = isset($_POST['sku_articulo_devolucion']) ? (int) $_POST['sku_articulo_devolucion'] : 0;
if ($sku <= 0) {
    echo json_encode(['statelogdevo' => 'error', 'message' => 'SKU no válido']);
    exit;
}

$permitted_chars = '0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZ';

function generar_codigo_autorizacion_devolucion($chars, $length = 6) {
    $len = strlen($chars);
    $out = '';
    for ($i = 0; $i < $length; $i++) {
        $out .= $chars[random_int(0, $len - 1)];
    }
    return $out;
}

$codigo_autorizacion = generar_codigo_autorizacion_devolucion($permitted_chars, 6);
$suc = (int) $usuario_sucursal;
if ($suc <= 0) {
    $suc = 0;
}
$usuario_str = (string) (isset($usuario_id) ? $usuario_id : '');
$sku_str = (string) $sku;

$conexion = conectar_bd();
$sql = '
    INSERT INTO autorizaciones_devoluciones (
        sucursal_autorizacion,
        usuario_autorizacion,
        codigo_autorizacion,
        estado_autorizacion,
        fecha_autorizacion,
        sku_articulo_devolucion
    ) VALUES (?, ?, ?, \'pendiente\', NOW(), ?)
';
$stmt = mysqli_prepare($conexion, $sql);
if (!$stmt) {
    mysqli_close($conexion);
    echo json_encode(['statelogdevo' => 'error', 'message' => 'Error al preparar inserción']);
    exit;
}

mysqli_stmt_bind_param($stmt, 'isss', $suc, $usuario_str, $codigo_autorizacion, $sku_str);
if (!mysqli_stmt_execute($stmt)) {
    mysqli_stmt_close($stmt);
    mysqli_close($conexion);
    echo json_encode(['statelogdevo' => 'error', 'message' => 'Error al guardar la solicitud']);
    exit;
}

$id_auth = (int) mysqli_insert_id($conexion);
mysqli_stmt_close($stmt);
mysqli_close($conexion);

echo json_encode([
    'statelogdevo' => 'ok',
    'code_aut' => $codigo_autorizacion,
    'id_auth' => $id_auth,
]);
