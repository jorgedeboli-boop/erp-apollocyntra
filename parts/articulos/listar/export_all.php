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
    $filtro_origen = isset($_POST['filtro_origen']) ? trim($_POST['filtro_origen']) : '';
    $filtro_fecha_desde = isset($_POST['filtro_fecha_desde']) ? trim($_POST['filtro_fecha_desde']) : '';
    $filtro_fecha_hasta = isset($_POST['filtro_fecha_hasta']) ? trim($_POST['filtro_fecha_hasta']) : '';
    $filtro_periodo = isset($_POST['filtro_periodo']) ? trim($_POST['filtro_periodo']) : '';
    $filtro_tipo_fecha = isset($_POST['filtro_tipo_fecha']) ? trim($_POST['filtro_tipo_fecha']) : 'vendido';
    
    if ($filtro_tipo_fecha === 'en_venta') {
        $campoFecha = 'a.fecha_en_venta';
    } else {
        $campoFecha = 'a.fecha_alta';
    }
    
    // Construir WHERE clause
    $whereConditions = array();
    $searchParams = array();
    
    // Filtro de fecha según período y tipo de fecha
    if ($filtro_periodo === 'dia') {
        $hoy = date('Y-m-d');
        $whereConditions[] = "DATE({$campoFecha}) = ?";
        $searchParams[] = $hoy;
    } elseif ($filtro_periodo === 'mes') {
        $whereConditions[] = "MONTH({$campoFecha}) = MONTH(CURRENT_DATE()) AND YEAR({$campoFecha}) = YEAR(CURRENT_DATE())";
    } elseif ($filtro_periodo === 'fecha') {
        if (!empty($filtro_fecha_desde) && !empty($filtro_fecha_hasta)) {
            $whereConditions[] = "DATE({$campoFecha}) BETWEEN ? AND ?";
            $searchParams[] = $filtro_fecha_desde;
            $searchParams[] = $filtro_fecha_hasta;
        } elseif (!empty($filtro_fecha_desde)) {
            $whereConditions[] = "DATE({$campoFecha}) >= ?";
            $searchParams[] = $filtro_fecha_desde;
        } elseif (!empty($filtro_fecha_hasta)) {
            $whereConditions[] = "DATE({$campoFecha}) <= ?";
            $searchParams[] = $filtro_fecha_hasta;
        }
    }
    
    // Búsqueda global
    if (!empty($searchValue)) {
        $whereConditions[] = "(
            a.sku LIKE ? OR
            a.descripcion LIKE ?
        )";
        $searchTerm = '%' . $searchValue . '%';
        $searchParams[] = $searchValue;
        $searchParams[] = $searchTerm;
    }
    
    $whereClause = '';
    if (count($whereConditions) > 0) {
        $whereClause = 'WHERE ' . implode(' AND ', $whereConditions);
    }
    
    // Consulta SIN LIMIT para obtener TODOS los registros
    $query = "
        SELECT 
            a.sku as id_articulo,
            a.descripcion,
            a.precio,
            a.precio_coste,
            a.fecha_en_venta,
            u.nombre_usuario
        FROM articulos a
        LEFT JOIN usuarios u ON a.creado_por = u.id_usuario
        $whereClause
        ORDER BY a.sku DESC
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
        $data[] = [
            $row['id_articulo'],
            $row['descripcion'],
            number_format($row['precio'], 0, ',', '.') . ' €',
            number_format($row['precio_coste'], 0, ',', '.') . ' €',
            '-',
            !empty($row['fecha_en_venta']) && $row['fecha_en_venta'] !== '0000-00-00 00:00:00' ? date('d/m/Y', strtotime($row['fecha_en_venta'])) : '-',
            '-',
            $row['nombre_usuario'] ? $row['nombre_usuario'] : 'N/A',
            '—'
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

