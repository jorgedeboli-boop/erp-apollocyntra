<?php
/**
 * Archivo para obtener datos de sucursales de un proveedor específico para DataTable
 * NOTA: Los proveedores no tienen sucursales, este archivo se mantiene por compatibilidad
 * Compatible con PHP 7.0
 */

require_once '../../../include/session.php';
require_once '../../../include/functions.php';

header('Content-Type: application/json');

try {
    // Verificar que sea una petición POST
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        http_response_code(405);
        echo json_encode(array('error' => 'Método no permitido'));
        exit;
    }
    
    // Obtener parámetros del DataTable
    $draw = isset($_POST['draw']) ? (int)$_POST['draw'] : 1;
    $start = isset($_POST['start']) ? (int)$_POST['start'] : 0;
    $length = isset($_POST['length']) ? (int)$_POST['length'] : 10;
    $search = isset($_POST['search']['value']) ? trim($_POST['search']['value']) : '';
    $order_column = isset($_POST['order'][0]['column']) ? (int)$_POST['order'][0]['column'] : 1;
    $order_dir = isset($_POST['order'][0]['dir']) ? $_POST['order'][0]['dir'] : 'asc';
    
    // Obtener ID del proveedor
    $id_proveedor = isset($_POST['id_proveedor']) ? (int)$_POST['id_proveedor'] : 0;
    
    if (!$id_proveedor) {
        http_response_code(400);
        echo json_encode(array('error' => 'ID de proveedor no válido'));
        exit;
    }
    
    // Conectar BD
    $conexion = conectar_bd();
    
    // Mapeo de columnas para ordenamiento
    $columns = array(
        0 => 's.id_sucursal',
        1 => 's.nombre_sucursal',
        2 => 's.nombre_corto',
        3 => 's.poblacion_tienda',
        4 => 's.provincia_tienda',
        5 => 's.telefono_tienda',
        6 => 's.estado_tienda'
    );
    
    $order_by = isset($columns[$order_column]) ? $columns[$order_column] : 's.nombre_sucursal';
    $order_direction = strtoupper($order_dir) === 'DESC' ? 'DESC' : 'ASC';
    
    // Construir consulta base
    $query_base = "
        FROM sucursal s
        LEFT JOIN usuarios u ON s.responsable_tienda = u.id_usuario
        WHERE s.empresa_id = ?
    ";
    
    // Agregar búsqueda si existe
    $where_conditions = array();
    $params = array($id_proveedor);
    $param_types = 'i';
    
    if (!empty($search)) {
        $search_condition = "
            (s.nombre_sucursal LIKE ? OR 
             s.nombre_corto LIKE ? OR 
             s.poblacion_tienda LIKE ? OR 
             s.provincia_tienda LIKE ? OR 
             s.telefono_tienda LIKE ? OR 
             s.estado_tienda LIKE ?)
        ";
        $where_conditions[] = $search_condition;
        
        $search_param = '%' . $search . '%';
        for ($i = 0; $i < 6; $i++) {
            $params[] = $search_param;
            $param_types .= 's';
        }
    }
    
    if (!empty($where_conditions)) {
        $query_base .= ' AND ' . implode(' AND ', $where_conditions);
    }
    
    // Consulta para contar total de registros
    $query_count = "SELECT COUNT(*) as total " . $query_base;
    $stmt_count = mysqli_prepare($conexion, $query_count);
    mysqli_stmt_bind_param($stmt_count, $param_types, ...$params);
    mysqli_stmt_execute($stmt_count);
    $result_count = mysqli_stmt_get_result($stmt_count);
    $total_records = mysqli_fetch_assoc($result_count)['total'];
    mysqli_stmt_close($stmt_count);
    
    // Consulta para obtener datos con paginación
    $query_data = "
        SELECT 
            s.id_sucursal,
            s.nombre_sucursal,
            s.nombre_corto,
            s.poblacion_tienda,
            s.provincia_tienda,
            s.telefono_tienda,
            s.estado_tienda
        " . $query_base . "
        ORDER BY {$order_by} {$order_direction}
        LIMIT ?, ?
    ";
    
    // Agregar parámetros de paginación
    $params[] = $start;
    $params[] = $length;
    $param_types .= 'ii';
    
    $stmt_data = mysqli_prepare($conexion, $query_data);
    mysqli_stmt_bind_param($stmt_data, $param_types, ...$params);
    mysqli_stmt_execute($stmt_data);
    $result_data = mysqli_stmt_get_result($stmt_data);
    
    $data = array();
    while ($row = mysqli_fetch_assoc($result_data)) {
            $data[] = array(
        $row['id_sucursal'],
        $row['nombre_sucursal'],
        $row['nombre_corto'] ?: 'N/A',
        $row['poblacion_tienda'] ?: 'Sin población',
        $row['provincia_tienda'] ?: 'Sin provincia',
        $row['telefono_tienda'] ?: 'Sin teléfono',
        $row['estado_tienda'] ?: 'Sin estado',
        '' // Columna de acciones (vacía, se renderiza en el frontend)
    );
    }
    
    mysqli_stmt_close($stmt_data);
    mysqli_close($conexion);
    
    // Respuesta para DataTable
    echo json_encode(array(
        'draw' => $draw,
        'recordsTotal' => $total_records,
        'recordsFiltered' => $total_records,
        'data' => $data
    ));
    
} catch (Exception $e) {
    // Error del sistema
    http_response_code(500);
    echo json_encode(array(
        'error' => 'Error interno del servidor: ' . $e->getMessage(),
        'draw' => isset($draw) ? $draw : 1,
        'recordsTotal' => 0,
        'recordsFiltered' => 0,
        'data' => array()
    ));
} catch (Error $e) {
    // Error fatal
    http_response_code(500);
    echo json_encode(array(
        'error' => 'Error fatal del sistema',
        'draw' => isset($draw) ? $draw : 1,
        'recordsTotal' => 0,
        'recordsFiltered' => 0,
        'data' => array()
    ));
}
?>
