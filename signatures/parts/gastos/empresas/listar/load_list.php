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
$filtro_empresa = isset($_POST['filtro_empresa']) ? trim($_POST['filtro_empresa']) : '';
$filtro_estado = isset($_POST['filtro_estado']) ? trim($_POST['filtro_estado']) : '';
$filtro_caja = isset($_POST['filtro_caja']) ? trim($_POST['filtro_caja']) : '';

try {
    $conexion = conectar_bd();
    
    if (!$conexion) {
        throw new Exception('Error de conexión a la base de datos');
    }
    
    // Construir la consulta base
    $query_base = "FROM empresas WHERE 1=1";
    
    $params = [];
    $types = '';
    
    // Aplicar filtros
    if (!empty($filtro_empresa)) {
        $query_base .= " AND nombre_empresa LIKE ?";
        $params[] = "%$filtro_empresa%";
        $types .= 's';
    }
    
    if (!empty($filtro_estado)) {
        $query_base .= " AND estado_empresa = ?";
        $params[] = $filtro_estado;
        $types .= 's';
    }
    
    if (!empty($filtro_caja)) {
        $query_base .= " AND caja_cerrada = ?";
        $params[] = $filtro_caja;
        $types .= 's';
    }
    
    // Búsqueda global
    if (!empty($search)) {
        $query_base .= " AND (nombre_empresa LIKE ? OR direccion_empresa LIKE ? OR poblacion_empresa LIKE ? OR provincia_empresa LIKE ? OR telefono_empresa LIKE ? OR cif_empresa LIKE ?)";
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
                        id_empresa,
                        nombre_empresa,
                        direccion_empresa,
                        poblacion_empresa,
                        provincia_empresa,
                        telefono_empresa,
                        cif_empresa
                    " . $query_base . " 
                    ORDER BY nombre_empresa ASC 
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
        $acciones .= '<a href="empresa.php?id=' . $row['id_empresa'] . '" class="btn btn-icon btn-text-secondary rounded-pill me-1" title="Ver empresa"><i class="icon-base ri ri-eye-line icon-md"></i></a>';
        $acciones .= '<a href="editar_empresa.php?id=' . $row['id_empresa'] . '" class="btn btn-icon btn-text-secondary rounded-pill me-1" title="Editar empresa"><i class="icon-base ri ri-pencil-line me-2"></i></a>';
        $acciones .= '</div>';
        
        $data[] = [
            $row['id_empresa'],
            $row['nombre_empresa'],
            $row['direccion_empresa'],
            $row['poblacion_empresa'],
            $row['provincia_empresa'],
            $row['telefono_empresa'],
            $row['cif_empresa'],
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
