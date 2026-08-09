<?php
/**
 * Inserta un registro en configuracion_general.
 */

require_once '../../../include/session.php';
require_once '../../../include/functions.php';

header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(array('success' => false, 'message' => 'Método no permitido'));
    exit;
}

$tipos_validos = array('text', 'boleano', 'options', 'integro', 'decimal', 'varchar');
$typ_config = isset($_POST['typ_config']) ? trim($_POST['typ_config']) : '';
$titulo_config = isset($_POST['titulo_config']) ? trim($_POST['titulo_config']) : '';
$valor_raw = isset($_POST['valor']) ? $_POST['valor'] : '';

if ($titulo_config === '' || mb_strlen($titulo_config) > 64) {
    echo json_encode(array('success' => false, 'message' => 'El nombre es obligatorio (máx. 64 caracteres).'));
    exit;
}

if (!in_array($typ_config, $tipos_validos, true)) {
    echo json_encode(array('success' => false, 'message' => 'Tipo de configuración no válido.'));
    exit;
}

$texto_value = '';
$boleano_value = 'false';
$options_value = '';
$integro_value = 0;
$decimal_value = 0.00;
$varchar_value = '';

switch ($typ_config) {
    case 'text':
        $texto_value = (string) $valor_raw;
        break;
    case 'boleano':
        $v = strtolower(trim((string) $valor_raw));
        $boleano_value = in_array($v, array('true', '1', 'si', 'sí', 'yes'), true) ? 'true' : 'false';
        break;
    case 'options':
        $options_value = (string) $valor_raw;
        $parts_opt = array_values(array_filter(array_map('trim', explode(',', $options_value)), function ($o) {
            return $o !== '';
        }));
        if (!empty($parts_opt)) {
            $varchar_value = mb_substr($parts_opt[0], 0, 168);
        }
        break;
    case 'integro':
        $norm = str_replace(array(' ', ','), array('', '.'), trim((string) $valor_raw));
        $integro_value = ($norm === '' || !is_numeric($norm)) ? 0 : (int) round((float) $norm);
        break;
    case 'decimal':
        $norm = str_replace(array(' ', ','), array('', '.'), trim((string) $valor_raw));
        if ($norm === '' || !is_numeric($norm)) {
            $decimal_value = 0.00;
        } else {
            $decimal_value = (float) $norm;
        }
        break;
    case 'varchar':
        $varchar_value = mb_substr((string) $valor_raw, 0, 168);
        break;
}

$conexion = conectar_bd();
$sql = "INSERT INTO configuracion_general (
    typ_config,
    texto_value,
    boleano_value,
    options_value,
    integro_value,
    decimal_value,
    varchar_value,
    titulo_config
) VALUES (?, ?, ?, ?, ?, ?, ?, ?)";

$stmt = mysqli_prepare($conexion, $sql);
if (!$stmt) {
    mysqli_close($conexion);
    echo json_encode(array('success' => false, 'message' => 'Error al preparar la consulta.'));
    exit;
}

mysqli_stmt_bind_param(
    $stmt,
    'ssssidss',
    $typ_config,
    $texto_value,
    $boleano_value,
    $options_value,
    $integro_value,
    $decimal_value,
    $varchar_value,
    $titulo_config
);

$ok = mysqli_stmt_execute($stmt);
$err = mysqli_stmt_error($stmt);
mysqli_stmt_close($stmt);
mysqli_close($conexion);

if (!$ok) {
    echo json_encode(array('success' => false, 'message' => $err ? $err : 'No se pudo guardar el registro.'));
    exit;
}

echo json_encode(array('success' => true, 'message' => 'Configuración guardada correctamente.'));
