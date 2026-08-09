<?php
/**
 * Actualiza el valor principal de un registro según su typ_config.
 */

require_once '../../../include/session.php';
require_once '../../../include/functions.php';

header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(array('success' => false, 'message' => 'Método no permitido'));
    exit;
}

$id_config = isset($_POST['id_config']) ? (int) $_POST['id_config'] : 0;
$valor_raw = isset($_POST['valor']) ? $_POST['valor'] : '';

if ($id_config <= 0) {
    echo json_encode(array('success' => false, 'message' => 'ID no válido.'));
    exit;
}

$conexion = conectar_bd();
$sql_row = "SELECT typ_config FROM configuracion_general WHERE id_config = ? LIMIT 1";
$stmt_row = mysqli_prepare($conexion, $sql_row);
if (!$stmt_row) {
    mysqli_close($conexion);
    echo json_encode(array('success' => false, 'message' => 'Error al preparar la consulta.'));
    exit;
}
mysqli_stmt_bind_param($stmt_row, 'i', $id_config);
mysqli_stmt_execute($stmt_row);
$res_row = mysqli_stmt_get_result($stmt_row);
$meta = $res_row ? mysqli_fetch_assoc($res_row) : null;
mysqli_stmt_close($stmt_row);

if (!$meta) {
    mysqli_close($conexion);
    echo json_encode(array('success' => false, 'message' => 'Registro no encontrado.'));
    exit;
}

$typ_config = $meta['typ_config'];
$tipos_actualizables = array('text', 'varchar', 'integro', 'decimal');

if (!in_array($typ_config, $tipos_actualizables, true)) {
    mysqli_close($conexion);
    echo json_encode(array('success' => false, 'message' => 'Este tipo no admite actualización desde aquí.'));
    exit;
}

$ok = false;
$err = '';

switch ($typ_config) {
    case 'text':
        $texto_value = (string) $valor_raw;
        $sql = "UPDATE configuracion_general SET texto_value = ? WHERE id_config = ? AND typ_config = 'text'";
        $stmt = mysqli_prepare($conexion, $sql);
        if ($stmt) {
            mysqli_stmt_bind_param($stmt, 'si', $texto_value, $id_config);
            $ok = mysqli_stmt_execute($stmt);
            $err = mysqli_stmt_error($stmt);
            mysqli_stmt_close($stmt);
        }
        break;

    case 'varchar':
        $varchar_value = mb_substr((string) $valor_raw, 0, 168);
        $sql = "UPDATE configuracion_general SET varchar_value = ? WHERE id_config = ? AND typ_config = 'varchar'";
        $stmt = mysqli_prepare($conexion, $sql);
        if ($stmt) {
            mysqli_stmt_bind_param($stmt, 'si', $varchar_value, $id_config);
            $ok = mysqli_stmt_execute($stmt);
            $err = mysqli_stmt_error($stmt);
            mysqli_stmt_close($stmt);
        }
        break;

    case 'integro':
        $norm = str_replace(array(' ', ','), array('', '.'), trim((string) $valor_raw));
        $integro_value = ($norm === '' || !is_numeric($norm)) ? 0 : (int) round((float) $norm);
        $sql = "UPDATE configuracion_general SET integro_value = ? WHERE id_config = ? AND typ_config = 'integro'";
        $stmt = mysqli_prepare($conexion, $sql);
        if ($stmt) {
            mysqli_stmt_bind_param($stmt, 'ii', $integro_value, $id_config);
            $ok = mysqli_stmt_execute($stmt);
            $err = mysqli_stmt_error($stmt);
            mysqli_stmt_close($stmt);
        }
        break;

    case 'decimal':
        $norm = str_replace(array(' ', ','), array('', '.'), trim((string) $valor_raw));
        if ($norm === '' || !is_numeric($norm)) {
            $decimal_value = 0.00;
        } else {
            $decimal_value = (float) $norm;
        }
        $sql = "UPDATE configuracion_general SET decimal_value = ? WHERE id_config = ? AND typ_config = 'decimal'";
        $stmt = mysqli_prepare($conexion, $sql);
        if ($stmt) {
            mysqli_stmt_bind_param($stmt, 'di', $decimal_value, $id_config);
            $ok = mysqli_stmt_execute($stmt);
            $err = mysqli_stmt_error($stmt);
            mysqli_stmt_close($stmt);
        }
        break;

    default:
        break;
}

mysqli_close($conexion);

if (!$ok) {
    echo json_encode(array('success' => false, 'message' => $err ? $err : 'No se pudo actualizar.'));
    exit;
}

echo json_encode(array('success' => true, 'message' => 'Valor actualizado correctamente.'));
