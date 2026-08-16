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

/**
 * @return list<string>
 */
function buscar_articulo_navbar_tablas(mysqli $conexion)
{
    $tablas = [];
    $res = mysqli_query($conexion, "SHOW TABLES LIKE 'articulos_venta'");
    if ($res && mysqli_fetch_row($res)) {
        $tablas[] = 'articulos_venta';
    }
    if (empty($tablas)) {
        $res = mysqli_query($conexion, "SHOW TABLES LIKE 'articulos'");
        if ($res && mysqli_fetch_row($res)) {
            $tablas[] = 'articulos';
        }
    }
    if (empty($tablas)) {
        $res = mysqli_query($conexion, "SHOW TABLES LIKE 'articulos\\_%'");
        if ($res) {
            while ($row = mysqli_fetch_row($res)) {
                $nombre = (string) ($row[0] ?? '');
                if (preg_match('/^articulos_\\d+$/', $nombre)) {
                    $tablas[] = $nombre;
                }
            }
        }
    }
    return $tablas;
}

/**
 * @return array<string,bool>
 */
function buscar_articulo_navbar_columnas(mysqli $conexion, $tabla)
{
    $columnas = [];
    $res = mysqli_query($conexion, 'SHOW COLUMNS FROM `' . str_replace('`', '', $tabla) . '`');
    if (!$res) {
        return $columnas;
    }
    while ($col = mysqli_fetch_assoc($res)) {
        $columnas[$col['Field']] = true;
    }
    return $columnas;
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

    $articulos = [];
    foreach (buscar_articulo_navbar_tablas($conexion) as $tabla) {
        $cols = buscar_articulo_navbar_columnas($conexion, $tabla);
        if (empty($cols)) {
            continue;
        }

        $idCampo = isset($cols['id']) ? 'id' : (isset($cols['id_articulo']) ? 'id_articulo' : '');
        if ($idCampo === '') {
            continue;
        }

        $select = ['`' . $idCampo . '` AS id'];
        $select[] = isset($cols['descripcion']) ? 'descripcion' : "'' AS descripcion";
        $select[] = isset($cols['estado']) ? 'estado' : "'' AS estado";
        $select[] = isset($cols['precio']) ? 'precio' : '0 AS precio';
        $select[] = isset($cols['last_id_venta']) ? 'last_id_venta' : (isset($cols['id_venta']) ? 'id_venta AS last_id_venta' : '0 AS last_id_venta');
        $select[] = isset($cols['fecha_en_venta']) ? 'fecha_en_venta' : "NULL AS fecha_en_venta";
        $select[] = isset($cols['fecha_vendido']) ? 'fecha_vendido' : "NULL AS fecha_vendido";

        $sql = 'SELECT ' . implode(', ', $select) . '
                FROM `' . str_replace('`', '', $tabla) . '`
                WHERE `' . $idCampo . '` = ?
                LIMIT 20';

        $stmt = mysqli_prepare($conexion, $sql);
        if (!$stmt) {
            continue;
        }
        mysqli_stmt_bind_param($stmt, 'i', $idArticulo);
        if (!mysqli_stmt_execute($stmt)) {
            mysqli_stmt_close($stmt);
            continue;
        }
        $resultado = mysqli_stmt_get_result($stmt);
        if ($resultado) {
            while ($row = mysqli_fetch_assoc($resultado)) {
                $lastVenta = (int) ($row['last_id_venta'] ?? 0);
                $articulos[] = [
                    'id' => (int) ($row['id'] ?? 0),
                    'descripcion' => trim((string) ($row['descripcion'] ?? '')) !== ''
                        ? (string) $row['descripcion']
                        : '—',
                    'estado' => trim((string) ($row['estado'] ?? '')) !== ''
                        ? (string) $row['estado']
                        : '—',
                    'precio' => buscar_articulo_navbar_formatear_precio($row['precio'] ?? 0),
                    'numero_venta' => $lastVenta > 0 ? (string) $lastVenta : '—',
                    'fecha_en_venta' => buscar_articulo_navbar_formatear_fecha($row['fecha_en_venta'] ?? ''),
                    'fecha_vendido' => buscar_articulo_navbar_formatear_fecha($row['fecha_vendido'] ?? ''),
                ];
            }
        }
        mysqli_stmt_close($stmt);
        if (count($articulos) >= 50) {
            break;
        }
    }

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
