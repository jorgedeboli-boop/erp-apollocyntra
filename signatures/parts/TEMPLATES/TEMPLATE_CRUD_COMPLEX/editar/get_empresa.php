<?php
/**
 * Archivo para obtener datos de una gasto específica
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
    
    // Obtener ID de la gasto
    $id_gasto = isset($_GET['id']) ? (int)$_GET['id'] : 0;
    
    if (!$id_gasto) {
        http_response_code(400);
        echo json_encode(array('error' => 'ID de gasto no válido'));
        exit;
    }
    
    // Conectar BD
    $conexion = conectar_bd();
    
    // Consulta para obtener datos de la gasto
    $query_gasto = "
        SELECT 
            id_gasto,
            nombre_gasto,
            cif_gasto,
            direccion_gasto,
            poblacion_gasto,
            provincia_gasto,
            telefono_gasto,
            codigo_postal_gasto,
            pais_gasto,
            email_gasto,
            texto_facturas,
            texto_contrato_empeno,
            texto_contrato_compra,
            webgasto
        FROM gastos
        WHERE id_gasto = ?
    ";
    
    $stmt_gasto = mysqli_prepare($conexion, $query_gasto);
    mysqli_stmt_bind_param($stmt_gasto, 'i', $id_gasto);
    mysqli_stmt_execute($stmt_gasto);
    $result_gasto = mysqli_stmt_get_result($stmt_gasto);
    
    if ($result_gasto && mysqli_num_rows($result_gasto) > 0) {
        $gasto = mysqli_fetch_assoc($result_gasto);
        mysqli_stmt_close($stmt_gasto);
        
        // Respuesta exitosa
        echo json_encode(array(
            'success' => true,
            'gasto' => $gasto
        ));
    } else {
        // Empresa no encontrada
        http_response_code(404);
        echo json_encode(array(
            'success' => false,
            'error' => 'Empresa no encontrada'
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
