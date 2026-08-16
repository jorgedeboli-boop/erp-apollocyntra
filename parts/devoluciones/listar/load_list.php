<?php
/**
 * Server-side processing para DataTable de devoluciones
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
    $searchValue = isset($_POST['search']['value']) ? trim($_POST['search']['value']) : '';
    
    // Construir WHERE clause
    $whereConditions = array();
    $params = array();
    $types = '';
    
    // Búsqueda global
    if (!empty($searchValue)) {
        $whereConditions[] = "(
            CAST(av.id_devolucion AS CHAR) LIKE ? OR
            CAST(av.id_venta_original AS CHAR) LIKE ? OR
            CAST(av.fecha_devolucion AS CHAR) LIKE ? OR
            CAST(av.cliente_devolucion AS CHAR) LIKE ? OR
            av.motivo_devolucion LIKE ? OR
            CAST(av.importe_devolucion AS CHAR) LIKE ? OR
            av.forma_de_pago_devolucion LIKE ? OR
            ventas.id_venta_sucursal LIKE ? OR
            CONCAT(clientes.nombre, ' ', clientes.apellido) LIKE ? OR
            CAST(av.articulo_devolucion AS CHAR) LIKE ?
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
        $types .= 'ssssssssss';
    }
    
    $whereClause = '';
    if (count($whereConditions) > 0) {
        $whereClause = 'WHERE ' . implode(' AND ', $whereConditions);
    }
    
    // Consulta base
    $queryBase = "
        FROM devoluciones AS av
        LEFT JOIN ventas ON av.id_venta_original = ventas.id
        LEFT JOIN clientes ON av.cliente_devolucion = clientes.id_cliente
        $whereClause
    ";
    
    // Consulta para contar total de registros
    $query_total = "SELECT COUNT(*) as total FROM devoluciones";
    $result_total = mysqli_query($conexion, $query_total);
    $row_total = mysqli_fetch_assoc($result_total);
    $recordsTotal = isset($row_total['total']) ? (int)$row_total['total'] : 0;
    
    // Consulta para contar registros filtrados
    $query_filtered = "SELECT COUNT(*) as total " . $queryBase;
    
    if (!empty($types)) {
        $stmt_filtered = mysqli_prepare($conexion, $query_filtered);
        mysqli_stmt_bind_param($stmt_filtered, $types, ...$params);
        mysqli_stmt_execute($stmt_filtered);
        $result_filtered = mysqli_stmt_get_result($stmt_filtered);
        $row_filtered = mysqli_fetch_assoc($result_filtered);
        $recordsFiltered = isset($row_filtered['total']) ? (int)$row_filtered['total'] : 0;
        mysqli_stmt_close($stmt_filtered);
    } else {
        $result_filtered = mysqli_query($conexion, $query_filtered);
        $row_filtered = mysqli_fetch_assoc($result_filtered);
        $recordsFiltered = isset($row_filtered['total']) ? (int)$row_filtered['total'] : 0;
    }
    
    // Parámetros de ordenamiento
    $orderColumn = isset($_POST['order'][0]['column']) ? (int)$_POST['order'][0]['column'] : 2; // Por defecto ordenar por fecha
    $orderDir = isset($_POST['order'][0]['dir']) ? $_POST['order'][0]['dir'] : 'desc';
    
    // Mapeo de columnas para ordenamiento
    $columnMap = [
        0 => 'av.id_devolucion',
        1 => 'av.id_venta_original',
        2 => 'av.fecha_devolucion',
        3 => 'CLIENTEDATA',
        4 => 'av.motivo_devolucion',
        5 => 'av.articulo_devolucion',
        6 => 'av.articulo_devolucion',
        7 => 'av.importe_devolucion',
        8 => 'av.forma_de_pago_devolucion',
        9 => 'av.devolucion_web'
    ];
    
    // Validar y sanitizar ORDER BY
    $allowedColumns = [
        'av.id_devolucion',
        'av.id_venta_original',
        'av.fecha_devolucion',
        'CLIENTEDATA',
        'av.motivo_devolucion',
        'av.articulo_devolucion',
        'av.importe_devolucion',
        'av.forma_de_pago_devolucion',
        'av.devolucion_web'
    ];
    
    $orderBy = isset($columnMap[$orderColumn]) ? $columnMap[$orderColumn] : 'av.fecha_devolucion';
    if (!in_array($orderBy, $allowedColumns)) {
        $orderBy = 'av.fecha_devolucion';
    }
    
    $orderDir = strtoupper($orderDir) === 'ASC' ? 'ASC' : 'DESC';
    
    // Consulta principal con paginación
    $query = "SELECT 
                av.id_devolucion,
                av.id_venta_original,
                av.fecha_devolucion,
                av.cliente_devolucion,
                av.motivo_devolucion,
                av.articulo_devolucion,
                av.importe_devolucion,
                av.forma_de_pago_devolucion,
                av.devolucion_web,
                ventas.id_venta_sucursal,
                CONCAT(clientes.nombre, ' ', clientes.apellido) AS CLIENTEDATA,
                av.articulo_devolucion AS SKUARTICULO
              " . $queryBase . "
              ORDER BY $orderBy $orderDir
              LIMIT ? OFFSET ?";
    
    // Agregar parámetros de LIMIT
    $params[] = $length;
    $params[] = $start;
    $types .= 'ii';
    
    $stmt = mysqli_prepare($conexion, $query);
    if (!$stmt) {
        throw new Exception('Error al preparar la consulta: ' . mysqli_error($conexion));
    }
    
    if (!empty($types)) {
        mysqli_stmt_bind_param($stmt, $types, ...$params);
    }
    
    if (!mysqli_stmt_execute($stmt)) {
        mysqli_stmt_close($stmt);
        throw new Exception('Error al ejecutar la consulta: ' . mysqli_stmt_error($stmt));
    }
    
    $result = mysqli_stmt_get_result($stmt);
    if (!$result) {
        mysqli_stmt_close($stmt);
        throw new Exception('Error al obtener el resultado: ' . mysqli_error($conexion));
    }
    
    $data = array();
    while ($row = mysqli_fetch_assoc($result)) {
        $data[] = [
            $row['id_devolucion'],
            $row['id_venta_original'],
            $row['fecha_devolucion'],
            $row['CLIENTEDATA'] ?: '-',
            $row['motivo_devolucion'] ?: '-',
            $row['SKUARTICULO'] ?: '-',
            '-',
            number_format($row['importe_devolucion'], 2, ',', '.') . ' €',
            $row['forma_de_pago_devolucion'] ?: '-',
            $row['devolucion_web'] ?: '-'
        ];
    }
    mysqli_stmt_close($stmt);
    
    echo json_encode([
        'draw' => $draw,
        'recordsTotal' => $recordsTotal,
        'recordsFiltered' => $recordsFiltered,
        'data' => $data
    ]);
    
} catch (Exception $e) {
    // Limpiar cualquier output previo
    if (ob_get_length()) {
        ob_clean();
    }
    
    http_response_code(500);
    header('Content-Type: application/json');
    echo json_encode([
        'draw' => isset($draw) ? $draw : 1,
        'recordsTotal' => 0,
        'recordsFiltered' => 0,
        'data' => [],
        'error' => 'Error al cargar los datos: ' . $e->getMessage()
    ]);
    exit;
} catch (Error $e) {
    // Limpiar cualquier output previo
    if (ob_get_length()) {
        ob_clean();
    }
    
    http_response_code(500);
    header('Content-Type: application/json');
    echo json_encode([
        'draw' => isset($draw) ? $draw : 1,
        'recordsTotal' => 0,
        'recordsFiltered' => 0,
        'data' => [],
        'error' => 'Error fatal: ' . $e->getMessage()
    ]);
    exit;
}
?>
