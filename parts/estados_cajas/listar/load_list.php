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

// Parámetros de ordenamiento
$orderColumn = isset($_POST['order'][0]['column']) ? (int)$_POST['order'][0]['column'] : 1; // Por defecto ordenar por nombre
$orderDir = isset($_POST['order'][0]['dir']) ? $_POST['order'][0]['dir'] : 'asc';

// Mapeo de columnas para ordenamiento
$columnMap = [
    0 => 's.id_sucursal',
    1 => 's.nombre_sucursal',
    2 => 's.caja_cerrada',
    3 => 's.new_sitema_caja'
];

// Validar columna de ordenamiento
if (!isset($columnMap[$orderColumn])) {
    $orderColumn = 1; // Por defecto ordenar por nombre
}

$orderBy = $columnMap[$orderColumn];
$orderDirection = strtoupper($orderDir) === 'ASC' ? 'ASC' : 'DESC';

// Filtros adicionales
$filtro_estado = isset($_POST['filtro_estado']) ? trim($_POST['filtro_estado']) : '';
$filtro_sistema = isset($_POST['filtro_sistema']) ? trim($_POST['filtro_sistema']) : '';

try {
    $conexion = conectar_bd();
    
    if (!$conexion) {
        throw new Exception('Error de conexión a la base de datos');
    }
    
    // Construir la consulta base - Solo sucursales habilitadas
    $query_base = "FROM sucursal s WHERE s.estado_tienda = 'habilitada'";
    
    $params = [];
    $types = '';
    
    // Aplicar filtros
    if (!empty($filtro_estado)) {
        $query_base .= " AND s.caja_cerrada = ?";
        $params[] = $filtro_estado;
        $types .= 's';
    }
    
    if (!empty($filtro_sistema)) {
        $query_base .= " AND s.new_sitema_caja = ?";
        $params[] = $filtro_sistema;
        $types .= 's';
    }
    
    // Búsqueda global (por id_sucursal si es numérico, si no por nombre)
    if (!empty($search)) {
        if (ctype_digit($search)) {
            $query_base .= " AND s.id_sucursal = ?";
            $params[] = (int) $search;
            $types .= 'i';
        } else {
            $query_base .= " AND s.nombre_sucursal LIKE ?";
            $search_param = "%$search%";
            $params[] = $search_param;
            $types .= 's';
        }
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
                        s.id_sucursal,
                        s.nombre_sucursal,
                        s.caja_cerrada,
                        s.new_sitema_caja
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
        $idSucursal = $row['id_sucursal'];
        
        // Buscar datos de apertura y cierre en movimientos_de_caja_X del día actual
        $tableName = "movimientos_de_caja_" . $idSucursal;
        
        // Inicializar valores
        $apertura = null;
        $cierre = null;
        $saldo = null;
        
        // Verificar si la tabla existe
        $tableCheck = mysqli_query($conexion, "SHOW TABLES LIKE '$tableName'");
        if (mysqli_num_rows($tableCheck) > 0) {
            // Buscar APERTURA (grupos = 'CAJA INICIO' del día actual)
            // y verificar que no tenga un cierre posterior - ordenadas por ID descendente
            $queryApertura = "SELECT id_movimientos, fecha_apunte, hora_de_apunte, entrada, grupos
                             FROM $tableName 
                             WHERE TRIM(grupos) = 'CAJA INICIO' 
                             AND fecha_apunte = CURDATE() 
                             ORDER BY id_movimientos DESC";
            $resultApertura = mysqli_query($conexion, $queryApertura);
            
            $idAperturaActiva = null; // Guardar el ID de la apertura activa
            
            if ($resultApertura && mysqli_num_rows($resultApertura) > 0) {
                // Buscar la apertura que no tiene cierre posterior
                while ($rowApertura = mysqli_fetch_assoc($resultApertura)) {
                    $idApertura = $rowApertura['id_movimientos'];
                    
                    // Verificar si hay un cierre después de esta apertura (por ID mayor)
                    $queryCierrePost = "SELECT COUNT(*) as tiene_cierre 
                                       FROM $tableName 
                                       WHERE cierre_caja = 'true' 
                                       AND DATE(fecha_apunte) = CURDATE() 
                                       AND id_movimientos > ?";
                    $stmtCierrePost = mysqli_prepare($conexion, $queryCierrePost);
                    mysqli_stmt_bind_param($stmtCierrePost, 'i', $idApertura);
                    mysqli_stmt_execute($stmtCierrePost);
                    $resultCierrePost = mysqli_stmt_get_result($stmtCierrePost);
                    $rowCierrePost = mysqli_fetch_assoc($resultCierrePost);
                    mysqli_stmt_close($stmtCierrePost);
                    
                    // Si no hay cierre posterior, esta es la apertura activa
                    if ($rowCierrePost['tiene_cierre'] == 0) {
                        $apertura = [
                            'fecha' => $rowApertura['fecha_apunte'],
                            'hora' => $rowApertura['hora_de_apunte'],
                            'importe' => floatval($rowApertura['entrada'])
                        ];
                        $idAperturaActiva = $idApertura; // Guardar el ID de la apertura activa
                        
                        // Si la caja está abierta, calcular el saldo
                        if ($row['caja_cerrada'] === 'false' || $row['caja_cerrada'] === false) {
                            $querySaldo = "SELECT 
                                            COALESCE(SUM(entrada), 0) as total_entradas,
                                            COALESCE(SUM(salida), 0) as total_salidas
                                          FROM $tableName 
                                          WHERE fecha_apunte = CURDATE() 
                                          AND id_movimientos >= ?";
                            $stmtSaldo = mysqli_prepare($conexion, $querySaldo);
                            mysqli_stmt_bind_param($stmtSaldo, 'i', $idApertura);
                            mysqli_stmt_execute($stmtSaldo);
                            $resultSaldo = mysqli_stmt_get_result($stmtSaldo);
                            $rowSaldo = mysqli_fetch_assoc($resultSaldo);
                            mysqli_stmt_close($stmtSaldo);
                            
                            $saldo = floatval($rowSaldo['total_entradas']) - floatval($rowSaldo['total_salidas']);
                        }
                        
                        break; // Ya encontramos la apertura activa
                    }
                }
            } else {
                // DEBUG: Si no hay resultados, ver qué hay en la tabla
                $queryDebugTotal = "SELECT COUNT(*) as total FROM $tableName WHERE TRIM(grupos) = 'CAJA INICIO'";
                $resultDebugTotal = mysqli_query($conexion, $queryDebugTotal);
                $rowDebugTotal = mysqli_fetch_assoc($resultDebugTotal);
                
                $queryDebugHoy = "SELECT COUNT(*) as total_hoy, MIN(fecha_apunte) as primera_fecha, MAX(fecha_apunte) as ultima_fecha 
                                 FROM $tableName 
                                 WHERE TRIM(grupos) = 'CAJA INICIO'";
                $resultDebugHoy = mysqli_query($conexion, $queryDebugHoy);
                $rowDebugHoy = mysqli_fetch_assoc($resultDebugHoy);
                
                $debugInfo['total_caja_inicio'] = $rowDebugTotal['total'];
                $debugInfo['total_hoy'] = $rowDebugHoy['total_hoy'];
                $debugInfo['primera_fecha'] = $rowDebugHoy['primera_fecha'];
                $debugInfo['ultima_fecha'] = $rowDebugHoy['ultima_fecha'];
            }
            
            // Buscar CIERRE
            // Si la caja está cerrada, mostrar el último cierre histórico
            // Si la caja está abierta, mostrar el cierre posterior a la apertura activa del día actual
            if ($row['caja_cerrada'] === 'true' || $row['caja_cerrada'] === true) {
                // Caja cerrada: buscar el último cierre histórico
                $queryCierre = "SELECT fecha_apunte, hora_de_apunte, salida 
                               FROM $tableName 
                               WHERE cierre_caja = 'true' 
                               ORDER BY id_movimientos DESC 
                               LIMIT 1";
                $resultCierre = mysqli_query($conexion, $queryCierre);
                
                if ($resultCierre && mysqli_num_rows($resultCierre) > 0) {
                    $rowCierre = mysqli_fetch_assoc($resultCierre);
                    $cierre = [
                        'fecha' => $rowCierre['fecha_apunte'],
                        'hora' => $rowCierre['hora_de_apunte'],
                        'importe' => floatval($rowCierre['salida'])
                    ];
                }
            } else {
                // Caja abierta: buscar CIERRE solo si es POSTERIOR a la apertura activa (por ID mayor)
                if ($idAperturaActiva !== null) {
                    $queryCierre = "SELECT fecha_apunte, hora_de_apunte, salida 
                                   FROM $tableName 
                                   WHERE cierre_caja = 'true' 
                                   AND fecha_apunte = CURDATE() 
                                   AND id_movimientos > ?
                                   ORDER BY id_movimientos DESC 
                                   LIMIT 1";
                    $stmtCierre = mysqli_prepare($conexion, $queryCierre);
                    mysqli_stmt_bind_param($stmtCierre, 'i', $idAperturaActiva);
                    mysqli_stmt_execute($stmtCierre);
                    $resultCierre = mysqli_stmt_get_result($stmtCierre);
                    
                    if ($resultCierre && mysqli_num_rows($resultCierre) > 0) {
                        $rowCierre = mysqli_fetch_assoc($resultCierre);
                        $cierre = [
                            'fecha' => $rowCierre['fecha_apunte'],
                            'hora' => $rowCierre['hora_de_apunte'],
                            'importe' => floatval($rowCierre['salida'])
                        ];
                    }
                    mysqli_stmt_close($stmtCierre);
                }
            }
        }
        
        // Debug info para esta sucursal
        $debugInfo = [
            'sucursal_id' => $row['id_sucursal'],
            'tabla_existe' => mysqli_num_rows($tableCheck) > 0,
            'apertura_encontrada' => $apertura !== null,
            'cierre_encontrado' => $cierre !== null
        ];
        
        // Formatear datos para la tabla
        $data[] = [
            $row['id_sucursal'], // Índice 0 - ID
            htmlspecialchars($row['nombre_sucursal']), // Índice 1 - Nombre sucursal
            $row['new_sitema_caja'], // Índice 2 - Sistema caja
            $row['caja_cerrada'], // Índice 3 - Caja cerrada
            $saldo, // Índice 4 - Saldo (solo si caja abierta)
            $apertura, // Índice 5 - Apertura (fecha y hora)
            $apertura ? $apertura['importe'] : null, // Índice 6 - Importe apertura
            $cierre, // Índice 7 - Cierre (fecha y hora)
            $cierre ? $cierre['importe'] : null, // Índice 8 - Importe cierre
            [
                'id' => $row['id_sucursal'], // Índice 9 - Objeto con ID para acciones
                'nombre' => htmlspecialchars($row['nombre_sucursal'])
            ],
            $debugInfo // Índice 10 - Debug info
        ];
    }
    
    mysqli_stmt_close($stmt_main);
    mysqli_close($conexion);
    
    // Respuesta para DataTables
    echo json_encode([
        'draw' => $draw,
        'recordsTotal' => $total_records,
        'recordsFiltered' => $total_records,
        'data' => $data,
        'debug_fecha_hoy' => date('Y-m-d')
    ]);
    
} catch (Exception $e) {
    error_log("Error en load_list estados_cajas: " . $e->getMessage());
    
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
