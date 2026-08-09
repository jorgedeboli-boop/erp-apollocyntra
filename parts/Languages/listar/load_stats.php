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
    
    // Total de idiomas
    $query_total = "SELECT COUNT(*) as total FROM Languages";
    $result_total = mysqli_query($conexion, $query_total);
    $total_idiomas = mysqli_fetch_assoc($result_total)['total'];
    
    // Idiomas activos
    $query_activos = "SELECT COUNT(*) as total FROM Languages WHERE stateLang = 'true'";
    $result_activos = mysqli_query($conexion, $query_activos);
    $total_activos = mysqli_fetch_assoc($result_activos)['total'];
    
    // Idiomas inactivos
    $query_inactivos = "SELECT COUNT(*) as total FROM Languages WHERE stateLang = 'false'";
    $result_inactivos = mysqli_query($conexion, $query_inactivos);
    $total_inactivos = mysqli_fetch_assoc($result_inactivos)['total'];
    
    $response = [
        'success' => true,
        'total_idiomas' => $total_idiomas,
        'total_activos' => $total_activos,
        'total_inactivos' => $total_inactivos
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
