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
    
    // Parámetros de ordenamiento (por defecto: fecha + hora, más reciente primero)
    $orderColumn = isset($_POST['order'][0]['column']) ? (int)$_POST['order'][0]['column'] : 1;
    $orderDir = isset($_POST['order'][0]['dir']) ? $_POST['order'][0]['dir'] : 'desc';
    
    // Filtros personalizados
    $filtroSucursal = isset($_POST['filtro_sucursal']) ? trim($_POST['filtro_sucursal']) : '';
    $filtroGrupo = isset($_POST['filtro_grupo']) ? trim($_POST['filtro_grupo']) : '';
    $filtroFechaDesde = isset($_POST['filtro_fecha_desde']) ? trim($_POST['filtro_fecha_desde']) : '';
    $filtroFechaHasta = isset($_POST['filtro_fecha_hasta']) ? trim($_POST['filtro_fecha_hasta']) : '';
    $filtroPeriodoProvided = isset($_POST['filtro_periodo']);
    $filtroPeriodo = $filtroPeriodoProvided ? trim((string) $_POST['filtro_periodo']) : 'dia';
    $filtroPeriodoLower = strtolower($filtroPeriodo);
    
    // Validar parámetros
    if ($start < 0) $start = 0;
    if (defined('MOVIMIENTOS_EXPORT_ALL') && MOVIMIENTOS_EXPORT_ALL) {
        if ($length < 1) $length = 500000;
    } elseif ($length < 1 || $length > 100) {
        $length = 25;
    }
    
    // Mapeo de columnas para ordenamiento
    $columnMap = [
        0 => 'id_movimientos',
        1 => 'fecha_apunte',
        2 => 'sucursal_nombre',
        3 => 'grupos',
        4 => 'concepto',
        5 => 'salida',
        6 => 'entrada',
        7 => 'usuario'
    ];
    
    // Validar columna de ordenamiento
    if (!isset($columnMap[$orderColumn])) {
        $orderColumn = 1;
    }
    
    $orderBy = $columnMap[$orderColumn];
    $orderDirection = strtoupper($orderDir) === 'ASC' ? 'ASC' : 'DESC';
    
    // Obtener sucursales disponibles (sin filtro para incluir todas)
    $querySucursales = "SELECT id_sucursal, nombre_sucursal FROM sucursal ORDER BY nombre_sucursal";
    $resultSucursales = mysqli_query($conexion, $querySucursales);
    $sucursales = [];
    
    while ($row = mysqli_fetch_assoc($resultSucursales)) {
        $sucursales[(int) $row['id_sucursal']] = $row['nombre_sucursal'];
    }
    
    // Si se especificó un filtro de sucursal (id_sucursal), solo consultar esa sucursal
    $filtroSucursalId = $filtroSucursal !== '' ? (int) $filtroSucursal : 0;
    $sucursalesAConsultar = [];
    if ($filtroSucursalId > 0 && isset($sucursales[$filtroSucursalId])) {
        $sucursalesAConsultar = [$filtroSucursalId => $sucursales[$filtroSucursalId]];
    } else {
        $sucursalesAConsultar = $sucursales;
    }
    
    // Construir condiciones de búsqueda
    $whereConditions = [];
    $searchParams = [];
    
    if (!empty($searchValue)) {
        $whereConditions[] = "(id_movimientos = ? OR grupos LIKE ? OR concepto LIKE ? OR usuario LIKE ?)";
        $searchTerm = "%$searchValue%";
        $searchParams = [$searchValue, $searchTerm, $searchTerm, $searchTerm];
    }
    
    // Filtro por grupo
    if (!empty($filtroGrupo)) {
        $whereConditions[] = "grupos = ?";
        $searchParams[] = $filtroGrupo;
    }
    
    // Filtro por fecha
    // Por defecto: hoy. Solo "todos" sin fechas muestra los últimos 18 meses.
    if (
        empty($filtroFechaDesde) &&
        empty($filtroFechaHasta) &&
        $filtroPeriodoLower === 'todos'
    ) {
        $whereConditions[] = "fecha_apunte BETWEEN DATE_SUB(CURDATE(), INTERVAL 18 MONTH) AND CURDATE()";
    } else if (!empty($filtroFechaDesde) && !empty($filtroFechaHasta)) {
        $whereConditions[] = "fecha_apunte BETWEEN ? AND ?";
        array_push($searchParams, $filtroFechaDesde, $filtroFechaHasta);
    } else if (!empty($filtroFechaDesde)) {
        $whereConditions[] = "fecha_apunte >= ?";
        $searchParams[] = $filtroFechaDesde;
    } else if (!empty($filtroFechaHasta)) {
        $whereConditions[] = "fecha_apunte <= ?";
        $searchParams[] = $filtroFechaHasta;
    } else if ($filtroPeriodoLower === 'hoy' || $filtroPeriodoLower === 'dia') {
        $whereConditions[] = "fecha_apunte = CURDATE()";
    } else if ($filtroPeriodoLower === 'mes') {
        $whereConditions[] = "YEAR(fecha_apunte) = YEAR(CURDATE()) AND MONTH(fecha_apunte) = MONTH(CURDATE())";
    }
    
    $whereClause = '';
    if (!empty($whereConditions)) {
        $whereClause = ' WHERE ' . implode(' AND ', $whereConditions);
    }
    
    // Array para almacenar todos los movimientos
    $allMovimientos = [];
    $recordsTotal = 0;
    $recordsFiltered = 0;
    
    // Recorrer todas las sucursales y combinar resultados
    foreach ($sucursalesAConsultar as $idSucursal => $nombreSucursal) {
        $tableName = "movimientos_de_caja_$idSucursal";
        
        // Verificar si la tabla existe
        $checkTable = mysqli_query($conexion, "SHOW TABLES LIKE '$tableName'");
        if (mysqli_num_rows($checkTable) == 0) {
            continue;
        }
        
        // Contar registros totales de esta sucursal
        $queryCountTotal = "SELECT COUNT(*) as total FROM $tableName";
        $resultCountTotal = mysqli_query($conexion, $queryCountTotal);
        $rowCountTotal = mysqli_fetch_assoc($resultCountTotal);
        $recordsTotal += (int)$rowCountTotal['total'];
        
        // Contar registros filtrados de esta sucursal
        $queryCountFiltered = "SELECT COUNT(*) as total FROM $tableName $whereClause";
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
        
        // Consulta principal para obtener movimientos
        $query = "SELECT id_movimientos, fecha_apunte, hora_de_apunte, grupos, concepto, salida, entrada, usuario 
                  FROM $tableName 
                  $whereClause";
        
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
                        $row['sucursal_nombre'] = $nombreSucursal;
                        $row['id_sucursal'] = $idSucursal;
                        $allMovimientos[] = $row;
                    }
                }
                mysqli_stmt_close($stmt);
            }
        } else {
            $result = mysqli_query($conexion, $query);
            if ($result) {
                while ($row = mysqli_fetch_assoc($result)) {
                    $row['sucursal_nombre'] = $nombreSucursal;
                    $row['id_sucursal'] = $idSucursal;
                    $allMovimientos[] = $row;
                }
            }
        }
    }
    
    // Ordenar todos los movimientos combinados
    usort($allMovimientos, function($a, $b) use ($orderBy, $orderDirection) {
        if ($orderBy === 'fecha_apunte') {
            $tsA = strtotime(trim(($a['fecha_apunte'] ?? '') . ' ' . ($a['hora_de_apunte'] ?? '00:00:00')));
            $tsB = strtotime(trim(($b['fecha_apunte'] ?? '') . ' ' . ($b['hora_de_apunte'] ?? '00:00:00')));
            if ($tsA === false) {
                $tsA = 0;
            }
            if ($tsB === false) {
                $tsB = 0;
            }
            if ($tsA === $tsB) {
                $idA = (int) ($a['id_movimientos'] ?? 0);
                $idB = (int) ($b['id_movimientos'] ?? 0);
                return $orderDirection === 'ASC' ? ($idA <=> $idB) : ($idB <=> $idA);
            }
            return $orderDirection === 'ASC' ? ($tsA <=> $tsB) : ($tsB <=> $tsA);
        }

        $valA = $a[$orderBy] ?? '';
        $valB = $b[$orderBy] ?? '';

        if ($orderDirection === 'ASC') {
            return $valA <=> $valB;
        }
        return $valB <=> $valA;
    });
    
    // Aplicar paginación
    $movimientosPaginados = array_slice($allMovimientos, $start, $length);
    
    // Preparar datos para DataTables
    $data = [];
    foreach ($movimientosPaginados as $row) {
        // Obtener usuario si es un ID numérico
        $nombreUsuario = $row['usuario'];
        if (is_numeric($row['usuario'])) {
            $queryUsuario = "SELECT usuario FROM usuarios WHERE id_usuario = ?";
            $stmtUsuario = mysqli_prepare($conexion, $queryUsuario);
            if ($stmtUsuario) {
                mysqli_stmt_bind_param($stmtUsuario, 'i', $row['usuario']);
                mysqli_stmt_execute($stmtUsuario);
                $resultUsuario = mysqli_stmt_get_result($stmtUsuario);
                if ($resultUsuario && $rowUsuario = mysqli_fetch_assoc($resultUsuario)) {
                    $nombreUsuario = $rowUsuario['usuario'];
                }
                mysqli_stmt_close($stmtUsuario);
            }
        }
        
        $data[] = [
            $row['id_movimientos'],
            date('d/m/Y', strtotime($row['fecha_apunte'])) . ' ' . $row['hora_de_apunte'],
            htmlspecialchars($row['sucursal_nombre']),
            htmlspecialchars($row['grupos']),
            htmlspecialchars($row['concepto']),
            $row['salida'],
            $row['entrada'],
            htmlspecialchars($nombreUsuario),
            (int) ($row['id_sucursal'] ?? 0),
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
