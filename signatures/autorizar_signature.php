<?php
require_once '../include/session.php';
require_once '../include/functions.php';

ob_start();
ob_clean();

header('Content-Type: application/json');

try {
    // Verificar que sea una petición POST
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception('Método no permitido');
    }
    
    // Validar parámetros
    if (!isset($_POST['id_signature']) || empty($_POST['id_signature'])) {
        throw new Exception('ID de firma no proporcionado');
    }
    
    $id_signature = (int)$_POST['id_signature'];
    
    // Conectar BD
    $conexion = conectar_bd();
    
    // Actualizar firma
    $query = "UPDATE Signatures SET auth_no_signature = 'true', user_auth_no_signature = ? WHERE id_signature = ?";
    $stmt = mysqli_prepare($conexion, $query);
    
    if (!$stmt) {
        throw new Exception('Error al preparar consulta: ' . mysqli_error($conexion));
    }
    
    mysqli_stmt_bind_param($stmt, 'ii', $usuario_id, $id_signature);
    
    if (!mysqli_stmt_execute($stmt)) {
        throw new Exception('Error al autorizar firma: ' . mysqli_stmt_error($stmt));
    }
    
    mysqli_stmt_close($stmt);
    mysqli_close($conexion);
    
    // Respuesta exitosa
    echo json_encode(array(
        'success' => true,
        'status' => 'ok',
        'message' => 'Firma autorizada correctamente'
    ));
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(array(
        'success' => false,
        'status' => 'error',
        'error_desc' => 'ko',
        'message' => $e->getMessage()
    ));
}
?>