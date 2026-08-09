<?php
/**
 * Server-side processing para DataTable de ventas
 */

require_once '../../../include/session.php';
require_once '../../../include/functions.php';

header('Content-Type: application/json');

try {
    // Conectar BD
    $conexion = conectar_bd();
    
    // Obtener parámetros de DataTables
    $draw = isset($_POST['draw']) ? (int)$_POST['draw'] : 1;
    $start = isset($_POST['start']) ? (int)$_POST['start'] : 0;
    $length = isset($_POST['length']) ? (int)$_POST['length'] : 10;
    $searchValue = isset($_POST['search']['value']) ? $_POST['search']['value'] : '';
    
    // Obtener filtros
    $filtro_sucursal = isset($_POST['filtro_sucursal']) ? trim($_POST['filtro_sucursal']) : '';
    $filtro_tipo_venta = isset($_POST['filtro_tipo_venta']) ? trim($_POST['filtro_tipo_venta']) : '';
    $filtro_venta_web = isset($_POST['filtro_venta_web']) ? trim($_POST['filtro_venta_web']) : '';
    $filtro_forma_pago = isset($_POST['filtro_forma_pago']) ? trim($_POST['filtro_forma_pago']) : '';
    $filtro_fecha_desde = isset($_POST['filtro_fecha_desde']) ? trim($_POST['filtro_fecha_desde']) : '';
    $filtro_fecha_hasta = isset($_POST['filtro_fecha_hasta']) ? trim($_POST['filtro_fecha_hasta']) : '';
    $filtro_periodo = isset($_POST['filtro_periodo']) ? trim($_POST['filtro_periodo']) : 'todos';
    
    // Construir WHERE clause
    $whereConditions = array();
    $params = array();
    $types = '';
    
    // Condición fija: solo ventas con estado 'vendido'
    //$whereConditions[] = "av.estado = 'vendido' ";
    
    // Filtro de sucursal
    if (!empty($filtro_sucursal)) {
        $whereConditions[] = "s.nombre_sucursal = ?";
        $params[] = $filtro_sucursal;
        $types .= 's';
    }
    
    // Filtro de tipo de venta (venta a plazos)
    if (!empty($filtro_tipo_venta)) {
        if ($filtro_tipo_venta === 'plazos') {
            $whereConditions[] = "av.venta_plazos = 'si'";
        } else {
            $whereConditions[] = "(av.venta_plazos = 'no' OR av.venta_plazos IS NULL)";
        }
    }
    
    // Filtro de venta web
    if (!empty($filtro_venta_web)) {
        if ($filtro_venta_web === 'true') {
            $whereConditions[] = "av.venta_web = 'true'";
        } else {
            $whereConditions[] = "(av.venta_web = 'false' OR av.venta_web IS NULL)";
        }
    }
    
    // Filtro de forma de pago
    if (!empty($filtro_forma_pago)) {
        $whereConditions[] = "av.tipo_pago = ?";
        $params[] = $filtro_forma_pago;
        $types .= 's';
    }
    
    // Filtro de fecha según período
    if ($filtro_periodo === 'dia') {
        $hoy = date('Y-m-d');
        $whereConditions[] = "DATE(av.fecha) = ?";
        $params[] = $hoy;
        $types .= 's';
    } elseif ($filtro_periodo === 'mes') {
        $whereConditions[] = "MONTH(av.fecha) = MONTH(CURRENT_DATE()) AND YEAR(av.fecha) = YEAR(CURRENT_DATE())";
    } elseif ($filtro_periodo === 'fecha') {
        if (!empty($filtro_fecha_desde) && !empty($filtro_fecha_hasta)) {
            $whereConditions[] = "DATE(av.fecha) BETWEEN ? AND ?";
            $params[] = $filtro_fecha_desde;
            $params[] = $filtro_fecha_hasta;
            $types .= 'ss';
        } elseif (!empty($filtro_fecha_desde)) {
            $whereConditions[] = "DATE(av.fecha) >= ?";
            $params[] = $filtro_fecha_desde;
            $types .= 's';
        } elseif (!empty($filtro_fecha_hasta)) {
            $whereConditions[] = "DATE(av.fecha) <= ?";
            $params[] = $filtro_fecha_hasta;
            $types .= 's';
        }
    }
    
    // Búsqueda global
    if (!empty($searchValue)) {
        $whereConditions[] = "(
            av.id_venta_sucursal LIKE ? OR
            av.tipo_pago LIKE ? OR
            s.nombre_sucursal LIKE ? OR
            u.nombre_usuario LIKE ?
        )";
        $searchParam = '%' . $searchValue . '%';
        $params[] = $searchValue;
        $params[] = $searchParam;
        $params[] = $searchParam;
        $params[] = $searchParam;
        $types .= 'ssss';
    }
    
    $whereClause = '';
    if (count($whereConditions) > 0) {
        $whereClause = 'WHERE ' . implode(' AND ', $whereConditions);
    }
    
    // Consulta para contar total de registros (con estado = 'vendido')
    $query_total = "SELECT COUNT(*) as total FROM ventas WHERE estado = 'vendido'";
    $result_total = mysqli_query($conexion, $query_total);
    $row_total = mysqli_fetch_assoc($result_total);
    $recordsTotal = $row_total['total'];
    
    // Consulta para contar registros filtrados
    $query_filtered = "
        SELECT COUNT(*) as total 
        FROM ventas av
        LEFT JOIN sucursal s ON av.id_sucursal = s.id_sucursal
        LEFT JOIN usuarios u ON av.comprado_por = u.id_usuario
        $whereClause
    ";
    
    if (!empty($types)) {
        $stmt_filtered = mysqli_prepare($conexion, $query_filtered);
        mysqli_stmt_bind_param($stmt_filtered, $types, ...$params);
        mysqli_stmt_execute($stmt_filtered);
        $result_filtered = mysqli_stmt_get_result($stmt_filtered);
        $row_filtered = mysqli_fetch_assoc($result_filtered);
        $recordsFiltered = $row_filtered['total'];
        mysqli_stmt_close($stmt_filtered);
    } else {
        $result_filtered = mysqli_query($conexion, $query_filtered);
        $row_filtered = mysqli_fetch_assoc($result_filtered);
        $recordsFiltered = $row_filtered['total'];
    }
    
    // Consulta principal con paginación
    $query = "
        SELECT 
            av.id AS identificador_venta,
            av.id_venta_sucursal,
            av.id_sucursal,
            av.precio,
            av.fecha,
            av.comprado_por,
            av.venta_plazos,
            av.venta_web,
            av.tipo_pago,
            s.nombre_sucursal,
            u.nombre_usuario
        FROM ventas av
        LEFT JOIN sucursal s ON av.id_sucursal = s.id_sucursal
        LEFT JOIN usuarios u ON av.comprado_por = u.id_usuario
        $whereClause
        ORDER BY av.fecha DESC
        LIMIT ?, ?
    ";
    
    $allParams = array_merge($params, [$start, $length]);
    $allTypes = $types . 'ii';
    
    $stmt = mysqli_prepare($conexion, $query);
    
    if (!empty($types)) {
        mysqli_stmt_bind_param($stmt, $allTypes, ...$allParams);
    } else {
        mysqli_stmt_bind_param($stmt, 'ii', $start, $length);
    }
    
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    
    $data = [];
    while ($row = mysqli_fetch_assoc($result)) {
        // Badge para venta plazos
        $venta_plazos_badge = '';
        if ($row['venta_plazos'] === 'si') {
            $venta_plazos_badge = '<span class="badge bg-label-success">Sí</span>';
        } else {
            $venta_plazos_badge = '<span class="badge bg-label-secondary">No</span>';
        }
        
        // Badge para venta web
        $venta_web_badge = '';
        if ($row['venta_web'] === 'true') {
            $venta_web_badge = '<span class="badge bg-label-info">Sí</span>';
        } else {
            $venta_web_badge = '<span class="badge bg-label-secondary">No</span>';
        }
        
        // Badge para forma de pago
        $forma_pago_badge = '';
        $forma_pago_class = 'secondary';
        $forma_pago_texto = !empty($row['tipo_pago']) ? ucfirst($row['tipo_pago']) : 'N/A';
        
        switch($row['tipo_pago']) {
            case 'contado':
                $forma_pago_class = 'success';
                $forma_pago_texto = 'Contado';
                break;
            case 'tarjeta':
                $forma_pago_class = 'primary';
                $forma_pago_texto = 'Tarjeta';
                break;
            case 'transferencia':
                $forma_pago_class = 'info';
                $forma_pago_texto = 'Transferencia';
                break;
            case 'bizum':
                $forma_pago_class = 'warning';
                $forma_pago_texto = 'Bizum';
                break;
            case 'combinado':
                $forma_pago_class = 'danger';
                $forma_pago_texto = 'Combinado';
                break;
        }
        $forma_pago_badge = '<span class="badge bg-label-' . $forma_pago_class . '">' . htmlspecialchars($forma_pago_texto) . '</span>';
        
        $data[] = [
            htmlspecialchars($row['id_venta_sucursal']),                       // 0 - Nº venta
            number_format($row['precio'], 0, ',', '.') . ' €',                // 1 - Total venta
            !empty($row['fecha']) ? date('d/m/Y H:i', strtotime($row['fecha'])) : 'N/A', // 2 - Fecha venta
            htmlspecialchars($row['nombre_sucursal'] ?: 'N/A'),               // 3 - Sucursal venta
            htmlspecialchars($row['nombre_usuario'] ?: 'N/A'),                // 4 - Vendido por
            $venta_plazos_badge,                                                // 5 - Venta plazos
            $venta_web_badge,                                                   // 6 - Venta web
            $forma_pago_badge,                                                  // 7 - Forma de pago
            $row['identificador_venta']                                        // 8 - ID (hidden)
        ];
    }
    
    mysqli_stmt_close($stmt);
    mysqli_close($conexion);
    
    // Respuesta
    echo json_encode([
        'draw' => $draw,
        'recordsTotal' => $recordsTotal,
        'recordsFiltered' => $recordsFiltered,
        'data' => $data
    ]);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'error' => $e->getMessage()
    ]);
}
?>

