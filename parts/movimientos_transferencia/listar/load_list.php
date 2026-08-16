<?php
require_once '../../../include/session.php';

// Verificar versión de PHP
if (version_compare(PHP_VERSION, '7.0.0', '<')) {
    http_response_code(500);
    echo json_encode(['error' => 'Se requiere PHP 7.0 o superior']);
    exit;
}

// Asegurar que no haya salida antes del JSON
ob_clean();

header('Content-Type: application/json');

try {
    // Conectar BD
    $conexion = conectar_bd();
    
    // Obtener parámetros de DataTables
    $start = isset($_POST['start']) ? (int)$_POST['start'] : 0;
    $length = isset($_POST['length']) ? (int)$_POST['length'] : 25;
    $searchValue = isset($_POST['search']['value']) ? trim($_POST['search']['value']) : '';
    
    // Parámetros de ordenamiento
    $orderColumn = isset($_POST['order'][0]['column']) ? (int)$_POST['order'][0]['column'] : 0; // Por defecto ordenar por ID descendente
    $orderDir = isset($_POST['order'][0]['dir']) ? $_POST['order'][0]['dir'] : 'desc';
    
    // Filtros personalizados
    $filtroGrupo = isset($_POST['filtro_grupo']) ? trim($_POST['filtro_grupo']) : '';
    $filtroFechaDesde = isset($_POST['filtro_fecha_desde']) ? trim($_POST['filtro_fecha_desde']) : '';
    $filtroFechaHasta = isset($_POST['filtro_fecha_hasta']) ? trim($_POST['filtro_fecha_hasta']) : '';
    $filtroPeriodo = isset($_POST['filtro_periodo']) ? trim($_POST['filtro_periodo']) : 'dia';
    
    // Validar parámetros
    if ($start < 0) $start = 0;
    if (defined('MOVIMIENTOS_EXPORT_ALL') && MOVIMIENTOS_EXPORT_ALL) {
        if ($length < 1) $length = 500000;
    } elseif ($length < 1 || $length > 100) {
        $length = 25;
    }
    
    // Mapeo de columnas para ordenamiento
    $columnMap = [
        0 => 'mt.id',
        1 => 'mt.fecha',
        2 => 'mt.grupos',
        3 => 'mt.descripcion',
        4 => 'mt.salida',
        5 => 'mt.entrada',
        6 => 'u.usuario'
    ];
    
    // Validar columna de ordenamiento
    if (!isset($columnMap[$orderColumn])) {
        $orderColumn = 1;
    }
    
    $orderBy = $columnMap[$orderColumn];
    $orderDirection = strtoupper($orderDir) === 'ASC' ? 'ASC' : 'DESC';
    
    // Construir condiciones de búsqueda
    $whereConditions = [];
    $searchParams = [];
    
    if (!empty($searchValue)) {
        $whereConditions[] = "(mt.id = ? OR mt.grupos LIKE ? OR mt.descripcion LIKE ? OR mt.usuario LIKE ?)";
        $searchTerm = "%$searchValue%";
        $searchParams = [$searchValue, $searchTerm, $searchTerm, $searchTerm];
    }
    
    // Filtro por grupo
    if (!empty($filtroGrupo)) {
        $whereConditions[] = "mt.grupos = ?";
        $searchParams[] = $filtroGrupo;
    }
    
    // Filtro por fecha
    if (!empty($filtroFechaDesde) && !empty($filtroFechaHasta)) {
        $whereConditions[] = "mt.fecha BETWEEN ? AND DATE_ADD(?, INTERVAL 1 DAY)";
        array_push($searchParams, $filtroFechaDesde, $filtroFechaHasta);
    } else if (!empty($filtroFechaDesde)) {
        $whereConditions[] = "mt.fecha >= ?";
        $searchParams[] = $filtroFechaDesde;
    } else if (!empty($filtroFechaHasta)) {
        $whereConditions[] = "mt.fecha <= DATE_ADD(?, INTERVAL 1 DAY)";
        $searchParams[] = $filtroFechaHasta;
    } else if ($filtroPeriodo === 'hoy' || $filtroPeriodo === 'dia') {
        $whereConditions[] = "DATE(mt.fecha) = CURDATE()";
    } else if ($filtroPeriodo === 'mes') {
        $whereConditions[] = "YEAR(mt.fecha) = YEAR(CURDATE()) AND MONTH(mt.fecha) = MONTH(CURDATE())";
    }
    
    $whereClause = '';
    if (!empty($whereConditions)) {
        $whereClause = ' WHERE ' . implode(' AND ', $whereConditions);
    }
    
    // Contar registros totales
    $queryCountTotal = "SELECT COUNT(*) as total 
                       FROM movimientos_transferencia mt";
    $resultCountTotal = mysqli_query($conexion, $queryCountTotal);
    $rowCountTotal = mysqli_fetch_assoc($resultCountTotal);
    $recordsTotal = (int)$rowCountTotal['total'];
    
    // Contar registros filtrados
    $queryCountFiltered = "SELECT COUNT(*) as total 
                          FROM movimientos_transferencia mt
                          $whereClause";
    
    if (!empty($searchParams)) {
        $stmtCount = mysqli_prepare($conexion, $queryCountFiltered);
        $types = str_repeat('s', count($searchParams));
        mysqli_stmt_bind_param($stmtCount, $types, ...$searchParams);
        mysqli_stmt_execute($stmtCount);
        $resultCount = mysqli_stmt_get_result($stmtCount);
        $rowCount = mysqli_fetch_assoc($resultCount);
        $recordsFiltered = (int)$rowCount['total'];
        mysqli_stmt_close($stmtCount);
    } else {
        $resultCount = mysqli_query($conexion, $queryCountFiltered);
        $rowCount = mysqli_fetch_assoc($resultCount);
        $recordsFiltered = (int)$rowCount['total'];
    }
    
    // Consulta principal
    $query = "SELECT mt.id, mt.fecha, mt.grupos, mt.descripcion, mt.salida, mt.entrada,
                     COALESCE(u.usuario, mt.usuario) as usuario
              FROM movimientos_transferencia mt
              LEFT JOIN usuarios u ON mt.usuario = u.id_usuario
              $whereClause
              ORDER BY $orderBy $orderDirection
              LIMIT ? OFFSET ?";
    
    // Preparar consulta
    $searchParams[] = $length;
    $searchParams[] = $start;
    
    $stmt = mysqli_prepare($conexion, $query);
    $types = str_repeat('s', count($searchParams) - 2) . 'ii';
    mysqli_stmt_bind_param($stmt, $types, ...$searchParams);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    
    // Preparar datos para DataTables
    $data = [];
    while ($row = mysqli_fetch_assoc($result)) {
        $data[] = [
            $row['id'],
            date('d/m/Y H:i', strtotime($row['fecha'])),
            htmlspecialchars($row['grupos']),
            htmlspecialchars($row['descripcion']),
            $row['salida'],
            $row['entrada'],
            htmlspecialchars($row['usuario'])
        ];
    }
    
    mysqli_stmt_close($stmt);
    
    // Respuesta para DataTables
    $response = [
        'draw' => isset($_POST['draw']) ? (int)$_POST['draw'] : 1,
        'recordsTotal' => $recordsTotal,
        'recordsFiltered' => $recordsFiltered,
        'data' => $data
    ];
    
    echo json_encode($response);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'error' => $e->getMessage(),
        'trace' => $e->getTraceAsString()
    ]);
} finally {
    if (isset($conexion)) {
        mysqli_close($conexion);
    }
}
?>

