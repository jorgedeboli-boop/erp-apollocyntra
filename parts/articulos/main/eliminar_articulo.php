<?php
require_once '../../../include/session.php';

header('Content-Type: application/json');

try {
    // Verificar que sea una petición POST
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        http_response_code(405);
        echo json_encode(array('success' => false, 'error' => 'Método no permitido'));
        exit;
    }
    
    // Validar que se haya proporcionado el ID del artículo
    if (!isset($_POST['id_articulo']) || empty($_POST['id_articulo'])) {
        http_response_code(400);
        echo json_encode(array('success' => false, 'error' => 'ID de artículo es requerido'));
        exit;
    }
    
    $id_articulo = (int)$_POST['id_articulo'];
    
    // Conectar BD
    $conexion = conectar_bd();
    
    // Verificar que el artículo existe y está en estado 'mermado'
    $query_check = "SELECT id, estado FROM articulos_venta WHERE id = ? LIMIT 1";
    $stmt_check = mysqli_prepare($conexion, $query_check);
    mysqli_stmt_bind_param($stmt_check, 'i', $id_articulo);
    mysqli_stmt_execute($stmt_check);
    $result_check = mysqli_stmt_get_result($stmt_check);
    
    if (mysqli_num_rows($result_check) == 0) {
        mysqli_stmt_close($stmt_check);
        mysqli_close($conexion);
        http_response_code(404);
        echo json_encode(array('success' => false, 'error' => 'El artículo no existe'));
        exit;
    }
    
    $articulo = mysqli_fetch_assoc($result_check);
    mysqli_stmt_close($stmt_check);
    
    // Verificar que el estado sea 'mermado'
    if (strtolower($articulo['estado']) !== 'mermado') {
        mysqli_close($conexion);
        http_response_code(400);
        echo json_encode(array('success' => false, 'error' => 'Solo se pueden eliminar artículos en estado mermado'));
        exit;
    }
    
    // Iniciar transacción
    mysqli_begin_transaction($conexion);
    
    try {
        // Eliminar el artículo
        $query_delete = "DELETE FROM articulos_venta WHERE id = ?";
        $stmt_delete = mysqli_prepare($conexion, $query_delete);
        
        if (!$stmt_delete) {
            throw new Exception("Error al preparar consulta: " . mysqli_error($conexion));
        }
        
        mysqli_stmt_bind_param($stmt_delete, 'i', $id_articulo);
        
        if (!mysqli_stmt_execute($stmt_delete)) {
            throw new Exception("Error al eliminar artículo: " . mysqli_stmt_error($stmt_delete));
        }
        
        mysqli_stmt_close($stmt_delete);
        
        // Confirmar transacción
        mysqli_commit($conexion);
        
        // Respuesta exitosa
        echo json_encode(array(
            'success' => true,
            'message' => 'Artículo eliminado correctamente'
        ));
        
    } catch (Exception $e) {
        // Rollback en caso de error
        mysqli_rollback($conexion);
        
        http_response_code(500);
        echo json_encode(array(
            'success' => false,
            'error' => $e->getMessage()
        ));
    }
    
    mysqli_close($conexion);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(array(
        'success' => false,
        'error' => $e->getMessage()
    ));
}
?>
