<?php
/**
 * Búsqueda de lotes por id_lote y/o nombre de sucursal para el buscador del navbar.
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
$tieneSucursal = strlen($busqueda['letras']) >= 2;

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
        $where[] = 'lj.id_lote = ?';
        $types .= 'i';
        $params[] = $idLote;
    }

    if ($tieneSucursal) {
        $where[] = 's.nombre_sucursal LIKE ?';
        $types .= 's';
        $params[] = '%' . $busqueda['letras'] . '%';
    }

    $sql = "
        SELECT
            lj.id_lote,
            lj.identificador,
            lj.compra_opcion,
            lj.fecha_compra,
            lj.precio_compra,
            lj.peso,
            lj.sucursal,
            s.nombre_sucursal
        FROM lotes_joyeria lj
        LEFT JOIN sucursal s ON lj.sucursal = s.id_sucursal
        WHERE " . implode(' AND ', $where) . "
        ORDER BY lj.id_lote ASC, s.nombre_sucursal ASC
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
            'nombre_sucursal' => trim((string) ($row['nombre_sucursal'] ?? '')) !== ''
                ? (string) $row['nombre_sucursal']
                : '—',
        ];
    }

    mysqli_stmt_close($stmt);
    mysqli_close($conexion);

    echo json_encode([
        'success' => true,
        'busqueda' => $busqueda['normalizado'],
        'id_lote' => $tieneIdLote ? (int) $busqueda['digitos'] : null,
        'sucursal' => $tieneSucursal ? $busqueda['letras'] : null,
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
