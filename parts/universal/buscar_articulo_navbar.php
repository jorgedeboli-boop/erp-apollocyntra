<?php
/**
 * Búsqueda de artículos por SKU para el buscador del navbar.
 */

require_once __DIR__ . '/../../include/session.php';
require_once __DIR__ . '/../../include/functions.php';

header('Content-Type: application/json; charset=utf-8');

if (empty($_SESSION['usuario_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'No autorizado', 'articulos' => [], 'total' => 0], JSON_UNESCAPED_UNICODE);
    exit;
}

/**
 * @param mixed $fecha
 */
function buscar_articulo_navbar_formatear_fecha($fecha)
{
    if (empty($fecha) || $fecha === '0000-00-00' || $fecha === '0000-00-00 00:00:00') {
        return '—';
    }
    $ts = strtotime((string) $fecha);
    if ($ts === false) {
        return '—';
    }
    return date('d-m-Y', $ts);
}

/**
 * @param mixed $valor
 */
function buscar_articulo_navbar_formatear_precio($valor)
{
    $num = (float) str_replace(',', '.', (string) $valor);
    return number_format($num, 2, ',', '.') . ' €';
}

/**
 * @return array{normalizado:string,digitos:string,letras:string}
 */
function buscar_articulo_navbar_parsear_busqueda($busqueda)
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

$busqueda = buscar_articulo_navbar_parsear_busqueda($busquedaRaw);
$tieneIdArticulo = $busqueda['digitos'] !== '';

if (!$tieneIdArticulo) {
    echo json_encode([
        'success' => false,
        'message' => 'Introduce el SKU del artículo para buscar.',
        'articulos' => [],
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

    $idArticulo = (int) $busqueda['digitos'];
    if ($idArticulo <= 0) {
        echo json_encode([
            'success' => false,
            'message' => 'SKU no válido.',
            'articulos' => [],
            'total' => 0,
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }

    $sql = 'SELECT
                a.sku AS id,
                a.descripcion,
                a.estado,
                a.precio,
                a.fecha_en_venta
            FROM articulos a
            WHERE a.sku = ?
            LIMIT 20';

    $stmt = mysqli_prepare($conexion, $sql);
    if (!$stmt) {
        throw new Exception('Error al preparar la búsqueda');
    }

    mysqli_stmt_bind_param($stmt, 'i', $idArticulo);
    if (!mysqli_stmt_execute($stmt)) {
        mysqli_stmt_close($stmt);
        throw new Exception('Error al ejecutar la búsqueda');
    }

    $resultado = mysqli_stmt_get_result($stmt);
    $articulos = [];
    if ($resultado) {
        while ($row = mysqli_fetch_assoc($resultado)) {
            $articulos[] = [
                'id' => (int) ($row['id'] ?? 0),
                'descripcion' => trim((string) ($row['descripcion'] ?? '')) !== ''
                    ? (string) $row['descripcion']
                    : '—',
                'estado' => trim((string) ($row['estado'] ?? '')) !== ''
                    ? (string) $row['estado']
                    : '—',
                'precio' => buscar_articulo_navbar_formatear_precio($row['precio'] ?? 0),
                'numero_venta' => '—',
                'fecha_en_venta' => buscar_articulo_navbar_formatear_fecha($row['fecha_en_venta'] ?? ''),
                'fecha_vendido' => '—',
            ];
        }
    }
    mysqli_stmt_close($stmt);
    mysqli_close($conexion);

    echo json_encode([
        'success' => true,
        'busqueda' => $busqueda['normalizado'],
        'id_articulo' => $idArticulo,
        'articulos' => $articulos,
        'total' => count($articulos),
    ], JSON_UNESCAPED_UNICODE);
} catch (Throwable $e) {
    if (isset($conexion) && $conexion) {
        mysqli_close($conexion);
    }
    echo json_encode([
        'success' => false,
        'message' => 'Error al buscar artículos.',
        'articulos' => [],
        'total' => 0,
    ], JSON_UNESCAPED_UNICODE);
}
