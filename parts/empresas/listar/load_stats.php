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
    
    // Total de empresas
    $query_total = "SELECT COUNT(*) as total FROM empresas";
    $result_total = mysqli_query($conexion, $query_total);
    $total_empresas = mysqli_fetch_assoc($result_total)['total'];
    
    // Empresas activas (creadas en los últimos 12 meses)
    $query_activas = "SELECT COUNT(*) as total FROM empresas WHERE fecha_creacion_empresa >= DATE_SUB(CURDATE(), INTERVAL 12 MONTH)";
    $result_activas = mysqli_query($conexion, $query_activas);
    $total_activas = mysqli_fetch_assoc($result_activas)['total'];
    
    // Nuevas empresas este mes
    $query_nuevas = "SELECT COUNT(*) as total FROM empresas WHERE fecha_creacion_empresa >= DATE_FORMAT(CURDATE(), '%Y-%m-01')";
    $result_nuevas = mysqli_query($conexion, $query_nuevas);
    $total_nuevas = mysqli_fetch_assoc($result_nuevas)['total'];
    
    $response = [
        'success' => true,
        'total_empresas' => $total_empresas,
        'total_activas' => $total_activas,
        'total_nuevas' => $total_nuevas
    ];
    
    echo json_encode($response);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}

if (isset($conexion)) {
    mysqli_close($conexion);
}
?>
