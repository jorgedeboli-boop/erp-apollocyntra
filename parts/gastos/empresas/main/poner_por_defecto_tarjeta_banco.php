<?php
require_once '../../../include/session.php';
require_once '../../../include/functions.php';

// Headers para JSON
header('Content-Type: application/json');
header('Cache-Control: no-cache, must-revalidate');

try {
    // Verificar permisos
    if (!puede_acceder_a('empresas')) {
        http_response_code(403);
        echo json_encode(array('success' => false, 'message' => 'Acceso denegado'));
        exit;
    }
    
    // Verificar método POST
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        http_response_code(405);
        echo json_encode(array('success' => false, 'message' => 'Método no permitido'));
        exit;
    }
    
    // Obtener datos del POST
    $id_tarjeta_banco = isset($_POST['id_tarjeta_banco']) ? (int)$_POST['id_tarjeta_banco'] : 0;
    $id_empresa = isset($_POST['id_empresa']) ? (int)$_POST['id_empresa'] : 0;
    
    if (!$id_tarjeta_banco || !$id_empresa) {
        echo json_encode(array('success' => false, 'message' => 'Parámetros inválidos'));
        exit;
    }
    
    // Conectar a la base de datos
    $conexion = conectar_bd();
    
    // Verificar que la tarjeta pertenece a la empresa
    $query_verificar = "SELECT empresa_tarjeta_id FROM tarjetas_banco_empresas WHERE id_tarjeta_banco = ?";
    $stmt_verificar = mysqli_prepare($conexion, $query_verificar);
    mysqli_stmt_bind_param($stmt_verificar, 'i', $id_tarjeta_banco);
    mysqli_stmt_execute($stmt_verificar);
    $result_verificar = mysqli_stmt_get_result($stmt_verificar);
    
    if (!$result_verificar || mysqli_num_rows($result_verificar) === 0) {
        mysqli_stmt_close($stmt_verificar);
        mysqli_close($conexion);
        echo json_encode(array('success' => false, 'message' => 'Tarjeta banco no encontrada'));
        exit;
    }
    
    $row_verificar = mysqli_fetch_assoc($result_verificar);
    if ($row_verificar['empresa_tarjeta_id'] != $id_empresa) {
        mysqli_stmt_close($stmt_verificar);
        mysqli_close($conexion);
        echo json_encode(array('success' => false, 'message' => 'La tarjeta no pertenece a esta empresa'));
        exit;
    }
    
    mysqli_stmt_close($stmt_verificar);
    
    // Primero, quitar el flag de por defecto de todas las tarjetas de la empresa
    $query_reset = "UPDATE tarjetas_banco_empresas SET por_defecto = 'false' WHERE empresa_tarjeta_id = ?";
    $stmt_reset = mysqli_prepare($conexion, $query_reset);
    mysqli_stmt_bind_param($stmt_reset, 'i', $id_empresa);
    mysqli_stmt_execute($stmt_reset);
    
    // Ahora marcar la tarjeta seleccionada como por defecto
    $query_update = "UPDATE tarjetas_banco_empresas SET por_defecto = 'true' WHERE id_tarjeta_banco = ?";
    $stmt_update = mysqli_prepare($conexion, $query_update);
    mysqli_stmt_bind_param($stmt_update, 'i', $id_tarjeta_banco);
    mysqli_stmt_execute($stmt_update);
    
    mysqli_stmt_close($stmt_reset);
    mysqli_stmt_close($stmt_update);
    mysqli_close($conexion);
    
    echo json_encode(array(
        'success' => true,
        'message' => 'Tarjeta banco marcada como por defecto correctamente'
    ));
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(array(
        'success' => false,
        'message' => 'Error interno del servidor: ' . $e->getMessage()
    ));
}
?>
