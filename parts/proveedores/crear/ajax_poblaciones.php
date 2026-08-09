<?php
/**
 * Archivo para cargar poblaciones, provincias y países via AJAX
 * Compatible con PHP 7.0+
 */

// Asegurar que no haya salida antes del JSON
ob_start();

// Verificar versión de PHP
if (version_compare(PHP_VERSION, '7.0.0', '<')) {
    ob_clean();
    header('Content-Type: application/json');
    http_response_code(500);
    echo json_encode(['error' => 'Se requiere PHP 7.0 o superior']);
    exit;
}

require_once '../../../include/session.php';
require_once '../../../include/functions.php';

// Limpiar cualquier salida previa
ob_clean();

header('Content-Type: application/json');

// Obtener parámetros
$action = isset($_GET['action']) ? $_GET['action'] : '';
$search = isset($_GET['search']) ? $_GET['search'] : '';
$page = isset($_GET['page']) ? $_GET['page'] : 1;
$idprovincia = isset($_GET['idprovincia']) ? $_GET['idprovincia'] : '';
$idpais = isset($_GET['idpais']) ? $_GET['idpais'] : '';

// Log para debug
error_log("AJAX Proveedores - Action: $action, Search: $search, Page: $page");

try {
    if (empty($action)) {
        throw new Exception('No se especificó ninguna acción');
    }
    
    switch ($action) {
        case 'paises':
            $result = obtenerPaises($search, $page);
            break;
            
        case 'provincias':
            $result = obtenerProvincias($search, $page, $idpais);
            break;
            
        case 'poblaciones':
            $result = obtenerPoblaciones($search, $page, $idprovincia);
            break;
            
        case 'poblacion_detalle':
            $idpoblacion = isset($_GET['idpoblacion']) ? $_GET['idpoblacion'] : '';
            $result = obtenerDetallePoblacion($idpoblacion);
            break;
            
        default:
            throw new Exception('Acción no válida: ' . $action);
    }
    
    error_log("AJAX Proveedores - Result count: " . count($result['results'] ?? []));
    echo json_encode($result);
    
} catch (Exception $e) {
    error_log("AJAX Proveedores - Error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'error' => true,
        'message' => $e->getMessage(),
        'file' => $e->getFile(),
        'line' => $e->getLine()
    ]);
}

/**
 * Obtener países
 */
function obtenerPaises($search = '', $page = 1) {
    $conexion = conectar_bd();
    
    $limit = 20;
    $offset = ($page - 1) * $limit;
    
    $sql = "SELECT id_country as id, name_spanish as text FROM countrys";
    
    if (!empty($search)) {
        $sql .= " WHERE name_spanish LIKE '%" . mysqli_real_escape_string($conexion, $search) . "%'";
    }
    
    $sql .= " ORDER BY name_spanish LIMIT $limit OFFSET $offset";
    
    $result = mysqli_query($conexion, $sql);
    
    if (!$result) {
        mysqli_close($conexion);
        throw new Exception('Error en consulta: ' . mysqli_error($conexion));
    }
    
    $paises = [];
    while ($row = mysqli_fetch_assoc($result)) {
        $paises[] = $row;
    }
    
    // Obtener total para paginación
    $sqlTotal = "SELECT COUNT(*) as total FROM countrys";
    if (!empty($search)) {
        $sqlTotal .= " WHERE name_spanish LIKE '%" . mysqli_real_escape_string($conexion, $search) . "%'";
    }
    
    $resultTotal = mysqli_query($conexion, $sqlTotal);
    $total = mysqli_fetch_assoc($resultTotal)['total'];
    
    mysqli_close($conexion);
    
    return [
        'results' => $paises,
        'pagination' => [
            'more' => ($offset + $limit) < $total
        ]
    ];
}

/**
 * Obtener provincias
 */
