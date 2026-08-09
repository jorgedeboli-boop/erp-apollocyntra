<?php
/**
 * Server-side processing para DataTable de facturas_fiskaly_cache
 */

// Limpiar cualquier output previo
ob_clean();

require_once '../../../include/session.php';
require_once '../../../include/functions.php';

// Verificar que el usuario esté autenticado
if (!isset($_SESSION['usuario_id'])) {
    http_response_code(401);
    header('Content-Type: application/json');
    echo json_encode(['error' => 'No autorizado']);
    exit;
}

header('Content-Type: application/json');

try {
    // Conexión Fiskaly producción (solo ENVIRONMENT === 'production')
    $conexion = get_mysqli_fiskalyapp_production();
    if (!$conexion) {
        throw new Exception('Error de conexión a la base de datos de Fiskaly (solo disponible en production)');
    }
    
    // Obtener parámetros de DataTables
    $draw = isset($_POST['draw']) ? (int)$_POST['draw'] : 1;
    $start = isset($_POST['start']) ? (int)$_POST['start'] : 0;
    $length = isset($_POST['length']) ? (int)$_POST['length'] : 10;
    $searchValue = isset($_POST['search']['value']) ? trim($_POST['search']['value']) : '';
    
    // Obtener id_sucursal del POST (enviado por el DataTable en la función data) o del GET
    $id_sucursal = 0;
    if (isset($_POST['id']) && !empty($_POST['id'])) {
        $id_sucursal = (int)$_POST['id'];
    } elseif (isset($_GET['id']) && !empty($_GET['id'])) {
        $id_sucursal = (int)$_GET['id'];
    }
    
    if (!$id_sucursal || $id_sucursal <= 0) {
        http_response_code(400);
        echo json_encode([
            'draw' => $draw,
            'recordsTotal' => 0,
            'recordsFiltered' => 0,
            'data' => [],
            'error' => 'ID de sucursal no válido'
        ]);
        exit;
    }
    
    // Construir WHERE clause
    $whereConditions = array();
    $params = array();
    $types = '';
    
    // Filtro por id_sucursal (obligatorio)
    $whereConditions[] = "id_sucursal = ?";
    $params[] = $id_sucursal;
    $types .= 'i';
    
    // Búsqueda global
    if (!empty($searchValue)) {
        $whereConditions[] = "(
            CAST(id_factura AS CHAR) LIKE ? OR
            CAST(numero_factura AS CHAR) LIKE ? OR
            formato_factura LIKE ? OR
            estado_cache LIKE ? OR
            tipo_factura LIKE ? OR
            InvoiceState LIKE ? OR
            SignedInvoiceRegistrationState LIKE ? OR
            SignedInvoiceCancellationState LIKE ?
        )";
        $searchParam = '%' . $searchValue . '%';
        $params[] = $searchParam;
        $params[] = $searchParam;
        $params[] = $searchParam;
        $params[] = $searchParam;
        $params[] = $searchParam;
        $params[] = $searchParam;
        $params[] = $searchParam;
        $params[] = $searchParam;
        $types .= 'ssssssss';
    }
    
    $whereClause = '';
    if (count($whereConditions) > 0) {
        $whereClause = 'WHERE ' . implode(' AND ', $whereConditions);
    }
    
    // Consulta para contar total de registros
    $query_total = "SELECT COUNT(*) as total FROM facturas_fiskaly_cache WHERE id_sucursal = ?";
    $stmt_total = mysqli_prepare($conexion, $query_total);
    if (!$stmt_total) {
        throw new Exception('Error al preparar la consulta de total: ' . mysqli_error($conexion));
    }
    mysqli_stmt_bind_param($stmt_total, 'i', $id_sucursal);
    if (!mysqli_stmt_execute($stmt_total)) {
        mysqli_stmt_close($stmt_total);
        throw new Exception('Error al ejecutar la consulta de total: ' . mysqli_stmt_error($stmt_total));
    }
    $result_total = mysqli_stmt_get_result($stmt_total);
    if (!$result_total) {
        mysqli_stmt_close($stmt_total);
        throw new Exception('Error al obtener el resultado de total: ' . mysqli_error($conexion));
    }
    $row_total = mysqli_fetch_assoc($result_total);
    $recordsTotal = isset($row_total['total']) ? (int)$row_total['total'] : 0;
    mysqli_stmt_close($stmt_total);
    
    // Consulta para contar registros filtrados
    $query_filtered = "SELECT COUNT(*) as total FROM facturas_fiskaly_cache $whereClause";
    
    $stmt_filtered = mysqli_prepare($conexion, $query_filtered);
    if (!$stmt_filtered) {
        throw new Exception('Error al preparar la consulta filtrada: ' . mysqli_error($conexion));
    }
    
    if (!empty($types)) {
        mysqli_stmt_bind_param($stmt_filtered, $types, ...$params);
    } else {
        mysqli_stmt_bind_param($stmt_filtered, 'i', $id_sucursal);
    }
    
    if (!mysqli_stmt_execute($stmt_filtered)) {
        mysqli_stmt_close($stmt_filtered);
        throw new Exception('Error al ejecutar la consulta filtrada: ' . mysqli_stmt_error($stmt_filtered));
    }
    
    $result_filtered = mysqli_stmt_get_result($stmt_filtered);
    if (!$result_filtered) {
        mysqli_stmt_close($stmt_filtered);
        throw new Exception('Error al obtener el resultado filtrado: ' . mysqli_error($conexion));
    }
    
    $row_filtered = mysqli_fetch_assoc($result_filtered);
    $recordsFiltered = isset($row_filtered['total']) ? (int)$row_filtered['total'] : 0;
    mysqli_stmt_close($stmt_filtered);
    
    // Parámetros de ordenamiento
    $orderColumn = isset($_POST['order'][0]['column']) ? (int)$_POST['order'][0]['column'] : 0;
    $orderDir = isset($_POST['order'][0]['dir']) ? $_POST['order'][0]['dir'] : 'desc';
    
    // Mapeo de columnas para ordenamiento
    $columnMap = [
        0 => 'id_factura',
        1 => 'id_sucursal',
        2 => 'numero_factura',
        3 => 'formato_factura',
        4 => 'estado_cache',
        5 => 'cliente_factura',
        6 => 'tipo_factura',
        7 => 'InvoiceState',
        8 => 'SignedInvoiceRegistrationState',
        9 => 'SignedInvoiceCancellationState'
    ];
    
    // Validar y sanitizar ORDER BY para prevenir inyección SQL
    $allowedColumns = [
        'id_factura',
        'id_sucursal',
        'numero_factura',
        'formato_factura',
        'estado_cache',
        'cliente_factura',
        'tipo_factura',
        'InvoiceState',
        'SignedInvoiceRegistrationState',
        'SignedInvoiceCancellationState'
    ];
    
    $orderBy = isset($columnMap[$orderColumn]) ? $columnMap[$orderColumn] : 'id_factura';
    // Validar que la columna esté permitida
    if (!in_array($orderBy, $allowedColumns)) {
        $orderBy = 'id_factura';
    }
    
    $orderDir = strtoupper($orderDir) === 'ASC' ? 'ASC' : 'DESC';
    
    // Consulta principal con paginación
    $query = "SELECT 
                id_factura,
                id_sucursal,
                numero_factura,
                formato_factura,
                estado_cache,
                cliente_factura,
                tipo_factura,
                InvoiceState,
                SignedInvoiceRegistrationState,
                SignedInvoiceCancellationState
              FROM facturas_fiskaly_cache
              $whereClause
              ORDER BY `$orderBy` $orderDir
              LIMIT ? OFFSET ?";
    
    // Agregar parámetros de LIMIT
    $params[] = $length;
    $params[] = $start;
    $types .= 'ii';
    
    $stmt = mysqli_prepare($conexion, $query);
    if (!$stmt) {
        throw new Exception('Error al preparar la consulta: ' . mysqli_error($conexion));
    }
    
    if (!empty($types)) {
        mysqli_stmt_bind_param($stmt, $types, ...$params);
    }
    
    if (!mysqli_stmt_execute($stmt)) {
        mysqli_stmt_close($stmt);
        throw new Exception('Error al ejecutar la consulta: ' . mysqli_stmt_error($stmt));
    }
    
    $result = mysqli_stmt_get_result($stmt);
    if (!$result) {
        mysqli_stmt_close($stmt);
        throw new Exception('Error al obtener el resultado: ' . mysqli_error($conexion));
    }
    
    $data = array();
    while ($row = mysqli_fetch_assoc($result)) {
        $data[] = [
            $row['id_factura'],
            $row['id_sucursal'],
            $row['numero_factura'],
            $row['formato_factura'],
            $row['estado_cache'],
            $row['cliente_factura'],
            $row['tipo_factura'],
            $row['InvoiceState'] !== 'false' ? $row['InvoiceState'] : '',
            $row['SignedInvoiceRegistrationState'] !== 'false' ? $row['SignedInvoiceRegistrationState'] : '',
            $row['SignedInvoiceCancellationState'] !== 'false' ? $row['SignedInvoiceCancellationState'] : ''
        ];
    }
    mysqli_stmt_close($stmt);
    
    echo json_encode([
        'draw' => $draw,
        'recordsTotal' => $recordsTotal,
        'recordsFiltered' => $recordsFiltered,
        'data' => $data
    ]);
    
} catch (Exception $e) {
    // Limpiar cualquier output previo
    if (ob_get_length()) {
        ob_clean();
    }
    
    http_response_code(500);
    header('Content-Type: application/json');
    echo json_encode([
        'draw' => isset($draw) ? $draw : 1,
        'recordsTotal' => 0,
        'recordsFiltered' => 0,
        'data' => [],
        'error' => 'Error al cargar los datos: ' . $e->getMessage()
    ]);
    exit;
} catch (Error $e) {
    // Limpiar cualquier output previo
    if (ob_get_length()) {
        ob_clean();
    }
    
    http_response_code(500);
    header('Content-Type: application/json');
    echo json_encode([
        'draw' => isset($draw) ? $draw : 1,
        'recordsTotal' => 0,
        'recordsFiltered' => 0,
        'data' => [],
        'error' => 'Error fatal: ' . $e->getMessage()
    ]);
    exit;
}
