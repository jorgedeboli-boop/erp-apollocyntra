<?php
/**
 * Archivo para obtener datos de un cliente específico para edición
 */

require_once '../../../include/session.php';
require_once '../../../include/functions.php';

header('Content-Type: application/json');

try {
    // Log para debug
    error_log("get_cliente.php - Método: " . $_SERVER['REQUEST_METHOD']);
    error_log("get_cliente.php - POST data: " . print_r($_POST, true));
    
    // Verificar que sea una petición POST
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        http_response_code(405);
        echo json_encode(array('error' => 'Método no permitido'));
        exit;
    }
    
    // Obtener ID del cliente
    $id_cliente = isset($_POST['id_cliente']) ? (int)$_POST['id_cliente'] : 0;
    
    error_log("get_cliente.php - ID cliente: " . $id_cliente);
    
    if (!$id_cliente) {
        throw new Exception('ID de cliente no válido');
    }
    
    // Conectar BD
    $conexion = conectar_bd();
    
    // Consulta para obtener datos del cliente
    $query_cliente = "
        SELECT 
            c.id_cliente,
            c.nombre,
            c.apellido,
            c.tipo_identificacion,
            c.identificacion,
            c.nacionalidad,
            c.f_nacimiento,
            c.telefono,
            c.f_alta,
            c.f_vencimiento
        FROM clientes c
        WHERE c.id_cliente = ?
    ";
    
    $stmt_cliente = mysqli_prepare($conexion, $query_cliente);
    mysqli_stmt_bind_param($stmt_cliente, 'i', $id_cliente);
    mysqli_stmt_execute($stmt_cliente);
    $result_cliente = mysqli_stmt_get_result($stmt_cliente);
    
    if (!$result_cliente || mysqli_num_rows($result_cliente) === 0) {
        throw new Exception('Cliente no encontrado');
    }
    
    $cliente = mysqli_fetch_assoc($result_cliente);
    mysqli_stmt_close($stmt_cliente);
    
    // Consulta para obtener datos adicionales del cliente
    $query_datos = "
        SELECT 
            dc.direccion,
            dc.c_provincia,
            dc.c_poblacion,
            dc.codigo_postal,
            dc.email,
            dc.observaciones,
            dc.sexo
        FROM datos_clientes dc
        WHERE dc.rel_id_cliente = ?
    ";
    
    $stmt_datos = mysqli_prepare($conexion, $query_datos);
    mysqli_stmt_bind_param($stmt_datos, 'i', $id_cliente);
    mysqli_stmt_execute($stmt_datos);
    $result_datos = mysqli_stmt_get_result($stmt_datos);
    
    $datos_cliente = null;
    if ($result_datos && mysqli_num_rows($result_datos) > 0) {
        $datos_cliente = mysqli_fetch_assoc($result_datos);
    }
    mysqli_stmt_close($stmt_datos);
    
    // Combinar datos
    $cliente['datos_cliente'] = $datos_cliente;
    
    // Log para debug
    error_log("get_cliente.php - Datos del cliente: " . print_r($cliente, true));
    
    // Respuesta de éxito
    error_log("get_cliente.php - Enviando respuesta exitosa");
    echo json_encode(array(
        'success' => true,
        'cliente' => $cliente
    ));
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(array(
        'success' => false,
        'error' => $e->getMessage()
    ));
} finally {
    if (isset($conexion)) {
        mysqli_close($conexion);
    }
}
?>
