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
    0 => 'ad.id_autorizacion',
    1 => 'ad.codigo_autorizacion',
    2 => 's.nombre_sucursal',
    3 => 'ad.estado_autorizacion',
    4 => 'ad.fecha_autorizacion',
    5 => 'u.nombre_usuario',
    6 => 'ad.sku_articulo_devolucion',
    7 => 'ad.venta_id',
    8 => 'ad.rel_id_devolucion'
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
    // Nota: usuario_autorizacion es varchar(64), contiene el id_usuario
    $query_base = "FROM autorizaciones_devoluciones ad
                    LEFT JOIN sucursal s ON ad.sucursal_autorizacion = s.id_sucursal
                    LEFT JOIN usuarios u ON CAST(ad.usuario_autorizacion AS UNSIGNED) = u.id_usuario
                    WHERE 1=1";
    
    $params = [];
    $types = '';
    
    // Aplicar filtros
    if (!empty($filtro_sucursal)) {
        $query_base .= " AND s.nombre_sucursal = ?";
        $params[] = $filtro_sucursal;
        $types .= 's';
    }
    
    if (!empty($filtro_estado)) {
        $query_base .= " AND ad.estado_autorizacion = ?";
        $params[] = $filtro_estado;
        $types .= 's';
    }
    
    // Búsqueda global
    if (!empty($search)) {
        $query_base .= " AND (
            ad.id_autorizacion LIKE ? OR
            s.nombre_sucursal LIKE ? OR
            ad.estado_autorizacion LIKE ? OR
            u.nombre_usuario LIKE ? OR
            ad.sku_articulo_devolucion LIKE ? OR
            ad.venta_id LIKE ? OR
            ad.rel_id_devolucion LIKE ? OR
            ad.codigo_autorizacion LIKE ?
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
        $types .= 'ssssssss';
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
                        ad.id_autorizacion,
                        ad.codigo_autorizacion,
                        s.nombre_sucursal,
                        ad.sucursal_autorizacion,
                        ad.estado_autorizacion,
                        ad.fecha_autorizacion,
                        u.nombre_usuario,
                        ad.sku_articulo_devolucion,
                        ad.venta_id,
                        ad.rel_id_devolucion
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
            $row['id_autorizacion'],
            htmlspecialchars($row['codigo_autorizacion'] ?? '', ENT_QUOTES, 'UTF-8'),
            htmlspecialchars($row['nombre_sucursal'] ?? 'Sin sucursal'),
            $row['estado_autorizacion'],
            $row['fecha_autorizacion'],
            htmlspecialchars($row['nombre_usuario'] ?? 'Sin usuario'),
            $row['sku_articulo_devolucion'],
            $row['venta_id'],
            $row['rel_id_devolucion']
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
    error_log("Error en load_list autorizaciones_devoluciones: " . $e->getMessage());
    
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
