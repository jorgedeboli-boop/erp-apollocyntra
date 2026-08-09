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
    0 => 'id_proveedor',
    1 => 'nombre_proveedor',
    2 => 'direccion_proveedor',
    3 => 'poblacion_proveedor',
    4 => 'provincia_proveedor',
    5 => 'telefono_proveedor',
    6 => 'cif_proveedor',
    7 => 'fundicion'
];

// Validar columna de ordenamiento
if (!isset($columnMap[$orderColumn])) {
    $orderColumn = 1; // Por defecto ordenar por nombre
}

$orderBy = $columnMap[$orderColumn];
$orderDirection = strtoupper($orderDir) === 'ASC' ? 'ASC' : 'DESC';

// Filtros adicionales
$filtro_proveedor = isset($_POST['filtro_proveedor']) ? trim($_POST['filtro_proveedor']) : '';
$filtro_fundicion = isset($_POST['filtro_fundicion']) ? trim($_POST['filtro_fundicion']) : '';
$filtro_pago = isset($_POST['filtro_pago']) ? trim($_POST['filtro_pago']) : '';

try {
    $conexion = conectar_bd();
    
    if (!$conexion) {
        throw new Exception('Error de conexión a la base de datos');
    }
    
    // Construir la consulta base
    $query_base = "FROM proveedores WHERE 1=1";
    
    $params = [];
    $types = '';
    
    // Aplicar filtros
    if (!empty($filtro_proveedor)) {
        $query_base .= " AND nombre_proveedor LIKE ?";
        $params[] = "%$filtro_proveedor%";
        $types .= 's';
    }
    
    if (!empty($filtro_fundicion)) {
        $query_base .= " AND fundicion = ?";
        $params[] = $filtro_fundicion;
        $types .= 's';
    }
    
    if (!empty($filtro_pago)) {
        $query_base .= " AND forma_pago_proveedor = ?";
        $params[] = $filtro_pago;
        $types .= 's';
    }
    
    // Búsqueda global
    if (!empty($search)) {
        $query_base .= " AND (nombre_proveedor LIKE ? OR direccion_proveedor LIKE ? OR poblacion_proveedor LIKE ? OR provincia_proveedor LIKE ? OR telefono_proveedor LIKE ? OR cif_proveedor LIKE ?)";
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
                        id_proveedor,
                        nombre_proveedor,
                        direccion_proveedor,
                        poblacion_proveedor,
                        provincia_proveedor,
                        telefono_proveedor,
                        cif_proveedor,
                        fundicion
                    " . $query_base . " 
                    ORDER BY $orderBy $orderDirection 
                    LIMIT ?, ?";
    
    // Agregar parámetros de paginación
    $params[] = $start;
    $params[] = $length;
    $types .= 'ii';
    
    $stmt_main = mysqli_prepare($conexion, $query_main);
    
    if (!empty($params)) {
        mysqli_stmt_bind_param($stmt_main, $types, ...$params);
    }
    
    mysqli_stmt_execute($stmt_main);
    $result_main = mysqli_stmt_get_result($stmt_main);
    
    $data = [];
    while ($row = mysqli_fetch_assoc($result_main)) {
        
        $es_fundicion = (isset($row['fundicion']) && $row['fundicion'] === 'true');
        $fundicion_txt = $es_fundicion ? 'Sí' : 'No';

        $data[] = [
            $row['id_proveedor'],
            $row['nombre_proveedor'],
            $row['direccion_proveedor'],
            $row['poblacion_proveedor'],
            $row['provincia_proveedor'],
            $row['telefono_proveedor'],
            $row['cif_proveedor'],
            $fundicion_txt
        ];
    }
    
    mysqli_stmt_close($stmt_main);
    
    // Respuesta para DataTables
    $response = [
        'draw' => $draw,
        'recordsTotal' => $total_records,
        'recordsFiltered' => $total_records,
        'data' => $data
    ];
    
    echo json_encode($response);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'error' => $e->getMessage(),
        'draw' => $draw,
        'recordsTotal' => 0,
        'recordsFiltered' => 0,
        'data' => []
    ]);
}

if (isset($conexion)) {
    mysqli_close($conexion);
}
?>
