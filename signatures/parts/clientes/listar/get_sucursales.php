<?php
/**
 * Archivo para obtener todas las sucursales disponibles
 * Usado para el filtro de sucursal en la lista de clientes
 */

require_once '../../../include/session.php';
require_once '../../../include/functions.php';

header('Content-Type: application/json');

try {
    // Conectar BD
    $conexion = conectar_bd();
    
    // Consulta para obtener todas las sucursales habilitadas
    $query = "SELECT id_sucursal, nombre_sucursal FROM sucursal WHERE estado_tienda = 'habilitada' ORDER BY nombre_sucursal ASC";
    
    $result = mysqli_query($conexion, $query);
    
    if (!$result) {
        throw new Exception("Error en consulta: " . mysqli_error($conexion));
    }
    
    $sucursales = [];
    while ($row = mysqli_fetch_assoc($result)) {
        $sucursales[] = [
            'id_sucursal' => $row['id_sucursal'],
            'nombre_sucursal' => $row['nombre_sucursal']
        ];
    }
    
    mysqli_close($conexion);
    
    // Respuesta exitosa
    echo json_encode([
        'success' => true,
        'sucursales' => $sucursales,
        'total' => count($sucursales)
    ]);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
?>
