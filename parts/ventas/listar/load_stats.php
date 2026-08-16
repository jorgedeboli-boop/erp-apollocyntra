<?php
require_once '../../../include/session.php';
require_once '../../../include/functions.php';

header('Content-Type: application/json');

try {
    $conexion = conectar_bd();
    
    // Obtener filtros
    $filtro_tipo_venta = isset($_POST['filtro_tipo_venta']) ? trim($_POST['filtro_tipo_venta']) : '';
    $filtro_venta_web = isset($_POST['filtro_venta_web']) ? trim($_POST['filtro_venta_web']) : '';
    $filtro_forma_pago = isset($_POST['filtro_forma_pago']) ? trim($_POST['filtro_forma_pago']) : '';
    $filtro_fecha_desde = isset($_POST['filtro_fecha_desde']) ? trim($_POST['filtro_fecha_desde']) : '';
    $filtro_fecha_hasta = isset($_POST['filtro_fecha_hasta']) ? trim($_POST['filtro_fecha_hasta']) : '';
    $filtro_periodo = isset($_POST['filtro_periodo']) ? trim($_POST['filtro_periodo']) : '';
    $tipo_stat = isset($_POST['tipo']) ? trim($_POST['tipo']) : 'total';
    
    // Construir WHERE clause
    $whereConditions = array();
    $params = array();
    $types = '';
    
    // Condición fija
    $whereConditions[] = "av.estado = 'vendido'";
    
    // Filtro de tipo de venta
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
    
    // Filtro de fecha
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
    
    // Agregar condición específica según tipo de estadística
    switch($tipo_stat) {
        case 'total-web':
            $whereConditions[] = "av.venta_web = 'true'";
            break;
        case 'total-plazos':
            $whereConditions[] = "av.venta_plazos = 'si'";
            break;
        case 'total-importe':
            // Para el importe usaremos SUM en lugar de COUNT
            break;
    }
    
    $whereClause = '';
    if (count($whereConditions) > 0) {
        $whereClause = 'WHERE ' . implode(' AND ', $whereConditions);
    }
    
    // Consulta según el tipo de estadística
    if ($tipo_stat === 'total-importe') {
        $query = "
            SELECT COALESCE(SUM(av.precio), 0) as total
            FROM ventas av
            $whereClause
        ";
    } else {
        $query = "
            SELECT COUNT(*) as total
            FROM ventas av
            $whereClause
        ";
    }
    
    if (!empty($types)) {
        $stmt = mysqli_prepare($conexion, $query);
        mysqli_stmt_bind_param($stmt, $types, ...$params);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        $row = mysqli_fetch_assoc($result);
        mysqli_stmt_close($stmt);
    } else {
        $result = mysqli_query($conexion, $query);
        $row = mysqli_fetch_assoc($result);
    }
    
    $total = $row['total'];
    
    // Si es importe, formatear
    if ($tipo_stat === 'total-importe') {
        $total = number_format($total, 0, ',', '.');
    }
    
    mysqli_close($conexion);
    
    echo json_encode([
        'success' => true,
        'total' => $total
    ]);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
?>

