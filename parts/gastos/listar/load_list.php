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
$search = isset($_POST['search']['value']) ? ltrim(trim($_POST['search']['value']), '#') : '';

// Filtros adicionales
$filtro_empresa = isset($_POST['filtro_empresa']) ? trim($_POST['filtro_empresa']) : '';
$filtro_sucursal = isset($_POST['filtro_sucursal']) ? trim($_POST['filtro_sucursal']) : '';
$filtro_proveedor = isset($_POST['filtro_proveedor']) ? trim($_POST['filtro_proveedor']) : '';
$filtro_estado = isset($_POST['filtro_estado']) ? trim($_POST['filtro_estado']) : '';
$filtro_tipo_gasto = isset($_POST['filtro_tipo_gasto']) ? trim($_POST['filtro_tipo_gasto']) : '';
$filtro_forma_pago = isset($_POST['filtro_forma_pago']) ? trim($_POST['filtro_forma_pago']) : '';
$filtro_fecha_desde = isset($_POST['filtro_fecha_desde']) ? trim($_POST['filtro_fecha_desde']) : '';
$filtro_fecha_hasta = isset($_POST['filtro_fecha_hasta']) ? trim($_POST['filtro_fecha_hasta']) : '';

// Debug: mostrar filtros recibidos
error_log('Filtros recibidos: ' . json_encode([
    'empresa' => $filtro_empresa,
    'sucursal' => $filtro_sucursal,
    'proveedor' => $filtro_proveedor,
    'estado' => $filtro_estado,
    'tipo_gasto' => $filtro_tipo_gasto,
    'forma_pago' => $filtro_forma_pago,
    'fecha_desde' => $filtro_fecha_desde,
    'fecha_hasta' => $filtro_fecha_hasta
]));

// Debug adicional para fechas
error_log('Filtros de fecha específicos:');
error_log('fecha_desde: ' . $filtro_fecha_desde);
error_log('fecha_hasta: ' . $filtro_fecha_hasta);

try {
    $conexion = conectar_bd();
    
    if (!$conexion) {
        throw new Exception('Error de conexión a la base de datos');
    }
    
    // Construir la consulta base con JOINs para obtener información relacionada
    $query_base = "
        FROM gastos g
        LEFT JOIN empresas e ON g.empresa_gasto = e.id_empresa
        LEFT JOIN sucursal s ON g.sucursal_gasto = s.id_sucursal
        LEFT JOIN proveedores p ON g.proveedor_gasto = p.id_proveedor
        LEFT JOIN tipo_de_gasto tg ON g.tipo_de_gasto = tg.id_tipo_gasto
        LEFT JOIN formas_de_pago fp ON g.forma_pago_gasto = fp.id_forma_de_pago
        WHERE 1=1
    ";
    
    $params = [];
    $types = '';
    
    // Aplicar filtros
    if (!empty($filtro_empresa)) {
        $query_base .= " AND g.empresa_gasto = ?";
        $params[] = $filtro_empresa;
        $types .= 'i';
    }
    
    if (!empty($filtro_sucursal)) {
        $query_base .= " AND g.sucursal_gasto = ?";
        $params[] = $filtro_sucursal;
        $types .= 'i';
    }
    
    if (!empty($filtro_proveedor)) {
        $query_base .= " AND g.proveedor_gasto = ?";
        $params[] = $filtro_proveedor;
        $types .= 'i';
    }
    
    if (!empty($filtro_estado)) {
        $query_base .= " AND g.estado_gasto = ?";
        $params[] = $filtro_estado;
        $types .= 's';
    }
    
    if (!empty($filtro_tipo_gasto)) {
        $query_base .= " AND g.tipo_de_gasto = ?";
        $params[] = $filtro_tipo_gasto;
        $types .= 'i';
    }
    
    if (!empty($filtro_forma_pago)) {
        $query_base .= " AND g.forma_pago_gasto = ?";
        $params[] = $filtro_forma_pago;
        $types .= 'i';
    }
    
    // Filtros de fecha
    if (!empty($filtro_fecha_desde)) {
        error_log('Aplicando filtro fecha desde: ' . $filtro_fecha_desde);
        $query_base .= " AND g.fecha_gasto >= ?";
        $params[] = $filtro_fecha_desde;
        $types .= 's';
    }
    
    if (!empty($filtro_fecha_hasta)) {
        error_log('Aplicando filtro fecha hasta: ' . $filtro_fecha_hasta);
        $query_base .= " AND g.fecha_gasto <= ?";
        $params[] = $filtro_fecha_hasta;
        $types .= 's';
    }
    
    // Búsqueda general
    if (!empty($search)) {
        $query_base .= " AND (
            CAST(g.id_gasto AS CHAR) LIKE ? OR
            g.descripcion_gasto LIKE ? OR 
            e.nombre_empresa LIKE ? OR 
            s.nombre_sucursal LIKE ? OR 
            p.nombre_proveedor LIKE ? OR 
            tg.nombre_tipo_gasto LIKE ? OR
            g.numero_factura_proveedor LIKE ?
        )";
        $search_param = "%$search%";
        for ($i = 0; $i < 7; $i++) {
            $params[] = $search_param;
            $types .= 's';
        }
    }
    
    // Contar total de registros
    $count_query = "SELECT COUNT(*) as total " . $query_base;
    $stmt_count = mysqli_prepare($conexion, $count_query);
    if (!empty($params)) {
        mysqli_stmt_bind_param($stmt_count, $types, ...$params);
    }
    mysqli_stmt_execute($stmt_count);
    $result_count = mysqli_stmt_get_result($stmt_count);
    $total_records = mysqli_fetch_assoc($result_count)['total'];
    mysqli_stmt_close($stmt_count);
    
    // Consulta principal con datos
    $data_query = "
        SELECT 
            g.id_gasto,
            g.fecha_gasto,
            e.nombre_empresa,
            s.nombre_sucursal,
            p.nombre_proveedor,
            tg.nombre_tipo_gasto,
            g.descripcion_gasto,
            g.total_gasto,
            g.estado_gasto
        " . $query_base . "
        ORDER BY g.fecha_gasto DESC
        LIMIT ?, ?
    ";
    
    // Agregar parámetros de paginación
    $params[] = $start;
    $params[] = $length;
    $types .= 'ii';
    
    $stmt_data = mysqli_prepare($conexion, $data_query);
    mysqli_stmt_bind_param($stmt_data, $types, ...$params);
    mysqli_stmt_execute($stmt_data);
    $result_data = mysqli_stmt_get_result($stmt_data);
    
    $data = [];
    while ($row = mysqli_fetch_assoc($result_data)) {
        $data[] = [
            $row['id_gasto'],
            date('d/m/Y', strtotime($row['fecha_gasto'])),
            $row['nombre_empresa'] ?: 'N/A',
            $row['nombre_sucursal'] ?: 'N/A',
            $row['nombre_proveedor'] ?: 'N/A',
            $row['nombre_tipo_gasto'] ?: 'N/A',
            $row['descripcion_gasto'] ?: 'Sin descripción',
            number_format($row['total_gasto'], 2) . ' €',
            $row['estado_gasto']
        ];
    }
    
    mysqli_stmt_close($stmt_data);
    mysqli_close($conexion);
    
    // Respuesta para DataTables
    echo json_encode([
        'draw' => $draw,
        'recordsTotal' => $total_records,
        'recordsFiltered' => $total_records,
        'data' => $data
    ]);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'error' => 'Error interno del servidor: ' . $e->getMessage(),
        'draw' => $draw,
        'recordsTotal' => 0,
        'recordsFiltered' => 0,
        'data' => []
    ]);
}
?>