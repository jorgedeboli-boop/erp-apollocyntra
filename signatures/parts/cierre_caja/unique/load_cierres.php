<?php
require_once '../../../include/session.php';
require_once '../../../include/functions.php';

// Verificar que el usuario esté autenticado
if (!isset($_SESSION['usuario_id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'No autorizado']);
    exit;
}

// Parámetros de DataTables
$draw = isset($_POST['draw']) ? intval($_POST['draw']) : 1;
$start = isset($_POST['start']) ? intval($_POST['start']) : 0;
$length = isset($_POST['length']) ? intval($_POST['length']) : 10;
$search = isset($_POST['search']['value']) ? trim($_POST['search']['value']) : '';

// Parámetro de sucursal
$idSucursal = isset($_POST['id_sucursal']) ? intval($_POST['id_sucursal']) : 0;

if ($idSucursal === 0) {
    http_response_code(400);
    echo json_encode(['error' => 'ID de sucursal no válido']);
    exit;
}

// Parámetros de filtro de fecha
$filtroFechaDesde = isset($_POST['filtro_fecha_desde']) ? trim($_POST['filtro_fecha_desde']) : '';
$filtroFechaHasta = isset($_POST['filtro_fecha_hasta']) ? trim($_POST['filtro_fecha_hasta']) : '';
$filtroPeriodo = isset($_POST['filtro_periodo']) ? trim($_POST['filtro_periodo']) : 'todos';

// Parámetros de ordenamiento
$orderColumn = isset($_POST['order'][0]['column']) ? (int)$_POST['order'][0]['column'] : 0;
$orderDir = isset($_POST['order'][0]['dir']) ? $_POST['order'][0]['dir'] : 'desc';

// Mapeo de columnas para ordenamiento
$columnMap = [
    0 => 'id_fecha_cierre',
    1 => 'fecha_cierre',
    2 => 'caja',
    3 => 'efectivo',
    4 => 'diferencia'
];

// Validar columna de ordenamiento
if (!isset($columnMap[$orderColumn])) {
    $orderColumn = 0; // Por defecto ordenar por ID
}

$orderBy = $columnMap[$orderColumn];
$orderDirection = strtoupper($orderDir) === 'ASC' ? 'ASC' : 'DESC';

try {
    $conexion = conectar_bd();
    
    if (!$conexion) {
        throw new Exception('Error de conexión a la base de datos');
    }
    
    // Nombre de la tabla de cierres
    $tableName = "cierre_caja_" . $idSucursal;
    
    // Verificar si la tabla existe
    $tableCheck = mysqli_query($conexion, "SHOW TABLES LIKE '$tableName'");
    
    if (mysqli_num_rows($tableCheck) === 0) {
        // Si no existe la tabla, devolver respuesta vacía
        echo json_encode([
            'draw' => $draw,
            'recordsTotal' => 0,
            'recordsFiltered' => 0,
            'data' => []
        ]);
        mysqli_close($conexion);
        exit;
    }
    
    // Construir la consulta base
    $query_base = "FROM $tableName WHERE 1=1";
    
    $params = [];
    $types = '';
    
    // Filtro por periodo
    if ($filtroPeriodo === 'hoy') {
        $query_base .= " AND DATE(fecha_cierre) = CURDATE()";
    } elseif ($filtroPeriodo === 'mes') {
        $query_base .= " AND MONTH(fecha_cierre) = MONTH(CURDATE()) AND YEAR(fecha_cierre) = YEAR(CURDATE())";
    } elseif ($filtroPeriodo === 'rango' && !empty($filtroFechaDesde) && !empty($filtroFechaHasta)) {
        $query_base .= " AND DATE(fecha_cierre) BETWEEN ? AND ?";
        $params[] = $filtroFechaDesde;
        $params[] = $filtroFechaHasta;
        $types .= 'ss';
    }
    // Si es 'todos', no se agrega filtro de fecha
    
    // Búsqueda global
    if (!empty($search)) {
        $query_base .= " AND (id_fecha_cierre LIKE ? OR DATE_FORMAT(fecha_cierre, '%d/%m/%Y %H:%i') LIKE ?)";
        $search_param = "%$search%";
        $params[] = $search_param;
        $params[] = $search_param;
        $types .= 'ss';
    }
    
    // Contar total de registros
    $query_count = "SELECT COUNT(*) as total " . $query_base;
    $stmt_count = mysqli_prepare($conexion, $query_count);
    
    if (!empty($params)) {
        mysqli_stmt_bind_param($stmt_count, $types, ...$params);
    }
    
    mysqli_stmt_execute($stmt_count);
    $result_count = mysqli_stmt_get_result($stmt_count);
    $total_records = mysqli_fetch_assoc($result_count)['total'];
    mysqli_stmt_close($stmt_count);
    
    // Consulta principal con paginación
    $query_main = "SELECT 
                        id_fecha_cierre,
                        fecha_cierre,
                        caja,
                        efectivo,
                        diferencia
                    " . $query_base . " 
                    ORDER BY $orderBy $orderDirection 
                    LIMIT ?, ?";
    
    // Agregar parámetros de paginación
    $params[] = $start;
    $params[] = $length;
    $types .= 'ii';
    
    $stmt_main = mysqli_prepare($conexion, $query_main);
    mysqli_stmt_bind_param($stmt_main, $types, ...$params);
    mysqli_stmt_execute($stmt_main);
    $result_main = mysqli_stmt_get_result($stmt_main);
    
    $data = [];
    while ($row = mysqli_fetch_assoc($result_main)) {
        // Formatear datos para la tabla
        $data[] = [
            $row['id_fecha_cierre'], // Índice 0 - Nº Arqueo
            $row['fecha_cierre'], // Índice 1 - Fecha de Arqueo
            floatval($row['caja']), // Índice 2 - Caja
            floatval($row['efectivo']), // Índice 3 - Efectivo
            floatval($row['diferencia']) // Índice 4 - Diferencia
        ];
    }
    
    mysqli_stmt_close($stmt_main);
    mysqli_close($conexion);
    
    // Respuesta para DataTables
    echo json_encode([
        'draw' => $draw,
        'recordsTotal' => $total_records,
        'recordsFiltered' => $total_records,
        'data' => $data
    ]);
    
} catch (Exception $e) {
    error_log("Error en load_cierres: " . $e->getMessage());
    
    if (isset($stmt_main)) {
        mysqli_stmt_close($stmt_main);
    }
    if (isset($conexion)) {
        mysqli_close($conexion);
    }
    
    http_response_code(500);
    echo json_encode([
        'error' => 'Error del servidor: ' . $e->getMessage()
    ]);
}
?>

