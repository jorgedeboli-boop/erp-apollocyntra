<?php
/**
 * Archivo para cargar estadísticas de movimientos de caja via AJAX
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
    $filtroGrupo = isset($_POST['filtro_grupo']) ? trim($_POST['filtro_grupo']) : '';
    $filtroFechaDesde = isset($_POST['filtro_fecha_desde']) ? trim($_POST['filtro_fecha_desde']) : '';
    $filtroFechaHasta = isset($_POST['filtro_fecha_hasta']) ? trim($_POST['filtro_fecha_hasta']) : '';
    $filtroPeriodo = isset($_POST['filtro_periodo']) ? trim($_POST['filtro_periodo']) : 'dia';
    
    $tablasCaja = [];
    $resultTablas = mysqli_query($conexion, "SHOW TABLES LIKE 'movimientos_de_caja_%'");
    if ($resultTablas) {
        while ($rowTabla = mysqli_fetch_row($resultTablas)) {
            if (preg_match('/^movimientos_de_caja_\d+$/', $rowTabla[0])) {
                $tablasCaja[] = $rowTabla[0];
            }
        }
    }
    
    // Construir condiciones WHERE para la fecha
    $whereConditions = [];
    $searchParams = [];
    
    // Filtro por grupo
    if (!empty($filtroGrupo)) {
        $whereConditions[] = "grupos = ?";
        $searchParams[] = $filtroGrupo;
    }
    
    // Filtro por fecha
    if (!empty($filtroFechaDesde) && !empty($filtroFechaHasta)) {
        $whereConditions[] = "fecha_apunte BETWEEN ? AND ?";
        array_push($searchParams, $filtroFechaDesde, $filtroFechaHasta);
    } else if (!empty($filtroFechaDesde)) {
        $whereConditions[] = "fecha_apunte >= ?";
        $searchParams[] = $filtroFechaDesde;
    } else if (!empty($filtroFechaHasta)) {
        $whereConditions[] = "fecha_apunte <= ?";
        $searchParams[] = $filtroFechaHasta;
    } else if ($filtroPeriodo === 'hoy' || $filtroPeriodo === 'dia') {
        $whereConditions[] = "fecha_apunte = CURDATE()";
    } else if ($filtroPeriodo === 'mes') {
        $whereConditions[] = "YEAR(fecha_apunte) = YEAR(CURDATE()) AND MONTH(fecha_apunte) = MONTH(CURDATE())";
    }
    
    $whereClause = '';
    if (!empty($whereConditions)) {
        $whereClause = ' WHERE ' . implode(' AND ', $whereConditions);
    }
    
    // Recorrer todas las tablas de caja y sumar los resultados
    $total = 0;
    
    foreach ($tablasCaja as $tableName) {
        
        switch ($tipo) {
            case 'total_entradas':
                $query = "SELECT COALESCE(SUM(entrada), 0) as total FROM $tableName $whereClause";
                break;
                
            case 'total_salidas':
                $query = "SELECT COALESCE(SUM(salida), 0) as total FROM $tableName $whereClause";
                break;
                
            case 'total_saldo':
                $query = "SELECT COALESCE(SUM(entrada), 0) - COALESCE(SUM(salida), 0) as total FROM $tableName $whereClause";
                break;
                
            default:
                throw new Exception("Tipo de consulta no válido: " . $tipo);
        }
        
        // Ejecutar consulta
        if (!empty($searchParams)) {
            $stmt = mysqli_prepare($conexion, $query);
            if ($stmt) {
                $types = str_repeat('s', count($searchParams));
                mysqli_stmt_bind_param($stmt, $types, ...$searchParams);
                mysqli_stmt_execute($stmt);
                $result = mysqli_stmt_get_result($stmt);
                
                if ($result) {
                    $row = mysqli_fetch_assoc($result);
                    $total += (float)$row['total'];
                }
                mysqli_stmt_close($stmt);
            }
        } else {
            $result = mysqli_query($conexion, $query);
            if ($result) {
                $row = mysqli_fetch_assoc($result);
                $total += (float)$row['total'];
            }
        }
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
