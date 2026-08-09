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
$orderColumn = isset($_POST['order'][0]['column']) ? (int)$_POST['order'][0]['column'] : 0;
$orderDir = isset($_POST['order'][0]['dir']) ? $_POST['order'][0]['dir'] : 'desc';

// Mapeo de columnas para ordenamiento
$columnMap = [
    0 => 'sms.id_sms',
    1 => 's.nombre_sucursal',
    2 => 'sms.codigo_sms',
    3 => 'sms.estado_sms',
    4 => 'sms.estado_codigo',
    5 => 'sms.fecha_sms',
    6 => 'u.nombre_usuario',
    7 => 'c.nombre',
    8 => 'sms.rel_item_sms',
    9 => 'sms.movil_sms',
    10 => 'sms.type_item_sms',
    11 => 'sms.mensaje_sms',
    12 => 'sms.autorizado_central'
];

// Validar columna de ordenamiento
if (!isset($columnMap[$orderColumn])) {
    $orderColumn = 0;
}

$orderBy = $columnMap[$orderColumn];
$orderDirection = strtoupper($orderDir) === 'ASC' ? 'ASC' : 'DESC';

// Filtros adicionales
$filtro_sucursal = isset($_POST['filtro_sucursal']) ? trim($_POST['filtro_sucursal']) : '';
$filtro_estado_sms = isset($_POST['filtro_estado_sms']) ? trim($_POST['filtro_estado_sms']) : '';
$filtro_estado_autorizado = isset($_POST['filtro_estado_autorizado']) ? trim($_POST['filtro_estado_autorizado']) : '';

try {
    $conexion = conectar_bd();
    
    if (!$conexion) {
        throw new Exception('Error de conexión a la base de datos');
    }
    
    // Construir la consulta base
    $query_base = "FROM sms_send sms
                    LEFT JOIN sucursal s ON sms.surusal_sms = s.id_sucursal
                    LEFT JOIN usuarios u ON sms.usuario_sms = u.id_usuario
                    LEFT JOIN clientes c ON sms.cliente_sms = c.id_cliente
                    WHERE NOT sms.type_item_sms = 'vencimiento'";
    
    $params = [];
    $types = '';
    
    // Aplicar filtros
    if (!empty($filtro_sucursal)) {
        $query_base .= " AND s.nombre_sucursal = ?";
        $params[] = $filtro_sucursal;
        $types .= 's';
    }
    
    if (!empty($filtro_estado_sms)) {
        $query_base .= " AND sms.estado_sms = ?";
        $params[] = $filtro_estado_sms;
        $types .= 's';
    }
    
    if (!empty($filtro_estado_autorizado)) {
        $query_base .= " AND sms.estado_autorizado = ?";
        $params[] = $filtro_estado_autorizado;
        $types .= 's';
    }
    
    // Búsqueda global
    if (!empty($search)) {
        $query_base .= " AND (
            sms.id_sms LIKE ? OR
            s.nombre_sucursal LIKE ? OR
            sms.codigo_sms LIKE ? OR
            u.nombre_usuario LIKE ? OR
            c.nombre LIKE ? OR
            c.apellido LIKE ? OR
            sms.rel_item_sms LIKE ? OR
            sms.movil_sms LIKE ? OR
            sms.type_item_sms LIKE ? OR
            sms.mensaje_sms LIKE ?
        )";
        $search_param = "%$search%";
        $params[] = $search_param;
        $params[] = $search_param;
        $params[] = $search_param;
        $params[] = $search_param;
        $params[] = $search_param;
        $params[] = $search_param;
        $params[] = $search_param;
        $params[] = $search_param;
        $params[] = $search_param;
        $params[] = $search_param;
        $types .= 'ssssssssss';
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
                        sms.id_sms,
                        s.nombre_sucursal,
                        sms.surusal_sms,
                        sms.codigo_sms,
                        sms.estado_sms,
                        sms.estado_codigo,
                        sms.fecha_sms,
                        u.usuario as nombre_usuario,
                        CONCAT(c.nombre, ' ', c.apellido) as nombre_cliente,
                        sms.rel_item_sms,
                        sms.movil_sms,
                        sms.type_item_sms,
                        sms.mensaje_sms,
                        sms.autorizado_central,
                        sms.estado_autorizado
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
        $data[] = [
            $row['id_sms'],
            htmlspecialchars($row['nombre_sucursal'] ?? 'Sin sucursal'),
            $row['codigo_sms'],
            $row['estado_sms'],
            $row['estado_codigo'],
            $row['fecha_sms'],
            htmlspecialchars($row['nombre_usuario'] ?? 'Sin usuario'),
            htmlspecialchars($row['nombre_cliente'] ?? 'Sin cliente'),
            $row['rel_item_sms'],
            htmlspecialchars($row['movil_sms']),
            htmlspecialchars($row['type_item_sms']),
            htmlspecialchars($row['mensaje_sms']),
            [
                'id_sms' => $row['id_sms'],
                'rel_item_sms' => $row['rel_item_sms'],
                'id_sucursal' => $row['surusal_sms'],
                'autorizado_central' => $row['autorizado_central'],
                'estado_autorizado' => $row['estado_autorizado']
            ]
        ];
    }
    
    mysqli_stmt_close($stmt_main);
    mysqli_close($conexion);

    // DENTRO DE $data, obtener el identificador de lotes_joyeria
    foreach ($data as $key => $value) {
        $rel_item_sms = $value[8]; // rel_item_sms está en el índice 8
        $id_sucursal = $value[12]['id_sucursal']; // id_sucursal está en el array asociativo del índice 12
        $data[$key][12]['identificador'] = obtenerIdLotesJoyeria($rel_item_sms, $id_sucursal);
    }
    
    // Respuesta para DataTables
    echo json_encode([
        'draw' => $draw,
        'recordsTotal' => $total_records,
        'recordsFiltered' => $total_records,
        'data' => $data
    ]);
    
} catch (Exception $e) {
    error_log("Error en load_list sms_send: " . $e->getMessage());
    
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

