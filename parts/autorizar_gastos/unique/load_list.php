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

require_once __DIR__ . '/../../universal/autorizaciones_comprobante_helper.php';

// Parámetros de ordenamiento
$orderColumn = isset($_POST['order'][0]['column']) ? (int)$_POST['order'][0]['column'] : 0;
$orderDir = isset($_POST['order'][0]['dir']) ? $_POST['order'][0]['dir'] : 'desc';

// Mapeo de columnas para ordenamiento
$columnMap = [
    0 => 'ag.id',
    1 => 's.nombre_sucursal',
    2 => 'ag.estado',
    3 => 'ag.codigo',
    4 => 'ag.fecha',
    5 => 'u.nombre_usuario',
    6 => 'ag.grupo',
    7 => 'ag.concepto',
    8 => 'ag.salida',
    9 => 'ag.fecha_uso'
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
    // Nota: usuario es varchar(64), contiene el id_usuario
    $query_base = "FROM autorizaciones_gastos ag
                    LEFT JOIN sucursal s ON ag.sucursal = s.id_sucursal
                    LEFT JOIN usuarios u ON CAST(ag.usuario AS UNSIGNED) = u.id_usuario
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
        $query_base .= " AND ag.estado = ?";
        $params[] = $filtro_estado;
        $types .= 's';
    }
    
    // Búsqueda global
    if (!empty($search)) {
        $query_base .= " AND (
            ag.id LIKE ? OR
            s.nombre_sucursal LIKE ? OR
            ag.estado LIKE ? OR
            u.nombre_usuario LIKE ? OR
            ag.grupo LIKE ? OR
            ag.concepto LIKE ? OR
            ag.codigo LIKE ?
        )";
        $search_param = "%$search%";
        $params[] = $search_param;
        $params[] = $search_param;
        $params[] = $search_param;
        $params[] = $search_param;
        $params[] = $search_param;
        $params[] = $search_param;
        $params[] = $search_param;
        $types .= 'sssssss';
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
                        ag.id,
                        s.nombre_sucursal,
                        ag.sucursal,
                        ag.estado,
                        ag.codigo,
                        ag.fecha,
                        u.nombre_usuario,
                        ag.grupo,
                        ag.concepto,
                        ag.salida,
                        ag.fecha_uso,
                        ag.id_apunte,
                        ag.imagen
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
            $row['id'],
            htmlspecialchars($row['nombre_sucursal'] ?? 'Sin sucursal'),
            $row['estado'],
            htmlspecialchars($row['codigo'] ?? '-'),
            $row['fecha'],
            htmlspecialchars($row['nombre_usuario'] ?? 'Sin usuario'),
            htmlspecialchars($row['grupo'] ?? '-'),
            htmlspecialchars($row['concepto'] ?? '-'),
            $row['salida'] ?? 0,
            $row['fecha_uso'] && $row['fecha_uso'] !== '0000-00-00 00:00:00' ? $row['fecha_uso'] : null,
            renderComprobanteAutorizacion($row['imagen'], $row['id_apunte'], $row['id']),
            [
                'id_apunte' => $row['id_apunte'],
                'imagen' => $row['imagen']
            ]
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
    error_log("Error en load_list autorizaciones_gastos: " . $e->getMessage());
    
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
