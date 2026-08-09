<?php
/**
 * Archivo para cargar las acciones del usuario via AJAX
 * Compatible con PHP 7.0+
 */

// Asegurar que no haya salida antes del JSON
ob_clean();

require_once '../../../include/session.php';
require_once '../../../include/functions.php';

header('Content-Type: application/json');

try {
    // Obtener ID del usuario
    $id_usuario = isset($_POST['id_usuario']) ? (int)$_POST['id_usuario'] : 0;
    
    if (!$id_usuario) {
        http_response_code(400);
        echo json_encode(['error' => 'ID de usuario no válido']);
        exit;
    }
    
    // Conectar BD
    $conexion = conectar_bd();
    
    // Obtener parámetros de DataTables
    $start = isset($_POST['start']) ? (int)$_POST['start'] : 0;
    $length = isset($_POST['length']) ? (int)$_POST['length'] : 25;
    $searchValue = isset($_POST['search']['value']) ? trim($_POST['search']['value']) : '';
    
    // Parámetros de ordenamiento
    $orderColumn = isset($_POST['order'][0]['column']) ? (int)$_POST['order'][0]['column'] : 1; // Por defecto ordenar por fecha
    $orderDir = isset($_POST['order'][0]['dir']) ? $_POST['order'][0]['dir'] : 'desc';
    
    // Validar parámetros
    if ($start < 0) $start = 0;
    if ($length < 1 || $length > 100) $length = 25;
    
    // Mapeo de columnas para ordenamiento
    $columnMap = [
        0 => 'ua.idUserAction',
        1 => 'ua.dateAction',
        2 => 'la.name_action',
        3 => 'ua.logTxt',
        4 => 's.nombre_sucursal',
        5 => 'ua.ipNumberUser',
        6 => 'ua.urlAction',
        7 => 'its.itemnameText'
    ];
    
    // Validar columna de ordenamiento
    if (!isset($columnMap[$orderColumn])) {
        $orderColumn = 1; // Por defecto ordenar por fecha
    }
    
    $orderBy = $columnMap[$orderColumn];
    $orderDirection = strtoupper($orderDir) === 'ASC' ? 'ASC' : 'DESC';
    
    // Construir condiciones de búsqueda
    $whereConditions = ['ua.userId = ?'];
    $searchParams = [$id_usuario];
    $paramTypes = 'i'; // Tipo para id_usuario
    
    if (!empty($searchValue)) {
        $whereConditions[] = "(ua.logTxt LIKE ? OR la.name_action LIKE ? OR s.nombre_sucursal LIKE ? OR ua.ipNumberUser LIKE ?)";
        $searchTerm = "%$searchValue%";
        $searchParams = array_merge($searchParams, [$searchTerm, $searchTerm, $searchTerm, $searchTerm]);
        $paramTypes .= 'ssss'; // 4 strings para LIKE
    }
    
    $whereClause = implode(' AND ', $whereConditions);
    
    // Consulta para contar total de registros
    $queryCount = "
        SELECT COUNT(*) as total
        FROM usersActions ua
        LEFT JOIN listActions la ON ua.relidlistActions = la.id_action
        LEFT JOIN sucursal s ON ua.sucursalIdUserAction = s.id_sucursal
        LEFT JOIN itemsSections its ON ua.relItemAction = its.id_type_Item
        WHERE $whereClause
    ";
    
    $stmtCount = mysqli_prepare($conexion, $queryCount);
    if (!$stmtCount) {
        throw new Exception("Error preparando consulta count: " . mysqli_error($conexion));
    }
    mysqli_stmt_bind_param($stmtCount, $paramTypes, ...$searchParams);
    if (!mysqli_stmt_execute($stmtCount)) {
        throw new Exception("Error ejecutando consulta count: " . mysqli_stmt_error($stmtCount));
    }
    $resultCount = mysqli_stmt_get_result($stmtCount);
    $totalRecords = mysqli_fetch_assoc($resultCount)['total'];
    mysqli_stmt_close($stmtCount);
    
    // Consulta principal con JOINs
    $query = "
        SELECT 
            ua.idUserAction,
            ua.dateAction,
            la.name_action,
            ua.logTxt,
            s.nombre_sucursal,
            ua.ipNumberUser,
            ua.urlAction,
            its.itemnameText
        FROM usersActions ua
        LEFT JOIN listActions la ON ua.relidlistActions = la.id_action
        LEFT JOIN sucursal s ON ua.sucursalIdUserAction = s.id_sucursal
        LEFT JOIN itemsSections its ON ua.relItemAction = its.id_type_Item
        WHERE $whereClause
        ORDER BY $orderBy $orderDirection
        LIMIT ?, ?
    ";
    
    $stmt = mysqli_prepare($conexion, $query);
    if (!$stmt) {
        throw new Exception("Error preparando consulta principal: " . mysqli_error($conexion));
    }
    $searchParams[] = $start;
    $searchParams[] = $length;
    $paramTypesWithLimit = $paramTypes . 'ii'; // Agregar dos enteros para LIMIT
    mysqli_stmt_bind_param($stmt, $paramTypesWithLimit, ...$searchParams);
    if (!mysqli_stmt_execute($stmt)) {
        throw new Exception("Error ejecutando consulta principal: " . mysqli_stmt_error($stmt));
    }
    $result = mysqli_stmt_get_result($stmt);
    
    $data = [];
    while ($row = mysqli_fetch_assoc($result)) {
        $data[] = [
            $row['idUserAction'],
            date('d/m/Y H:i', strtotime($row['dateAction'])),
            $row['name_action'] ?: 'N/A',
            $row['logTxt'] ?: 'Sin descripción',
            $row['nombre_sucursal'] ?: 'Sin sucursal',
            $row['ipNumberUser'] ?: 'N/A',
            $row['urlAction'] ?: 'N/A',
            $row['itemnameText'] ?: 'N/A'
        ];
    }
    
    mysqli_stmt_close($stmt);
    mysqli_close($conexion);
    
    // Respuesta para DataTables
    echo json_encode([
        'draw' => isset($_POST['draw']) ? (int)$_POST['draw'] : 1,
        'recordsTotal' => $totalRecords,
        'recordsFiltered' => $totalRecords,
        'data' => $data
    ]);
    
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
?>
