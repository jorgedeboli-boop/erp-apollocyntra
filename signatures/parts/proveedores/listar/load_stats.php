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
    
    // Total de proveedores
    $query_total = "SELECT COUNT(*) as total FROM proveedores";
    $result_total = mysqli_query($conexion, $query_total);
    $total_proveedores = mysqli_fetch_assoc($result_total)['total'];
    
    // Proveedores con fundición
    $query_fundicion = "SELECT COUNT(*) as total FROM proveedores WHERE fundicion = 'true'";
    $result_fundicion = mysqli_query($conexion, $query_fundicion);
    $total_fundicion = mysqli_fetch_assoc($result_fundicion)['total'];
    
    // Nuevos proveedores este mes
    $query_nuevos = "SELECT COUNT(*) as total FROM proveedores WHERE fecha_creacion_proveedor >= DATE_FORMAT(CURDATE(), '%Y-%m-01')";
    $result_nuevos = mysqli_query($conexion, $query_nuevos);
    $total_nuevos = mysqli_fetch_assoc($result_nuevos)['total'];
    
    $response = [
        'success' => true,
        'total_proveedores' => $total_proveedores,
        'total_fundicion' => $total_fundicion,
        'total_nuevos' => $total_nuevos
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
