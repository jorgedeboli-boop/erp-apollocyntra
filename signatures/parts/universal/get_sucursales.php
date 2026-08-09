<?php
/**
 * Archivo para obtener la lista de sucursales via AJAX
 * Utilizado por los filtros de diferentes módulos
 */

require_once '../../include/session.php';
require_once '../../include/functions.php';

header('Content-Type: application/json');

try {
    // Verificar permisos básicos (cualquier usuario autenticado puede ver sucursales)
    if (!isset($_SESSION['usuario_id'])) {
        http_response_code(401);
        echo json_encode(['success' => false, 'error' => 'No autorizado']);
        exit;
    }
    
    $conexion = conectar_bd();
    
    if (!$conexion) {
        throw new Exception('Error de conexión a la base de datos');
    }
    
    // Consulta para obtener sucursales activas
    $query = "SELECT id_sucursal, nombre_sucursal 
              FROM sucursal 
              ORDER BY nombre_sucursal ASC";
    
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
    // Respuesta de error
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
} finally {
    if (isset($conexion)) {
        mysqli_close($conexion);
    }
}
?>
