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
    0 => 's.id_signature',
    1 => 'suc.nombre_sucursal',
    2 => 's.auth_no_signature',
    3 => 'suc.codigo_firmas',
    4 => 's.createDate',
    5 => 'u.nombre_usuario',
    6 => 's.typeItem',
    7 => 's.ItemId',
    8 => 's.recibe_euros'
];

// Validar columna de ordenamiento
if (!isset($columnMap[$orderColumn])) {
    $orderColumn = 0;
}

$orderBy = $columnMap[$orderColumn];
$orderDirection = strtoupper($orderDir) === 'ASC' ? 'ASC' : 'DESC';

// Filtros adicionales
$filtro_sucursal = isset($_POST['filtro_sucursal']) ? trim($_POST['filtro_sucursal']) : '';
$filtro_estado = isset($_POST['filtro_estado']) ? trim($_POST['filtro_estado']) : '';

try {
    $conexion = conectar_bd();
    
    if (!$conexion) {
        throw new Exception('Error de conexión a la base de datos');
    }
    
    // Construir la consulta base
    // Nota: userCreate es int(11), contiene el id_usuario
    $query_base = "FROM Signatures s
                    LEFT JOIN sucursal suc ON s.sucursalSignature = suc.id_sucursal
                    LEFT JOIN usuarios u ON s.userCreate = u.id_usuario
                    WHERE 1=1";
    
    $params = [];
    $types = '';
    
    // Aplicar filtros
    if (!empty($filtro_sucursal)) {
        $query_base .= " AND suc.nombre_sucursal = ?";
        $params[] = $filtro_sucursal;
        $types .= 's';
    }
    
    if (!empty($filtro_estado)) {
        if ($filtro_estado === 'pendiente') {
            $query_base .= " AND s.auth_no_signature = 'false'";
        } else if ($filtro_estado === 'autorizada') {
            $query_base .= " AND s.auth_no_signature = 'true'";
        }
    }
    
    // Búsqueda global
    if (!empty($search)) {
        $query_base .= " AND (
            s.id_signature LIKE ? OR
            suc.nombre_sucursal LIKE ? OR
            suc.codigo_firmas LIKE ? OR
            u.nombre_usuario LIKE ? OR
            s.typeItem LIKE ? OR
            s.ItemId LIKE ?
        )";
        $search_param = "%$search%";
        $params[] = $search_param;
        $params[] = $search_param;
        $params[] = $search_param;
        $params[] = $search_param;
        $params[] = $search_param;
        $params[] = $search_param;
        $types .= 'ssssss';
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
                        s.id_signature,
                        suc.nombre_sucursal,
                        s.sucursalSignature,
                        s.auth_no_signature,
                        suc.codigo_firmas,
                        s.createDate,
                        u.nombre_usuario,
                        s.typeItem,
                        s.ItemId,
                        s.recibe_euros
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
        // Convertir auth_no_signature a estado legible
        $estado = $row['auth_no_signature'] === 'true' ? 'autorizada' : 'pendiente';
        
        $data[] = [
            $row['id_signature'],
            htmlspecialchars($row['nombre_sucursal'] ?? 'Sin sucursal'),
            $estado,
            htmlspecialchars($row['codigo_firmas'] ?? '-'),
            $row['createDate'],
            htmlspecialchars($row['nombre_usuario'] ?? 'Sin usuario'),
            htmlspecialchars($row['typeItem'] ?? '-'),
            $row['ItemId'],
            $row['recibe_euros'] ?? 0
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
    error_log("Error en load_list Signatures: " . $e->getMessage());
    
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
