<?php
/**
 * Archivo para obtener datos de una empresa específica
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
    
    // Obtener ID de la empresa
    $id_empresa = isset($_GET['id']) ? (int)$_GET['id'] : 0;
    
    if (!$id_empresa) {
        http_response_code(400);
        echo json_encode(array('error' => 'ID de empresa no válido'));
        exit;
    }
    
    // Conectar BD
    $conexion = conectar_bd();
    
    // Consulta para obtener datos de la empresa
    $query_empresa = "
        SELECT 
            id_empresa,
            nombre_empresa,
            cif_empresa,
            direccion_empresa,
            poblacion_empresa,
            provincia_empresa,
            telefono_empresa,
            codigo_postal_empresa,
            pais_empresa,
            email_empresa,
            texto_facturas,
            texto_contrato_empeno,
            texto_contrato_compra,
            webempresa
        FROM empresas
        WHERE id_empresa = ?
    ";
    
    $stmt_empresa = mysqli_prepare($conexion, $query_empresa);
    mysqli_stmt_bind_param($stmt_empresa, 'i', $id_empresa);
    mysqli_stmt_execute($stmt_empresa);
    $result_empresa = mysqli_stmt_get_result($stmt_empresa);
    
    if ($result_empresa && mysqli_num_rows($result_empresa) > 0) {
        $empresa = mysqli_fetch_assoc($result_empresa);
        mysqli_stmt_close($stmt_empresa);
        
        // Respuesta exitosa
        echo json_encode(array(
            'success' => true,
            'empresa' => $empresa
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
