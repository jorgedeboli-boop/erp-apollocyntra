<?php
require_once '../../../include/session.php';
require_once '../../../include/functions.php';

// Verificar que el usuario esté autenticado
if (!isset($_SESSION['usuario_id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'No autorizado']);
    exit;
}

try {
    $conexion = conectar_bd();
    
    if (!$conexion) {
        throw new Exception('Error de conexión a la base de datos');
    }
    
    // Obtener estadísticas
    $stats = [];
    
    // Total de tipos de gasto
    $query_total = "SELECT COUNT(*) as total FROM tipo_de_gasto";
    $result_total = mysqli_query($conexion, $query_total);
    if ($result_total) {
        $stats['total_tipos'] = mysqli_fetch_assoc($result_total)['total'];
        mysqli_free_result($result_total);
    } else {
        $stats['total_tipos'] = 0;
    }
    
    // Total de tipos activos (todos están activos por defecto)
    $stats['total_activos'] = $stats['total_tipos'];
    
    // Fecha del último tipo creado
    $query_fecha = "SELECT fecha_alta FROM tipo_de_gasto ORDER BY fecha_alta DESC LIMIT 1";
    $result_fecha = mysqli_query($conexion, $query_fecha);
    if ($result_fecha && mysqli_num_rows($result_fecha) > 0) {
        $stats['fecha_ultimo'] = mysqli_fetch_assoc($result_fecha)['fecha_alta'];
        mysqli_free_result($result_fecha);
    } else {
        $stats['fecha_ultimo'] = null;
    }
    
    mysqli_close($conexion);
    
    // Respuesta exitosa
    echo json_encode([
        'success' => true,
        'stats' => $stats
    ]);
    
} catch (Exception $e) {
    error_log("Error en load_stats tipos de gasto: " . $e->getMessage());
    
    if (isset($conexion)) {
        mysqli_close($conexion);
    }
    
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Error del servidor: ' . $e->getMessage()
    ]);
}
?>
