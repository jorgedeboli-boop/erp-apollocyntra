<?php
/**
 * Crea registro en migraciones y genera el script PHP en parts/migracion/unique/.
 */

require_once __DIR__ . '/../../../include/session.php';
require_once __DIR__ . '/../../../include/functions.php';

header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(array('success' => false, 'message' => 'Método no permitido'));
    exit;
}

function migracion_normalizar_script($valor)
{
    $nombre = strtolower(trim((string) $valor));
    $nombre = preg_replace('/\.php$/i', '', $nombre);
    $nombre = preg_replace('/[^a-z0-9_]/', '_', $nombre);
    $nombre = preg_replace('/_+/', '_', $nombre);
    $nombre = trim($nombre, '_');

    if ($nombre === '' || strlen($nombre) > 120) {
        return false;
    }

    return $nombre . '.php';
}

function migracion_plantilla_php($nombre, $descripcion, $codigo)
{
    $lineaNombre = '* ' . preg_replace('/[\r\n]+/', ' ', $nombre);
    $lineaDesc = '* ' . preg_replace('/[\r\n]+/', ' ', $descripcion);
    $lineaCodigo = '* Código: ' . preg_replace('/[\r\n]+/', ' ', $codigo);

    return <<<PHP
<?php
/**
{$lineaNombre}
{$lineaDesc}
{$lineaCodigo}
 */
require_once __DIR__ . '/../../../include/session.php';
require_once __DIR__ . '/../../../include/functions.php';

header('Content-Type: text/plain; charset=utf-8');
set_time_limit(0);

// TODO: implementar lógica de migración
echo "Migración pendiente de implementar.\\n";
echo "Hecho.\\n";

PHP;
}

$codigo = isset($_POST['codigo_migracion']) ? strtolower(trim($_POST['codigo_migracion'])) : '';
$nombre = isset($_POST['nombre_migracion']) ? trim($_POST['nombre_migracion']) : '';
$descripcion = isset($_POST['descripcion_migracion']) ? trim($_POST['descripcion_migracion']) : '';
$scriptInput = isset($_POST['script_migracion']) ? trim($_POST['script_migracion']) : '';

if ($codigo === '' || !preg_match('/^[a-z0-9_]{2,64}$/', $codigo)) {
    echo json_encode(array('success' => false, 'message' => 'Código inválido. Use solo letras minúsculas, números y guion bajo (2-64 caracteres).'));
    exit;
}

if ($nombre === '' || mb_strlen($nombre) > 120) {
    echo json_encode(array('success' => false, 'message' => 'El nombre es obligatorio (máx. 120 caracteres).'));
    exit;
}

if ($descripcion === '') {
    echo json_encode(array('success' => false, 'message' => 'La descripción es obligatoria.'));
    exit;
}

$scriptFile = migracion_normalizar_script($scriptInput !== '' ? $scriptInput : $codigo);
if ($scriptFile === false) {
    echo json_encode(array('success' => false, 'message' => 'Nombre de script inválido.'));
    exit;
}

$scriptRel = 'parts/migracion/unique/' . $scriptFile;
$scriptAbs = __DIR__ . '/' . $scriptFile;

$reservados = array('migrar_clientes.php', 'migrar_clientes_2.php', 'load_migraciones.php', 'crear_migracion.php', 'actualizar_estado_migracion.php', 'sugerir_nacionalidades_ia.php', 'nacionalidades_ia_api.php', 'migrar_nacionalidades_helpers.php', 'migrar_nacionalidades_mapa.php');
if (in_array($scriptFile, $reservados, true)) {
    echo json_encode(array('success' => false, 'message' => 'Ese nombre de script está reservado.'));
    exit;
}

if (file_exists($scriptAbs)) {
    echo json_encode(array('success' => false, 'message' => 'Ya existe un fichero con ese nombre de script.'));
    exit;
}

$conexion = conectar_bd();
if (!$conexion) {
    echo json_encode(array('success' => false, 'message' => 'No se pudo conectar a la base de datos.'));
    exit;
}

$stmtCheck = mysqli_prepare($conexion, 'SELECT id_migracion FROM migraciones WHERE codigo_migracion = ? LIMIT 1');
if (!$stmtCheck) {
    mysqli_close($conexion);
    echo json_encode(array('success' => false, 'message' => 'Error al validar código.'));
    exit;
}
mysqli_stmt_bind_param($stmtCheck, 's', $codigo);
mysqli_stmt_execute($stmtCheck);
$resCheck = mysqli_stmt_get_result($stmtCheck);
if ($resCheck && mysqli_fetch_assoc($resCheck)) {
    mysqli_stmt_close($stmtCheck);
    mysqli_close($conexion);
    echo json_encode(array('success' => false, 'message' => 'Ya existe una migración con ese código.'));
    exit;
}
mysqli_stmt_close($stmtCheck);

$orden = 1;
$resOrden = mysqli_query($conexion, 'SELECT IFNULL(MAX(orden_visual), 0) + 1 AS next_orden FROM migraciones');
if ($resOrden) {
    $rowOrden = mysqli_fetch_assoc($resOrden);
    $orden = (int) ($rowOrden['next_orden'] ?? 1);
    mysqli_free_result($resOrden);
}

$contenido = migracion_plantilla_php($nombre, $descripcion, $codigo);
if (@file_put_contents($scriptAbs, $contenido) === false) {
    mysqli_close($conexion);
    echo json_encode(array('success' => false, 'message' => 'No se pudo crear el fichero PHP.'));
    exit;
}

$estado = 'pendiente';
$activa = 'true';
$stmt = mysqli_prepare(
    $conexion,
    'INSERT INTO migraciones (
        codigo_migracion,
        nombre_migracion,
        descripcion_migracion,
        script_migracion,
        estado_migracion,
        orden_visual,
        activa
    ) VALUES (?, ?, ?, ?, ?, ?, ?)'
);

if (!$stmt) {
    @unlink($scriptAbs);
    mysqli_close($conexion);
    echo json_encode(array('success' => false, 'message' => 'Error al preparar INSERT.'));
    exit;
}

mysqli_stmt_bind_param($stmt, 'sssssis', $codigo, $nombre, $descripcion, $scriptRel, $estado, $orden, $activa);
$ok = mysqli_stmt_execute($stmt);
$id = mysqli_insert_id($conexion);
$err = mysqli_stmt_error($stmt);
mysqli_stmt_close($stmt);
mysqli_close($conexion);

if (!$ok) {
    @unlink($scriptAbs);
    echo json_encode(array('success' => false, 'message' => $err ? $err : 'No se pudo guardar la migración.'));
    exit;
}

echo json_encode(array(
    'success' => true,
    'message' => 'Migración creada correctamente.',
    'item' => array(
        'id_migracion' => (int) $id,
        'codigo_migracion' => $codigo,
        'nombre_migracion' => $nombre,
        'descripcion_migracion' => $descripcion,
        'script_migracion' => $scriptRel,
        'estado_migracion' => $estado,
        'mensaje_resultado' => null,
        'registros_procesados' => 0,
        'registros_total' => 0,
        'fecha_ejecucion' => null,
        'fecha_ultimo_intento' => null
    )
));
