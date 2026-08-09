<?php
/**
 * Archivo para cargar estadísticas de clientes via AJAX
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
        case 'total_clientes':
            // Total de clientes en la tabla clientes
            $query = "SELECT COUNT(*) as total FROM clientes";
            break;
            
        case 'clientes_habilitados':
            // Clientes con estado = 'habilitado'
            $query = "SELECT COUNT(*) as total FROM clientes WHERE estado = 'habilitado'";
            break;
            
        case 'clientes_lista_negra':
            // Clientes con estado = 'deshabilitado'
            $query = "SELECT COUNT(*) as total FROM clientes WHERE estado = 'deshabilitado'";
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
