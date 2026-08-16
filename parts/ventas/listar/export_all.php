<?php
require_once '../../../include/session.php';
require_once '../../../include/functions.php';

// Verificar autenticación
if (!usuario_autenticado()) {
    http_response_code(401);
    echo json_encode(['error' => 'No autorizado']);
    exit;
}

header('Content-Type: application/json');

try {
    // Conectar BD
    $conexion = conectar_bd();
    
    // Obtener parámetros de filtros
    $searchValue = isset($_POST['search']) ? trim($_POST['search']) : '';
    $filtro_tipo_venta = isset($_POST['filtro_tipo_venta']) ? trim($_POST['filtro_tipo_venta']) : '';
    $filtro_venta_web = isset($_POST['filtro_venta_web']) ? trim($_POST['filtro_venta_web']) : '';
    $filtro_forma_pago = isset($_POST['filtro_forma_pago']) ? trim($_POST['filtro_forma_pago']) : '';
    $filtro_fecha_desde = isset($_POST['filtro_fecha_desde']) ? trim($_POST['filtro_fecha_desde']) : '';
    $filtro_fecha_hasta = isset($_POST['filtro_fecha_hasta']) ? trim($_POST['filtro_fecha_hasta']) : '';
    $filtro_periodo = isset($_POST['filtro_periodo']) ? trim($_POST['filtro_periodo']) : '';
    
    // Construir WHERE clause
    $whereConditions = array();
    $searchParams = array();
    
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
        $searchParams[] = $filtro_forma_pago;
    }
    
    // Filtro de fecha
    if ($filtro_periodo === 'dia') {
        $hoy = date('Y-m-d');
        $whereConditions[] = "DATE(av.fecha) = ?";
        $searchParams[] = $hoy;
    } elseif ($filtro_periodo === 'mes') {
        $whereConditions[] = "MONTH(av.fecha) = MONTH(CURRENT_DATE()) AND YEAR(av.fecha) = YEAR(CURRENT_DATE())";
    } elseif ($filtro_periodo === 'fecha') {
        if (!empty($filtro_fecha_desde) && !empty($filtro_fecha_hasta)) {
            $whereConditions[] = "DATE(av.fecha) BETWEEN ? AND ?";
            $searchParams[] = $filtro_fecha_desde;
            $searchParams[] = $filtro_fecha_hasta;
        } elseif (!empty($filtro_fecha_desde)) {
            $whereConditions[] = "DATE(av.fecha) >= ?";
            $searchParams[] = $filtro_fecha_desde;
        } elseif (!empty($filtro_fecha_hasta)) {
            $whereConditions[] = "DATE(av.fecha) <= ?";
            $searchParams[] = $filtro_fecha_hasta;
        }
    }
    
    // Búsqueda global
    if (!empty($searchValue)) {
        $whereConditions[] = "(
            av.id_venta_sucursal LIKE ? OR
            av.tipo_pago LIKE ? OR
            u.nombre_usuario LIKE ?
        )";
        $searchTerm = '%' . $searchValue . '%';
        $searchParams[] = $searchValue;
        $searchParams[] = $searchTerm;
        $searchParams[] = $searchTerm;
    }
    
    $whereClause = '';
    if (count($whereConditions) > 0) {
        $whereClause = 'WHERE ' . implode(' AND ', $whereConditions);
    }
    
    // Consulta SIN LIMIT para obtener TODOS los registros
    $query = "
        SELECT 
            av.id AS identificador_venta,
            av.id_venta_sucursal,
            av.precio,
            av.fecha,
            av.comprado_por,
            av.venta_plazos,
            av.venta_web,
            av.tipo_pago,
            u.nombre_usuario
        FROM ventas av
        LEFT JOIN usuarios u ON av.comprado_por = u.id_usuario
        $whereClause
        ORDER BY av.fecha DESC
    ";
    
    if (!empty($searchParams)) {
        $stmt = mysqli_prepare($conexion, $query);
        if ($stmt) {
            $types = str_repeat('s', count($searchParams));
            mysqli_stmt_bind_param($stmt, $types, ...$searchParams);
            mysqli_stmt_execute($stmt);
            $result = mysqli_stmt_get_result($stmt);
        } else {
            throw new Exception("Error en preparación de consulta: " . mysqli_error($conexion));
        }
    } else {
        $result = mysqli_query($conexion, $query);
    }
    
    if (!$result) {
        throw new Exception("Error en consulta: " . mysqli_error($conexion));
    }
    
    // Obtener TODOS los datos
    $data = [];
    while ($row = mysqli_fetch_assoc($result)) {
        // Formatear datos
        $venta_plazos = ($row['venta_plazos'] === 'si') ? 'Sí' : 'No';
        $venta_web = ($row['venta_web'] === 'true') ? 'Sí' : 'No';
        
        $forma_pago = 'N/A';
        if (!empty($row['tipo_pago'])) {
            switch($row['tipo_pago']) {
                case 'contado': $forma_pago = 'Contado'; break;
                case 'tarjeta': $forma_pago = 'Tarjeta'; break;
                case 'transferencia': $forma_pago = 'Transferencia'; break;
                case 'bizum': $forma_pago = 'Bizum'; break;
                default: $forma_pago = ucfirst($row['tipo_pago']);
            }
        }
        
        $data[] = [
            $row['id_venta_sucursal'],
            number_format($row['precio'], 0, ',', '.') . ' €',
            !empty($row['fecha']) ? date('d/m/Y H:i', strtotime($row['fecha'])) : 'N/A',
            $row['nombre_usuario'] ? $row['nombre_usuario'] : 'N/A',
            $venta_plazos,
            $venta_web,
            $forma_pago
        ];
    }
    
    mysqli_close($conexion);
    
    // Respuesta
    echo json_encode([
        'success' => true,
        'data' => $data,
        'total' => count($data)
    ]);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
?>