function obtenerProvincias($search = '', $page = 1, $idpais = '') {
    $conexion = conectar_bd();
    
    $limit = 20;
    $offset = ($page - 1) * $limit;
    
    $sql = "SELECT p.id_province as id, p.nombreProvince as text FROM provincias p";
    $whereConditions = [];
    
    if (!empty($idpais)) {
        $whereConditions[] = "p.id_rel_country = '" . mysqli_real_escape_string($conexion, $idpais) . "'";
    }
    
    if (!empty($search)) {
        $whereConditions[] = "p.nombreProvince LIKE '%" . mysqli_real_escape_string($conexion, $search) . "%'";
    }
    
    if (!empty($whereConditions)) {
        $sql .= " WHERE " . implode(" AND ", $whereConditions);
    }
    
    $sql .= " ORDER BY p.nombreProvince LIMIT $limit OFFSET $offset";
    
    $result = mysqli_query($conexion, $sql);
    
    if (!$result) {
        mysqli_close($conexion);
        throw new Exception('Error en consulta provincias: ' . mysqli_error($conexion));
    }
    
    $provincias = [];
    while ($row = mysqli_fetch_assoc($result)) {
        $provincias[] = $row;
    }
    
    // Obtener total para paginación
    $sqlTotal = "SELECT COUNT(*) as total FROM provincias p";
    if (!empty($whereConditions)) {
        $sqlTotal .= " WHERE " . implode(" AND ", $whereConditions);
    }
    
    $resultTotal = mysqli_query($conexion, $sqlTotal);
    $total = mysqli_fetch_assoc($resultTotal)['total'];
    
    mysqli_close($conexion);
    
    return [
        'results' => $provincias,
        'pagination' => [
            'more' => ($offset + $limit) < $total
        ]
    ];
}

/**
 * Obtener poblaciones
 */
function obtenerPoblaciones($search = '', $page = 1, $idprovincia = '') {
    $conexion = conectar_bd();
    
    $limit = 20;
    $offset = ($page - 1) * $limit;
    
    $sql = "SELECT p.idpoblacion as id, p.poblacion as text FROM poblacion p";
    $whereConditions = [];
    
    if (!empty($idprovincia)) {
        $whereConditions[] = "p.idprovincia = '" . mysqli_real_escape_string($conexion, $idprovincia) . "'";
    }
    
    if (!empty($search)) {
        $whereConditions[] = "p.poblacion LIKE '%" . mysqli_real_escape_string($conexion, $search) . "%'";
    }
    
    if (!empty($whereConditions)) {
        $sql .= " WHERE " . implode(" AND ", $whereConditions);
    }
    
    $sql .= " ORDER BY p.poblacion LIMIT $limit OFFSET $offset";
    
    $result = mysqli_query($conexion, $sql);
    
    if (!$result) {
        mysqli_close($conexion);
        throw new Exception('Error en consulta poblaciones: ' . mysqli_error($conexion));
    }
    
    $poblaciones = [];
    while ($row = mysqli_fetch_assoc($result)) {
        $poblaciones[] = $row;
    }
    
    // Obtener total para paginación
    $sqlTotal = "SELECT COUNT(*) as total FROM poblacion p";
    if (!empty($whereConditions)) {
        $sqlTotal .= " WHERE " . implode(" AND ", $whereConditions);
    }
    
    $resultTotal = mysqli_query($conexion, $sqlTotal);
    $total = mysqli_fetch_assoc($resultTotal)['total'];
    
    mysqli_close($conexion);
    
    return [
        'results' => $poblaciones,
        'pagination' => [
            'more' => ($offset + $limit) < $total
        ]
    ];
}

/**
 * Obtener detalle de población (código postal, provincia y país)
 */
function obtenerDetallePoblacion($idpoblacion) {
    $conexion = conectar_bd();
    
    $sql = "SELECT 
                p.idpoblacion,
                p.poblacion,
                p.postal as codigo_postal,
                p.idprovincia,
                prov.nombreProvince as provincia,
                prov.id_rel_country,
                c.name_spanish as pais
            FROM poblacion p
            LEFT JOIN provincias prov ON p.idprovincia = prov.id_province
            LEFT JOIN countrys c ON prov.id_rel_country = c.id_country
            WHERE p.idpoblacion = ?";
    
    $stmt = mysqli_prepare($conexion, $sql);
    mysqli_stmt_bind_param($stmt, 'i', $idpoblacion);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    
    if ($row = mysqli_fetch_assoc($result)) {
        mysqli_close($conexion);
        return [
            'success' => true,
            'data' => $row
        ];
    } else {
        mysqli_close($conexion);
        return [
            'success' => false,
            'message' => 'Población no encontrada'
        ];
    }
}
?>

