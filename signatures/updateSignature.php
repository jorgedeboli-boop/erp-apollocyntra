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
    if (!isset($_POST['svgFile']) || !isset($_POST['idItem']) || !isset($_POST['typeItem']) || !isset($_POST['id_signature'])) {
        throw new Exception('Parámetros incompletos');
    }
    
    $signature_value = $_POST['svgFile'];
    $typeItem = trim($_POST['typeItem']);
    $idItem = (int)$_POST['idItem'];
    $id_signature = (int)$_POST['id_signature'];
    
    // Validar que signature_value no esté vacío
    if (empty($signature_value)) {
        throw new Exception('El valor de la firma no puede estar vacío');
    }
    
    // Conectar BD
    $conexion = conectar_bd();
    
    // Actualizar firma con el valor SVG y cambiar estado a 'true'
    $query = "UPDATE Signatures 
              SET signature_value = ?, 
                  state_signature = 'true' 
              WHERE id_signature = ?";
    
    $stmt = mysqli_prepare($conexion, $query);
    
    if (!$stmt) {
        throw new Exception('Error al preparar consulta: ' . mysqli_error($conexion));
    }
    
    mysqli_stmt_bind_param($stmt, 'si', $signature_value, $id_signature);
    
    if (!mysqli_stmt_execute($stmt)) {
        throw new Exception('Error al actualizar firma: ' . mysqli_stmt_error($stmt));
    }
    
    mysqli_stmt_close($stmt);
    mysqli_close($conexion);
    
    // Respuesta exitosa
    echo json_encode(array(
        'success' => true,
        'status' => 'ok',
        'idSignature' => $id_signature,
        'message' => 'Firma actualizada correctamente'
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