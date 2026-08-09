<?php
/**
 * Archivo para obtener datos de un proveedor específico
 * Compatible con PHP 7.0
 */

require_once '../../../include/session.php';
require_once '../../../include/functions.php';

header('Content-Type: application/json');

try {
    // Verificar que sea una petición GET
    if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
        http_response_code(405);
        echo json_encode(array('error' => 'Método no permitido'));
        exit;
    }
    
    // Obtener ID del proveedor
    $id_proveedor = isset($_GET['id']) ? (int)$_GET['id'] : 0;
    
    if (!$id_proveedor) {
        http_response_code(400);
        echo json_encode(array('error' => 'ID de proveedor no válido'));
        exit;
    }
    
    // Conectar BD
    $conexion = conectar_bd();
    
    // Consulta para obtener datos del proveedor
    $query_proveedor = "
        SELECT 
            id_proveedor,
            nombre_proveedor,
            cif_proveedor,
            direccion_proveedor,
            poblacion_proveedor,
            provincia_proveedor,
            telefono_proveedor,
            codigo_postal_proveedor,
            pais_proveedor,
            email_proveedor,
            moneda_proveedor,
            forma_pago_proveedor,
            fundicion,
            fundicion_multi_kilates
        FROM proveedores
        WHERE id_proveedor = ?
    ";
    
    $stmt_proveedor = mysqli_prepare($conexion, $query_proveedor);
    mysqli_stmt_bind_param($stmt_proveedor, 'i', $id_proveedor);
    mysqli_stmt_execute($stmt_proveedor);
    $result_proveedor = mysqli_stmt_get_result($stmt_proveedor);
    
    if ($result_proveedor && mysqli_num_rows($result_proveedor) > 0) {
        $proveedor = mysqli_fetch_assoc($result_proveedor);
        mysqli_stmt_close($stmt_proveedor);
        
        // Respuesta exitosa
        echo json_encode(array(
            'success' => true,
            'proveedor' => $proveedor
        ));
    } else {
        // Proveedor no encontrado
        http_response_code(404);
        echo json_encode(array(
            'success' => false,
            'error' => 'Proveedor no encontrado'
        ));
    }
    
    mysqli_close($conexion);
    
} catch (Exception $e) {
    // Error del sistema
    http_response_code(500);
    echo json_encode(array(
        'success' => false,
        'error' => 'Error interno del servidor: ' . $e->getMessage()
    ));
} catch (Error $e) {
    // Error fatal
    http_response_code(500);
    echo json_encode(array(
        'success' => false,
        'error' => 'Error fatal del sistema'
    ));
}
?>
