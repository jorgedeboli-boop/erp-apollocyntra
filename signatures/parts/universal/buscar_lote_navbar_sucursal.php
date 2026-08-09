<?php
/**
 * Búsqueda de lotes por id_lote restringida a la sucursal del usuario en sesión.
 */

require_once __DIR__ . '/../../include/session.php';
require_once __DIR__ . '/../../include/functions.php';

header('Content-Type: application/json; charset=utf-8');

if (empty($_SESSION['usuario_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'No autorizado'], JSON_UNESCAPED_UNICODE);
    exit;
}

/**
 * @param mixed $fecha
 */
function buscar_lote_navbar_formatear_fecha($fecha)
{
    if (empty($fecha) || $fecha === '0000-00-00') {
        return '—';
    }
    $ts = strtotime((string) $fecha);
    if ($ts === false) {
        return '—';
    }
    return date('d-m-Y', $ts);
}

/**
 * @param mixed $compraOpcion
 */
function buscar_lote_navbar_tipo_lote($compraOpcion)
{
    return strtolower(trim((string) $compraOpcion)) === 'si' ? 'Empeño' : 'Compra';
}

/**
 * @param mixed $valor
 */
function buscar_lote_navbar_formatear_precio($valor)
{
    $num = (float) str_replace(',', '.', (string) $valor);
    return number_format($num, 2, ',', '.') . ' €';
}

/**
 * @param mixed $valor
 */
function buscar_lote_navbar_formatear_peso($valor)
{
    $num = (float) str_replace(',', '.', (string) $valor);
    return number_format($num, 2, ',', '.') . ' gr';
}

/**
 * @return array{normalizado:string,digitos:string,letras:string}
 */
function buscar_lote_navbar_parsear_busqueda($busqueda)
{
    $normalizado = strtoupper(preg_replace('/[^0-9A-Z]/', '', (string) $busqueda));
    $digitos = preg_replace('/[^0-9]/', '', $normalizado);
    $letras = preg_replace('/[^A-Z]/', '', $normalizado);

    return [
        'normalizado' => $normalizado,
        'digitos' => $digitos,
        'letras' => $letras,
    ];
}

$busquedaRaw = isset($_GET['busqueda']) ? (string) $_GET['busqueda'] : '';
if ($busquedaRaw === '' && isset($_POST['busqueda'])) {
    $busquedaRaw = (string) $_POST['busqueda'];
}

$busqueda = buscar_lote_navbar_parsear_busqueda($busquedaRaw);
$tieneIdLote = $busqueda['digitos'] !== '';

$idSucursalUsuario = isset($usuario_sucursal) ? (int) $usuario_sucursal : 0;
if ($idSucursalUsuario <= 0) {
    http_response_code(403);
    echo json_encode([
        'success' => false,
        'message' => 'Sucursal de usuario no válida.',
        'lotes' => [],
        'total' => 0,
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

if (!$tieneIdLote) {
    echo json_encode([
        'success' => false,
        'message' => 'Introduce el número de lote para buscar.',
        'lotes' => [],
        'total' => 0,
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

try {
    $conexion = conectar_bd();
    if (!$conexion) {
        throw new Exception('Error de conexión');
    }
    mysqli_set_charset($conexion, 'utf8');

    $where = [];
    $types = '';
    $params = [];

    if ($tieneIdLote) {
        $idLote = (int) $busqueda['digitos'];
        if ($idLote <= 0) {
            echo json_encode([
                'success' => false,
                'message' => 'Número de lote no válido.',
                'lotes' => [],
                'total' => 0,
            ], JSON_UNESCAPED_UNICODE);
            exit;
        }
        $where[] = 'l.id_lote = ?';
        $types .= 'i';
        $params[] = $idLote;
    }

    $tablaLotes = 'lotes_' . $idSucursalUsuario;
    $tablaLotesEsc = mysqli_real_escape_string($conexion, $tablaLotes);
    $checkTable = mysqli_query($conexion, "SHOW TABLES LIKE '{$tablaLotesEsc}'");
    if (!$checkTable || mysqli_num_rows($checkTable) === 0) {
        throw new Exception('Tabla de lotes no encontrada');
    }

    $nombreSucursalUsuario = trim((string) ($usuario_sucursal_nombre ?? ''));
    if ($nombreSucursalUsuario === '') {
        $nombreSucursalUsuario = '—';
    }

    $sql = "
        SELECT
            l.id_lote,
            l.identificador,
            l.compra_opcion,
            l.fecha_compra,
            l.precio_compra,
            l.peso
        FROM `{$tablaLotes}` l
        WHERE " . implode(' AND ', $where) . "
        ORDER BY l.id_lote ASC
        LIMIT 50
    ";

    $stmt = mysqli_prepare($conexion, $sql);
    if (!$stmt) {
        throw new Exception('Error al preparar la consulta');
    }

    if ($types !== '') {
        $bindParams = array_merge([$stmt, $types], $params);
        $refs = [];
        foreach ($bindParams as $key => $value) {
            $refs[$key] = &$bindParams[$key];
        }
        call_user_func_array('mysqli_stmt_bind_param', $refs);
    }

    mysqli_stmt_execute($stmt);
    $resultado = mysqli_stmt_get_result($stmt);

    $lotes = [];
    while ($row = mysqli_fetch_assoc($resultado)) {
        $lotes[] = [
            'id_lote' => (int) ($row['id_lote'] ?? 0),
            'identificador' => (int) ($row['identificador'] ?? 0),
            'tipo_lote' => buscar_lote_navbar_tipo_lote($row['compra_opcion'] ?? ''),
            'fecha_compra' => buscar_lote_navbar_formatear_fecha($row['fecha_compra'] ?? ''),
            'precio_compra' => buscar_lote_navbar_formatear_precio($row['precio_compra'] ?? 0),
            'peso' => buscar_lote_navbar_formatear_peso($row['peso'] ?? 0),
            'nombre_sucursal' => $nombreSucursalUsuario,
        ];
    }

    mysqli_stmt_close($stmt);
    mysqli_close($conexion);

    echo json_encode([
        'success' => true,
        'busqueda' => $busqueda['normalizado'],
        'id_lote' => $tieneIdLote ? (int) $busqueda['digitos'] : null,
        'id_sucursal' => $idSucursalUsuario,
        'lotes' => $lotes,
        'total' => count($lotes),
    ], JSON_UNESCAPED_UNICODE);
} catch (Exception $e) {
    if (isset($conexion) && $conexion) {
        mysqli_close($conexion);
    }
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Error al buscar lotes.',
        'lotes' => [],
        'total' => 0,
    ], JSON_UNESCAPED_UNICODE);
}
