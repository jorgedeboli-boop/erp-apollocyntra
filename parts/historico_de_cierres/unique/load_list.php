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
    $filtroFechaDesde = isset($_POST['filtro_fecha_desde']) ? trim($_POST['filtro_fecha_desde']) : '';
    $filtroFechaHasta = isset($_POST['filtro_fecha_hasta']) ? trim($_POST['filtro_fecha_hasta']) : '';
    $filtroPeriodo = isset($_POST['filtro_periodo']) ? trim($_POST['filtro_periodo']) : 'todos';
    
    // Validar parámetros
    if ($start < 0) $start = 0;
    if ($length < 1 || $length > 100) $length = 25;
    
    // Mapeo de columnas para ordenamiento
    $columnMap = [
        0 => 'id_fecha_cierre',
        1 => 'fecha_cierre',
        2 => 'caja',
        3 => 'efectivo',
        4 => 'diferencia',
        5 => 'usuario'
    ];
    
    // Validar columna de ordenamiento
    if (!isset($columnMap[$orderColumn])) {
        $orderColumn = 0;
    }
    
    $orderBy = $columnMap[$orderColumn];
    $orderDirection = strtoupper($orderDir) === 'ASC' ? 'ASC' : 'DESC';
    
    $tablasCierre = [];
    $resultTablas = mysqli_query($conexion, "SHOW TABLES LIKE 'cierre_caja_%'");
    if ($resultTablas) {
        while ($rowTabla = mysqli_fetch_row($resultTablas)) {
            if (preg_match('/^cierre_caja_(\d+)$/', $rowTabla[0], $m)) {
                $tablasCierre[] = [
                    'name' => $rowTabla[0],
                    'id' => (int) $m[1],
                ];
            }
        }
    }
    
    // Construir condiciones de búsqueda
    $whereConditions = [];
    $searchParams = [];
    
    if (!empty($searchValue)) {
        $whereConditions[] = "(c.id_fecha_cierre = ? OR DATE_FORMAT(c.fecha_cierre, '%d/%m/%Y %H:%i') LIKE ? OR u.usuario LIKE ?)";
        $searchTerm = "%$searchValue%";
        $searchParams = [$searchValue, $searchTerm, $searchTerm];
    }
    
    // Filtro por fecha
    if (!empty($filtroFechaDesde) && !empty($filtroFechaHasta)) {
        $whereConditions[] = "DATE(c.fecha_cierre) BETWEEN ? AND ?";
        array_push($searchParams, $filtroFechaDesde, $filtroFechaHasta);
    } else if (!empty($filtroFechaDesde)) {
        $whereConditions[] = "DATE(c.fecha_cierre) >= ?";
        $searchParams[] = $filtroFechaDesde;
    } else if (!empty($filtroFechaHasta)) {
        $whereConditions[] = "DATE(c.fecha_cierre) <= ?";
        $searchParams[] = $filtroFechaHasta;
    } else if ($filtroPeriodo === 'hoy') {
        $whereConditions[] = "DATE(c.fecha_cierre) = CURDATE()";
    } else if ($filtroPeriodo === 'mes') {
        $whereConditions[] = "YEAR(c.fecha_cierre) = YEAR(CURDATE()) AND MONTH(c.fecha_cierre) = MONTH(CURDATE())";
    }
    
    $whereClauseJoin = '';
    if (!empty($whereConditions)) {
        $whereClauseJoin = ' WHERE ' . implode(' AND ', $whereConditions);
    }
    
    // Array para almacenar todos los cierres
    $allCierres = [];
    $recordsTotal = 0;
    $recordsFiltered = 0;
    
    foreach ($tablasCierre as $tablaCierre) {
        $tableName = $tablaCierre['name'];
        $idTabla = $tablaCierre['id'];
        
        // Contar registros totales de esta tabla
        $queryCountTotal = "SELECT COUNT(*) as total FROM $tableName";
        $resultCountTotal = mysqli_query($conexion, $queryCountTotal);
        $rowCountTotal = mysqli_fetch_assoc($resultCountTotal);
        $recordsTotal += (int)$rowCountTotal['total'];
        
        // Contar registros filtrados de esta tabla
        $queryCountFiltered = "SELECT COUNT(*) as total
                               FROM $tableName c
                               LEFT JOIN usuarios u ON c.usuario_cierre = u.id_usuario
                               $whereClauseJoin";
        if (!empty($searchParams)) {
            $stmtCount = mysqli_prepare($conexion, $queryCountFiltered);
            if ($stmtCount) {
                $types = str_repeat('s', count($searchParams));
                mysqli_stmt_bind_param($stmtCount, $types, ...$searchParams);
                mysqli_stmt_execute($stmtCount);
                $resultCount = mysqli_stmt_get_result($stmtCount);
                $rowCount = mysqli_fetch_assoc($resultCount);
                $recordsFiltered += (int)$rowCount['total'];
                mysqli_stmt_close($stmtCount);
            }
        } else {
            $resultCount = mysqli_query($conexion, $queryCountFiltered);
            $rowCount = mysqli_fetch_assoc($resultCount);
            $recordsFiltered += (int)$rowCount['total'];
        }
        
        // Consulta principal para obtener cierres
        $query = "SELECT c.id_fecha_cierre, c.fecha_cierre, c.caja, c.efectivo, c.diferencia,
                         COALESCE(u.usuario, '') AS usuario
                  FROM $tableName c
                  LEFT JOIN usuarios u ON c.usuario_cierre = u.id_usuario
                  $whereClauseJoin";
        
        // Ejecutar consulta
        if (!empty($searchParams)) {
            $stmt = mysqli_prepare($conexion, $query);
            if ($stmt) {
                $types = str_repeat('s', count($searchParams));
                mysqli_stmt_bind_param($stmt, $types, ...$searchParams);
                mysqli_stmt_execute($stmt);
                $result = mysqli_stmt_get_result($stmt);
                
                if ($result) {
                    while ($row = mysqli_fetch_assoc($result)) {
                        $row['id_tabla'] = $idTabla;
                        $allCierres[] = $row;
                    }
                }
                mysqli_stmt_close($stmt);
            }
        } else {
            $result = mysqli_query($conexion, $query);
            if ($result) {
                while ($row = mysqli_fetch_assoc($result)) {
                    $row['id_tabla'] = $idTabla;
                    $allCierres[] = $row;
                }
            }
        }
    }
    
    // Ordenar todos los cierres combinados
    usort($allCierres, function($a, $b) use ($orderBy, $orderDirection) {
        $valA = $a[$orderBy] ?? '';
        $valB = $b[$orderBy] ?? '';
        
        if ($orderDirection === 'ASC') {
            return $valA <=> $valB;
        } else {
            return $valB <=> $valA;
        }
    });
    
    // Aplicar paginación
    $cierresPaginados = array_slice($allCierres, $start, $length);
    
    // Preparar datos para DataTables
    $data = [];
    foreach ($cierresPaginados as $row) {
        $data[] = [
            $row['id_fecha_cierre'],
            date('d/m/Y H:i', strtotime($row['fecha_cierre'])),
            floatval($row['caja']),
            floatval($row['efectivo']),
            floatval($row['diferencia']),
            htmlspecialchars($row['usuario']),
            [
                'id_tabla' => $row['id_tabla'],
                'id_fecha_cierre' => $row['id_fecha_cierre']
            ]
        ];
    }
    
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

