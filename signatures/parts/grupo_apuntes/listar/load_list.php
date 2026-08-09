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
$length = isset($_POST['length']) ? intval($_POST['length']) : 25;
$search = isset($_POST['search']['value']) ? trim($_POST['search']['value']) : '';

try {
    $conexion = conectar_bd();
    
    if (!$conexion) {
        throw new Exception('Error de conexión a la base de datos');
    }
    
    // Construir la consulta base
    $query_base = "FROM grupos_movimientos WHERE 1=1";
    
    $params = [];
    $types = '';
    
    // Búsqueda global
    if (!empty($search)) {
        $query_base .= " AND (nombre_grupo LIKE ? OR tipo_grupo LIKE ?)";
        $search_param = "%$search%";
        $params[] = $search_param;
        $params[] = $search_param;
        $types .= 'ss';
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
                        id_grupo,
                        nombre_grupo,
                        tipo_grupo
                    " . $query_base . " 
                    ORDER BY nombre_grupo ASC 
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
            $row['id_grupo'], // Índice 0 - ID
            htmlspecialchars($row['nombre_grupo']), // Índice 1 - Nombre del grupo
            htmlspecialchars($row['tipo_grupo']), // Índice 2 - Tipo de grupo
            [
                'id' => $row['id_grupo'] // Índice 3 - Objeto con ID para acciones
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
    if (isset($stmt_main)) {
        mysqli_stmt_close($stmt_main);
    }
    if (isset($conexion)) {
        mysqli_close($conexion);
    }
    
    http_response_code(500);
    echo json_encode([
        'error' => $e->getMessage(),
        'draw' => $draw,
        'recordsTotal' => 0,
        'recordsFiltered' => 0,
        'data' => []
    ]);
}
?>
