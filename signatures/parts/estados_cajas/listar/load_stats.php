<?php
/**
 * Archivo para cargar estadísticas de estados de cajas via AJAX
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
    
    switch ($tipo) {
        case 'total_sucursales':
            // Total de sucursales en la tabla sucursal
            $query = "SELECT COUNT(*) as total FROM sucursal";
            break;
            
        case 'cajas_abiertas':
            // Cajas con caja_cerrada = 'false'
            $query = "SELECT COUNT(*) as total FROM sucursal WHERE caja_cerrada = 'false'";
            break;
            
        case 'cajas_cerradas':
            // Cajas con caja_cerrada = 'true'
            $query = "SELECT COUNT(*) as total FROM sucursal WHERE caja_cerrada = 'true'";
            break;
            
        default:
            throw new Exception("Tipo de consulta no válido: " . $tipo);
    }
    
    // Ejecutar consulta
    $result = mysqli_query($conexion, $query);
    
    if (!$result) {
        throw new Exception("Error en consulta: " . mysqli_error($conexion));
    }
    
    // Obtener resultado
    $row = mysqli_fetch_assoc($result);
    $total = (int)$row['total'];
    
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
