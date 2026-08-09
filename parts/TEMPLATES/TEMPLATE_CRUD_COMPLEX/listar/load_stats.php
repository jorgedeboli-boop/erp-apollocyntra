<?php
/**
 * Archivo para cargar estadísticas de gastos via AJAX
 * Maneja diferentes tipos de consultas para las tarjetas superiores
 */

// Asegurar que no haya salida antes del JSON
ob_clean();

require_once '../../../include/session.php';
require_once '../../../include/functions.php';

header('Content-Type: application/json');

try {
    // Verificar que se haya enviado el tipo de consulta
    if (!isset($_POST['tipo'])) {
        throw new Exception("Tipo de consulta no especificado");
    }
    
    $tipo = $_POST['tipo'];
    $conexion = conectar_bd();
    $resultado = 0;
    
    // Obtener filtros adicionales (igual que en load_list.php)
    $filtro_empresa = isset($_POST['filtro_empresa']) ? trim($_POST['filtro_empresa']) : '';
    $filtro_sucursal = isset($_POST['filtro_sucursal']) ? trim($_POST['filtro_sucursal']) : '';
    $filtro_proveedor = isset($_POST['filtro_proveedor']) ? trim($_POST['filtro_proveedor']) : '';
    $filtro_estado = isset($_POST['filtro_estado']) ? trim($_POST['filtro_estado']) : '';
    $filtro_tipo_gasto = isset($_POST['filtro_tipo_gasto']) ? trim($_POST['filtro_tipo_gasto']) : '';
    $filtro_forma_pago = isset($_POST['filtro_forma_pago']) ? trim($_POST['filtro_forma_pago']) : '';
    $filtro_fecha_desde = isset($_POST['filtro_fecha_desde']) ? trim($_POST['filtro_fecha_desde']) : '';
    $filtro_fecha_hasta = isset($_POST['filtro_fecha_hasta']) ? trim($_POST['filtro_fecha_hasta']) : '';
    
    // Construir condiciones WHERE basadas en los filtros
    $where_conditions = [];
    $params = [];
    $types = '';
    
    if (!empty($filtro_empresa)) {
        $where_conditions[] = "empresa_gasto = ?";
        $params[] = $filtro_empresa;
        $types .= 'i';
    }
    
    if (!empty($filtro_sucursal)) {
        $where_conditions[] = "sucursal_gasto = ?";
        $params[] = $filtro_sucursal;
        $types .= 'i';
    }
    
    if (!empty($filtro_proveedor)) {
        $where_conditions[] = "proveedor_gasto = ?";
        $params[] = $filtro_proveedor;
        $types .= 'i';
    }
    
    if (!empty($filtro_estado)) {
        $where_conditions[] = "estado_gasto = ?";
        $params[] = $filtro_estado;
        $types .= 's';
    }
    
    if (!empty($filtro_tipo_gasto)) {
        $where_conditions[] = "tipo_de_gasto = ?";
        $params[] = $filtro_tipo_gasto;
        $types .= 'i';
    }
    
    if (!empty($filtro_forma_pago)) {
        $where_conditions[] = "forma_pago_gasto = ?";
        $params[] = $filtro_forma_pago;
        $types .= 'i';
    }
    
    if (!empty($filtro_fecha_desde)) {
        $where_conditions[] = "fecha_gasto >= ?";
        $params[] = $filtro_fecha_desde;
        $types .= 's';
    }
    
    if (!empty($filtro_fecha_hasta)) {
        $where_conditions[] = "fecha_gasto <= ?";
        $params[] = $filtro_fecha_hasta;
        $types .= 's';
    }
    
    // Construir la cláusula WHERE
    $where_clause = '';
    if (!empty($where_conditions)) {
        $where_clause = ' WHERE ' . implode(' AND ', $where_conditions);
    }
    
    switch ($tipo) {
        case 'total_gastos':
            // Total de gastos en la tabla gastos
            $query = "SELECT COUNT(*) as total FROM gastos" . $where_clause;
            break;
            
        case 'total_euros':
            // Suma total de todos los gastos
            $query = "SELECT SUM(total_gasto) as total FROM gastos" . $where_clause;
            break;
            
        case 'media_gasto':
            // Media de euros por gasto
            $query = "SELECT AVG(total_gasto) as total FROM gastos" . $where_clause;
            break;
            
        case 'gastos_pendientes':
            // Gastos con estado 'pendiente' (agregar filtro adicional si no está ya aplicado)
            $pendiente_condition = "estado_gasto = 'pendiente'";
            if (!empty($where_conditions)) {
                $query = "SELECT COUNT(*) as total FROM gastos" . $where_clause . " AND " . $pendiente_condition;
            } else {
                $query = "SELECT COUNT(*) as total FROM gastos WHERE " . $pendiente_condition;
            }
            break;
            
        default:
            throw new Exception("Tipo de consulta no válido: " . $tipo);
    }
    
    // Ejecutar consulta con prepared statement si hay parámetros
    if (!empty($params)) {
        $stmt = mysqli_prepare($conexion, $query);
        if (!$stmt) {
            throw new Exception("Error preparando consulta: " . mysqli_error($conexion));
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
    
    // Obtener resultado
    $row = mysqli_fetch_assoc($result);
    $resultado = $row['total'];
    
    // Formatear resultado según el tipo
    if ($tipo === 'total_euros' || $tipo === 'media_gasto') {
        // Para valores monetarios, formatear con 2 decimales
        $resultado = number_format((float)$resultado, 2, ',', '.');
    } else {
        // Para contadores, convertir a entero
        $resultado = (int)$resultado;
    }
    
    // Respuesta exitosa
    echo json_encode([
        'success' => true,
        'total' => $resultado,
        'tipo' => $tipo
    ]);
    
} catch (Exception $e) {
    // Respuesta de error
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage(),
        'tipo' => isset($tipo) ? $tipo : 'desconocido'
    ]);
}

mysqli_close($conexion);
?>
