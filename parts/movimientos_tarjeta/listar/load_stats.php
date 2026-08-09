<?php
/**
 * Archivo para cargar estadísticas de movimientos tarjeta via AJAX
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
    $total = 0;
    
    // Obtener filtros
    $filtroSucursal = isset($_POST['filtro_sucursal']) ? trim($_POST['filtro_sucursal']) : '';
    $filtroFechaDesde = isset($_POST['filtro_fecha_desde']) ? trim($_POST['filtro_fecha_desde']) : '';
    $filtroFechaHasta = isset($_POST['filtro_fecha_hasta']) ? trim($_POST['filtro_fecha_hasta']) : '';
    $filtroPeriodo = isset($_POST['filtro_periodo']) ? trim($_POST['filtro_periodo']) : 'dia';
    
    // Construir condiciones WHERE
    $whereConditions = [];
    $searchParams = [];
    
    $filtroSucursalId = $filtroSucursal !== '' ? (int) $filtroSucursal : 0;
    if ($filtroSucursalId > 0) {
        $whereConditions[] = "mt.sucursal = ?";
        $searchParams[] = $filtroSucursalId;
    }
    
    // Filtro por fecha
    if (!empty($filtroFechaDesde) && !empty($filtroFechaHasta)) {
        $whereConditions[] = "mt.fecha BETWEEN ? AND DATE_ADD(?, INTERVAL 1 DAY)";
        array_push($searchParams, $filtroFechaDesde, $filtroFechaHasta);
    } else if (!empty($filtroFechaDesde)) {
        $whereConditions[] = "mt.fecha >= ?";
        $searchParams[] = $filtroFechaDesde;
    } else if (!empty($filtroFechaHasta)) {
        $whereConditions[] = "mt.fecha <= DATE_ADD(?, INTERVAL 1 DAY)";
        $searchParams[] = $filtroFechaHasta;
    } else if ($filtroPeriodo === 'hoy' || $filtroPeriodo === 'dia') {
        $whereConditions[] = "DATE(mt.fecha) = CURDATE()";
    } else if ($filtroPeriodo === 'mes') {
        $whereConditions[] = "YEAR(mt.fecha) = YEAR(CURDATE()) AND MONTH(mt.fecha) = MONTH(CURDATE())";
    }
    
    $whereClause = '';
    if (!empty($whereConditions)) {
        $whereClause = ' WHERE ' . implode(' AND ', $whereConditions);
    }
    
    switch ($tipo) {
        case 'total_entradas':
            $query = "SELECT COALESCE(SUM(mt.importe), 0) as total 
                     FROM movimientos_tarjeta mt
                     LEFT JOIN sucursal s ON mt.sucursal = s.id_sucursal
                     $whereClause";
            break;
            
        case 'total_salidas':
            // Tarjeta no tiene salidas, siempre es 0
            $total = 0;
            echo json_encode([
                'success' => true,
                'total' => $total,
                'tipo' => $tipo
            ]);
            exit;
            break;
            
        case 'total_saldo':
            // Solo entradas para tarjeta
            $query = "SELECT COALESCE(SUM(mt.importe), 0) as total 
                     FROM movimientos_tarjeta mt
                     LEFT JOIN sucursal s ON mt.sucursal = s.id_sucursal
                     $whereClause";
            break;
            
        default:
            throw new Exception("Tipo de consulta no válido: " . $tipo);
    }
    
    // Ejecutar consulta
    if (!empty($searchParams)) {
        $stmt = mysqli_prepare($conexion, $query);
        $types = str_repeat('s', count($searchParams));
        mysqli_stmt_bind_param($stmt, $types, ...$searchParams);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        
        if (!$result) {
            throw new Exception("Error en consulta: " . mysqli_error($conexion));
        }
        
        $row = mysqli_fetch_assoc($result);
        $total = (float)$row['total'];
        mysqli_stmt_close($stmt);
    } else {
        $result = mysqli_query($conexion, $query);
        
        if (!$result) {
            throw new Exception("Error en consulta: " . mysqli_error($conexion));
        }
        
        $row = mysqli_fetch_assoc($result);
        $total = (float)$row['total'];
    }
    
    // Respuesta exitosa
    echo json_encode([
        'success' => true,
        'total' => $total,
        'tipo' => $tipo
    ]);
    
} catch (Exception $e) {
    // Respuesta de error
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage(),
        'tipo' => $tipo ?? 'desconocido'
    ]);
} finally {
    if (isset($conexion)) {
        mysqli_close($conexion);
    }
}
?>

