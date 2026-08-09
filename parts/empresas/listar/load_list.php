<?php
require_once '../../../include/session.php';
require_once '../../../include/functions.php';
/**
 * Archivo para cargar la lista de empresas via AJAX
 * Versión optimizada que funciona correctamente
 * Compatible con PHP 7.0+
 */

// Verificar versión de PHP
if (version_compare(PHP_VERSION, '7.0.0', '<')) {
    http_response_code(500);
    echo json_encode(['error' => 'Se requiere PHP 7.0 o superior']);
    exit;
}

// Asegurar que no haya salida antes del JSON
ob_clean();



if (!function_exists('conectar_bd')) {
    http_response_code(500);
    echo json_encode(['error' => 'Función conectar_bd no encontrada']);
    exit;
}

header('Content-Type: application/json');

try {
    // Conectar BD
    $conexion = conectar_bd();
    
    // Obtener parámetros de DataTables
    $start = isset($_POST['start']) ? (int)$_POST['start'] : 0;
    $length = isset($_POST['length']) ? (int)$_POST['length'] : 25;
    $searchValue = isset($_POST['search']['value']) ? trim($_POST['search']['value']) : '';
    
    // Parámetros de ordenamiento
    $orderColumn = isset($_POST['order'][0]['column']) ? (int)$_POST['order'][0]['column'] : 1; // Por defecto ordenar por nombre
    $orderDir = isset($_POST['order'][0]['dir']) ? $_POST['order'][0]['dir'] : 'asc';
    
    // Filtros personalizados de columnas
    $filtroEmpresa = isset($_POST['filtro_empresa']) ? trim($_POST['filtro_empresa']) : '';
    $filtroPoblacion = isset($_POST['filtro_poblacion']) ? trim($_POST['filtro_poblacion']) : '';
    $filtroProvincia = isset($_POST['filtro_provincia']) ? trim($_POST['filtro_provincia']) : '';
    
    // Validar parámetros
    if ($start < 0) $start = 0;
    if ($length < 1 || $length > 100) $length = 25;
    
    // Mapeo de columnas para ordenamiento
    $columnMap = [
        0 => 'e.id_empresa',
        1 => 'e.nombre_empresa',
        2 => 'e.direccion_empresa',
        3 => 'e.poblacion_empresa',
        4 => 'e.provincia_empresa',
        5 => 'e.telefono_empresa',
        6 => 'e.cif_empresa'
    ];
    
    // Validar columna de ordenamiento
    if (!isset($columnMap[$orderColumn])) {
        $orderColumn = 1; // Por defecto ordenar por nombre
    }
    
    $orderBy = $columnMap[$orderColumn];
    $orderDirection = strtoupper($orderDir) === 'ASC' ? 'ASC' : 'DESC';
    
    // Construir condiciones de búsqueda
    $whereConditions = [];
    $searchParams = [];
    
    // Condición base
    $whereConditions[] = "e.empresa_app = 'false'";
    
    if (!empty($searchValue)) {
        $whereConditions[] = "(e.id_empresa = ? OR e.nombre_empresa LIKE ? OR e.direccion_empresa LIKE ? OR e.poblacion_empresa LIKE ? OR e.provincia_empresa LIKE ? OR e.telefono_empresa LIKE ? OR e.cif_empresa LIKE ?)";
        $searchTerm = "%$searchValue%";
        $searchParams = [$searchValue, $searchTerm, $searchTerm, $searchTerm, $searchTerm, $searchTerm, $searchTerm];
    }
    
    // Agregar filtros de columna personalizados
    if (!empty($filtroEmpresa)) {
        $whereConditions[] = "e.nombre_empresa LIKE ?";
        $searchParams[] = "%$filtroEmpresa%";
    }
    
    if (!empty($filtroPoblacion)) {
        $whereConditions[] = "e.poblacion_empresa LIKE ?";
        $searchParams[] = "%$filtroPoblacion%";
    }
    
    if (!empty($filtroProvincia)) {
        $whereConditions[] = "e.provincia_empresa LIKE ?";
        $searchParams[] = "%$filtroProvincia%";
    }
    
    $whereClause = 'WHERE ' . implode(' AND ', $whereConditions);
    
    // Consulta para contar total de registros filtrados
    $queryCount = "SELECT COUNT(*) as total FROM empresas e $whereClause";
    
    // Variable para almacenar el total de registros filtrados
    $totalFiltrados = 0;
    
    if (!empty($searchParams)) {
        $stmtCount = mysqli_prepare($conexion, $queryCount);
        if ($stmtCount) {
            $types = str_repeat('s', count($searchParams));
            call_user_func_array('mysqli_stmt_bind_param', array_merge(array($stmtCount, $types), $searchParams));
            mysqli_stmt_execute($stmtCount);
            $resultCount = mysqli_stmt_get_result($stmtCount);
        } else {
            throw new Exception("Error en preparación de consulta de conteo: " . mysqli_error($conexion));
        }
    } else {
        $resultCount = mysqli_query($conexion, $queryCount);
    }
    
    if (!$resultCount) {
        throw new Exception("Error en consulta de conteo: " . mysqli_error($conexion));
    }
    $rowCount = mysqli_fetch_assoc($resultCount);
    $totalRegistros = (int)$rowCount['total'];
    $totalFiltrados = $totalRegistros; // Total de registros que coinciden con los filtros
    
    // Consulta principal con paginación y filtros
    $query = "
        SELECT 
            e.id_empresa,
            e.nombre_empresa,
            e.direccion_empresa,
            e.poblacion_empresa,
            e.provincia_empresa,
            e.telefono_empresa,
            e.cif_empresa,
            e.fecha_creacion_empresa
        FROM empresas e
        $whereClause
        ORDER BY $orderBy $orderDirection
        LIMIT $start, $length
    ";
    
    if (!empty($searchParams)) {
        $stmt = mysqli_prepare($conexion, $query);
        if ($stmt) {
            $types = str_repeat('s', count($searchParams));
            call_user_func_array('mysqli_stmt_bind_param', array_merge(array($stmt, $types), $searchParams));
            mysqli_stmt_execute($stmt);
            $result = mysqli_stmt_get_result($stmt);
        } else {
            throw new Exception("Error en preparación de consulta principal: " . mysqli_error($conexion));
        }
    } else {
        $result = mysqli_query($conexion, $query);
    }
    
    if (!$result) {
        throw new Exception("Error en consulta: " . mysqli_error($conexion));
    }
    
    // Datos simples
    $data = [];
    while ($row = mysqli_fetch_assoc($result)) {
        $data[] = [
            $row['id_empresa'], // ID
            $row['nombre_empresa'], // Nombre empresa
            $row['direccion_empresa'], // Dirección
            $row['poblacion_empresa'], // Población
            $row['provincia_empresa'], // Provincia
            $row['telefono_empresa'], // Teléfono
            $row['cif_empresa'] // CIF
        ];
    }
    
    // Respuesta para serverSide
    $response = [
        'draw' => isset($_POST['draw']) ? (int)$_POST['draw'] : 1,
        'recordsTotal' => $totalRegistros, // Total de registros en la tabla
        'recordsFiltered' => $totalFiltrados, // Total de registros después de filtros (sin paginación)
        'data' => $data
    ];
    
    // Debug: Log de la respuesta
    error_log("Respuesta empresas enviada - draw: {$response['draw']}, total: {$response['recordsTotal']}, filtrados: {$response['recordsFiltered']}, datos: " . count($response['data']));
    
    echo json_encode($response);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'error' => $e->getMessage(),
        'draw' => isset($_POST['draw']) ? (int)$_POST['draw'] : 1,
        'recordsTotal' => 0,
        'recordsFiltered' => 0,
        'data' => []
    ]);
}

    mysqli_close($conexion);
?>