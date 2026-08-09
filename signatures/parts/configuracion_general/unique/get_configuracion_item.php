<?php
/**
 * Devuelve un registro de configuracion_general (para refrescar valores en la UI).
 */

require_once '../../../include/session.php';
require_once '../../../include/functions.php';

header('Content-Type: application/json; charset=utf-8');

$id_config = isset($_GET['id']) ? (int) $_GET['id'] : 0;
if ($id_config <= 0) {
    echo json_encode(array('success' => false, 'message' => 'ID no válido.'));
    exit;
}

$conexion = conectar_bd();
$sql = "SELECT id_config, typ_config, texto_value, boleano_value, options_value, integro_value, decimal_value, varchar_value, titulo_config
        FROM configuracion_general WHERE id_config = ? LIMIT 1";
$stmt = mysqli_prepare($conexion, $sql);
if (!$stmt) {
    mysqli_close($conexion);
    echo json_encode(array('success' => false, 'message' => 'Error al preparar la consulta.'));
    exit;
}

mysqli_stmt_bind_param($stmt, 'i', $id_config);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$row = $result ? mysqli_fetch_assoc($result) : null;
mysqli_stmt_close($stmt);
mysqli_close($conexion);

if (!$row) {
    echo json_encode(array('success' => false, 'message' => 'Registro no encontrado.'));
    exit;
}

$row['success'] = true;
$row['decimal_value'] = isset($row['decimal_value']) ? (string) $row['decimal_value'] : '0.00';
echo json_encode($row);
