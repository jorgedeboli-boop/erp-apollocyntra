<?php
require_once '../../../include/session.php';
require_once '../../../include/functions.php';

header('Content-Type: application/json');

try {
    $conexion = conectar_bd();
    
    // Obtener filtros
    $filtro_tipo = isset($_POST['filtro_tipo']) ? trim($_POST['filtro_tipo']) : '';
    $filtro_estado = isset($_POST['filtro_estado']) ? trim($_POST['filtro_estado']) : '';
    $filtro_origen = isset($_POST['filtro_origen']) ? trim($_POST['filtro_origen']) : '';
    $filtro_fecha_desde = isset($_POST['filtro_fecha_desde']) ? trim($_POST['filtro_fecha_desde']) : '';
    $filtro_fecha_hasta = isset($_POST['filtro_fecha_hasta']) ? trim($_POST['filtro_fecha_hasta']) : '';
    $filtro_periodo = isset($_POST['filtro_periodo']) ? trim($_POST['filtro_periodo']) : '';
    $filtro_tipo_fecha = isset($_POST['filtro_tipo_fecha']) ? trim($_POST['filtro_tipo_fecha']) : 'vendido';
    $tipo_stat = isset($_POST['tipo']) ? trim($_POST['tipo']) : 'total';
    
    if ($filtro_tipo_fecha === 'enviado') {
        $campoFecha = 'av.fecha_enviado';
    } elseif ($filtro_tipo_fecha === 'en_venta') {
        $campoFecha = 'av.fecha_en_venta';
    } else {
        $campoFecha = 'av.fecha_vendido';
    }
    
    // Construir WHERE clause
    $whereConditions = array();
    $params = array();
    $types = '';
    
    // Filtro de tipo
    if (!empty($filtro_tipo)) {
        $whereConditions[] = "av.tipo = ?";
        $params[] = $filtro_tipo;
        $types .= 's';
    }
    
    // Filtro de estado
    if (!empty($filtro_estado)) {
        $whereConditions[] = "av.estado = ?";
        $params[] = $filtro_estado;
        $types .= 's';
    }
    
    // Filtro de origen
    if (!empty($filtro_origen)) {
        $whereConditions[] = "av.origen_articulo = ?";
        $params[] = $filtro_origen;
        $types .= 's';
    }
    
    // Filtro de fecha según período y tipo de fecha
    if ($filtro_periodo === 'dia') {
        $hoy = date('Y-m-d');
        $whereConditions[] = "DATE({$campoFecha}) = ?";
        $params[] = $hoy;
        $types .= 's';
    } elseif ($filtro_periodo === 'mes') {
        $whereConditions[] = "MONTH({$campoFecha}) = MONTH(CURRENT_DATE()) AND YEAR({$campoFecha}) = YEAR(CURRENT_DATE())";
    } elseif ($filtro_periodo === 'fecha') {
        if (!empty($filtro_fecha_desde) && !empty($filtro_fecha_hasta)) {
            $whereConditions[] = "DATE({$campoFecha}) BETWEEN ? AND ?";
            $params[] = $filtro_fecha_desde;
            $params[] = $filtro_fecha_hasta;
            $types .= 'ss';
        } elseif (!empty($filtro_fecha_desde)) {
            $whereConditions[] = "DATE({$campoFecha}) >= ?";
            $params[] = $filtro_fecha_desde;
            $types .= 's';
        } elseif (!empty($filtro_fecha_hasta)) {
            $whereConditions[] = "DATE({$campoFecha}) <= ?";
            $params[] = $filtro_fecha_hasta;
            $types .= 's';
        }
    }
    
    // Agregar condición específica según el tipo de estadística
    switch($tipo_stat) {
        case 'total-enventa':
            $whereConditions[] = "av.estado = 'enventa'";
            break;
        case 'total-vendidos':
            $whereConditions[] = "av.estado IN ('vendido', 'vendido_web')";
            break;
        case 'total-reservados':
            $whereConditions[] = "av.estado = 'reservado'";
            break;
    }
    
    $whereClause = '';
    if (count($whereConditions) > 0) {
        $whereClause = 'WHERE ' . implode(' AND ', $whereConditions);
    }
    
    // Consulta para contar
    $query = "
        SELECT COUNT(*) as total
        FROM articulos_venta av
        $whereClause
    ";
    
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

