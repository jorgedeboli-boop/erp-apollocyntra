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
    
    if ($filtro_tipo_fecha === 'enviado') {
        $campoFecha = 'av.fecha_enviado';
    } elseif ($filtro_tipo_fecha === 'en_venta') {
        $campoFecha = 'av.fecha_en_venta';
    } else {
        $campoFecha = 'av.fecha_vendido';
    }
    
    // Construir WHERE clause
    $whereConditions = array();
    $searchParams = array();
    
    // Filtro de origen
    if (!empty($filtro_origen)) {
        $whereConditions[] = "av.origen_articulo = ?";
        $searchParams[] = $filtro_origen;
    }
    
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
            av.id LIKE ? OR
            av.descripcion LIKE ?
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
            av.id as id_articulo,
            av.descripcion,
            av.precio,
            av.precio_coste,
            av.fecha_enviado,
            av.fecha_en_venta,
            av.fecha_vendido,
            av.origen_articulo,
            u.nombre_usuario
        FROM articulos_venta av
        LEFT JOIN usuarios u ON av.creado_por = u.id_usuario
        $whereClause
        ORDER BY av.id DESC
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
        // Formatear origen
        $origen = ($row['origen_articulo'] === 'sucursal') ? 'Otro' : ucfirst((string) $row['origen_articulo']);
        
        // Formatear datos para exportación
        $data[] = [
            $row['id_articulo'],                                                                                                                          // 0 - SKU
            $row['descripcion'],                                                                                                                          // 1 - Descripción
            number_format($row['precio'], 0, ',', '.') . ' €',                                                                                           // 2 - Precio (sin decimales)
            number_format($row['precio_coste'], 0, ',', '.') . ' €',                                                                                     // 3 - Precio Coste (sin decimales)
            !empty($row['fecha_enviado']) && $row['fecha_enviado'] !== '0000-00-00 00:00:00' ? date('d/m/Y', strtotime($row['fecha_enviado'])) : '-',  // 4 - F. Enviado
            !empty($row['fecha_en_venta']) && $row['fecha_en_venta'] !== '0000-00-00 00:00:00' ? date('d/m/Y', strtotime($row['fecha_en_venta'])) : '-', // 5 - F. En Venta
            !empty($row['fecha_vendido']) && $row['fecha_vendido'] !== '0000-00-00' ? date('d/m/Y', strtotime($row['fecha_vendido'])) : '-',             // 6 - F. Vendido
            $row['nombre_usuario'] ? $row['nombre_usuario'] : 'N/A',                                                                                     // 7 - Creado Por
            $origen                                                                                                                                       // 8 - Origen
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

