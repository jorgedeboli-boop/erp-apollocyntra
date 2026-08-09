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

// Filtros adicionales
$filtro_codigo = isset($_POST['filtro_codigo']) ? trim($_POST['filtro_codigo']) : '';
$filtro_pais = isset($_POST['filtro_pais']) ? trim($_POST['filtro_pais']) : '';
$filtro_estado = isset($_POST['filtro_estado']) ? trim($_POST['filtro_estado']) : '';

try {
    $conexion = conectar_bd();
    
    if (!$conexion) {
        throw new Exception('Error de conexión a la base de datos');
    }
    
    // Construir la consulta base
    $query_base = "FROM Languages l LEFT JOIN countrys c ON l.rel_id_country = c.id_country WHERE 1=1";
    
    $params = [];
    $types = '';
    
    // Aplicar filtros
    if (!empty($filtro_codigo)) {
        $query_base .= " AND l.cod_LP LIKE ?";
        $params[] = "%$filtro_codigo%";
        $types .= 's';
    }
    
    if (!empty($filtro_pais)) {
        $query_base .= " AND c.name_spanish LIKE ?";
        $params[] = "%$filtro_pais%";
        $types .= 's';
    }
    
    if (!empty($filtro_estado)) {
        $query_base .= " AND l.stateLang = ?";
        $params[] = $filtro_estado;
        $types .= 's';
    }
    
    // Búsqueda global
    if (!empty($search)) {
        $query_base .= " AND (l.cod_LP LIKE ? OR l.description LIKE ? OR c.name_spanish LIKE ?)";
        $search_param = "%$search%";
        $params[] = $search_param;
        $params[] = $search_param;
        $params[] = $search_param;
        $types .= 'sss';
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
                        l.id_lang,
                        l.cod_LP,
                        l.description,
                        c.name_spanish as pais,
                        l.stateLang
                    " . $query_base . " 
                    ORDER BY l.stateLang DESC, l.cod_LP ASC 
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
        // Crear enlaces de acciones
        $acciones = '<div class="d-flex gap-2">';
        $acciones .= '<a href="Language.php?id=' . $row['id_lang'] . '" class="btn btn-icon btn-text-secondary rounded-pill me-1" title="Ver Language"><i class="icon-base ri ri-eye-line icon-md"></i></a>';
        $acciones .= '<a href="editar_Language.php?id=' . $row['id_lang'] . '" class="btn btn-icon btn-text-secondary rounded-pill me-1" title="Editar Language"><i class="icon-base ri ri-pencil-line me-2"></i></a>';
        $acciones .= '</div>';
        
        $data[] = [
            $row['id_lang'],
            $row['cod_LP'],
            $row['description'],
            $row['pais'] ?? 'Sin país',
            $row['stateLang'],
            $acciones
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
