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
    $filtro_sucursal = isset($_POST['filtro_sucursal']) ? trim($_POST['filtro_sucursal']) : '';
    
    // Construir WHERE clause
    $whereConditions = array();
    $params = array();
    $types = '';
    
    // Filtro de sucursal
    if (!empty($filtro_sucursal)) {
        $whereConditions[] = "sucursal.nombre_sucursal = ?";
        $params[] = $filtro_sucursal;
        $types .= 's';
    }
    
    // Búsqueda global
    if (!empty($searchValue)) {
        $whereConditions[] = "(
            CAST(av.id_devolucion AS CHAR) LIKE ? OR
            CAST(av.id_venta_original AS CHAR) LIKE ? OR
            CAST(av.fecha_devolucion AS CHAR) LIKE ? OR
            CAST(av.cliente_devolucion AS CHAR) LIKE ? OR
            av.motivo_devolucion LIKE ? OR
            CAST(av.articulo_devolucion AS CHAR) LIKE ? OR
            CAST(av.importe_devolucion AS CHAR) LIKE ? OR
            av.forma_de_pago_devolucion LIKE ? OR
            sucursal.nombre_sucursal LIKE ? OR
            ventas.id_venta_sucursal LIKE ? OR
            CONCAT(clientes.nombre, ' ', clientes.apellido) LIKE ? OR
            articulos_venta.descripcion LIKE ?
        )";
        $searchParam = '%' . $searchValue . '%';
        $params[] = $searchParam;
        $params[] = $searchParam;
        $params[] = $searchParam;
        $params[] = $searchParam;
        $params[] = $searchParam;
        $params[] = $searchParam;
        $params[] = $searchParam;
        $params[] = $searchParam;
        $params[] = $searchParam;
        $params[] = $searchParam;
        $params[] = $searchParam;
        $params[] = $searchParam;
        $types .= 'sssssssssss';
    }
    
    $whereClause = '';
    if (count($whereConditions) > 0) {
        $whereClause = 'WHERE ' . implode(' AND ', $whereConditions);
    }
    
    // Consulta SIN LIMIT para obtener TODOS los registros
    $query = "SELECT 
                av.id_devolucion,
                av.id_venta_original,
                av.fecha_devolucion,
                av.cliente_devolucion,
                av.sucursal_devolucion,
                av.motivo_devolucion,
                av.articulo_devolucion,
                av.importe_devolucion,
                av.forma_de_pago_devolucion,
                av.devolucion_web,
                sucursal.nombre_sucursal,
                ventas.id_venta_sucursal,
                CONCAT(clientes.nombre, ' ', clientes.apellido) AS CLIENTEDATA,
                articulos_venta.id AS SKUARTICULO,
                articulos_venta.descripcion
              FROM devoluciones AS av
              LEFT JOIN sucursal ON av.sucursal_devolucion = sucursal.id_sucursal
              LEFT JOIN ventas ON av.id_venta_original = ventas.id
              LEFT JOIN clientes ON av.cliente_devolucion = clientes.id_cliente
              LEFT JOIN articulos_venta ON av.articulo_devolucion = articulos_venta.id
              $whereClause
              ORDER BY av.fecha_devolucion DESC
    ";
    
    if (!empty($types)) {
        $stmt = mysqli_prepare($conexion, $query);
        if (!$stmt) {
            throw new Exception('Error al preparar la consulta: ' . mysqli_error($conexion));
        }
        mysqli_stmt_bind_param($stmt, $types, ...$params);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        mysqli_stmt_close($stmt);
    } else {
        $result = mysqli_query($conexion, $query);
    }
    
    if (!$result) {
        throw new Exception("Error en consulta: " . mysqli_error($conexion));
    }
    
    // Obtener TODOS los datos
    $data = [];
    while ($row = mysqli_fetch_assoc($result)) {
        // Formatear datos para exportación
        $data[] = [
            $row['id_devolucion'],
            $row['id_venta_original'] ?: '-',
            $row['fecha_devolucion'] ? date('d/m/Y H:i', strtotime($row['fecha_devolucion'])) : '-',
            $row['CLIENTEDATA'] ?: '-',
            $row['nombre_sucursal'] ?: '-',
            $row['motivo_devolucion'] ?: '-',
            $row['SKUARTICULO'] ?: '-',
            $row['descripcion'] ?: '-',
            number_format($row['importe_devolucion'], 2, ',', '.') . ' €',
            $row['forma_de_pago_devolucion'] ?: '-',
            ($row['devolucion_web'] === 'si' || $row['devolucion_web'] === 'true' || $row['devolucion_web'] === '1') ? 'Sí' : 'No'
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
