<?php
require_once '../../../include/session.php';
require_once '../../../include/functions.php';

// Headers para JSON
header('Content-Type: application/json');
header('Cache-Control: no-cache, must-revalidate');

try {
    // Verificar que el usuario sea usuario_root
    if (!isset($_SESSION['usuario_root']) || $_SESSION['usuario_root'] !== true) {
        http_response_code(403);
        echo json_encode(array('success' => false, 'message' => 'Solo los usuarios administradores pueden eliminar idiomas'));
        exit;
    }
    
    // Verificar método POST
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        http_response_code(405);
        echo json_encode(array('success' => false, 'message' => 'Método no permitido'));
        exit;
    }
    
    // Obtener ID del idioma
    $id_idioma = isset($_POST['id']) ? (int)$_POST['id'] : 0;
    
    if (!$id_idioma) {
        echo json_encode(array('success' => false, 'message' => 'ID de idioma no válido'));
        exit;
    }
    
    // Conectar a la base de datos
    $conexion = conectar_bd();
    
    // Verificar que el idioma existe
    $query_verificar = "SELECT description FROM Languages WHERE id_lang = ?";
    $stmt_verificar = mysqli_prepare($conexion, $query_verificar);
    mysqli_stmt_bind_param($stmt_verificar, 'i', $id_idioma);
    mysqli_stmt_execute($stmt_verificar);
    $result_verificar = mysqli_stmt_get_result($stmt_verificar);
    
    if (!$result_verificar || mysqli_num_rows($result_verificar) === 0) {
        mysqli_stmt_close($stmt_verificar);
        mysqli_close($conexion);
        echo json_encode(array('success' => false, 'message' => 'Idioma no encontrado'));
        exit;
    }
    
    $idioma = mysqli_fetch_assoc($result_verificar);
    mysqli_stmt_close($stmt_verificar);
    
    // Iniciar transacción
    mysqli_begin_transaction($conexion);
    
    try {
        // Eliminar el idioma
        $query_eliminar_idioma = "DELETE FROM Languages WHERE id_lang = ?";
        $stmt_eliminar_idioma = mysqli_prepare($conexion, $query_eliminar_idioma);
        mysqli_stmt_bind_param($stmt_eliminar_idioma, 'i', $id_idioma);
        mysqli_stmt_execute($stmt_eliminar_idioma);
        mysqli_stmt_close($stmt_eliminar_idioma);
        
        // Confirmar transacción
        mysqli_commit($conexion);
        
        echo json_encode(array(
            'success' => true,
            'message' => 'Idioma "' . $idioma['description'] . '" eliminado correctamente'
        ));
        
    } catch (Exception $e) {
        // Revertir transacción en caso de error
        mysqli_rollback($conexion);
        throw $e;
    }
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(array(
        'success' => false,
        'message' => 'Error interno del servidor: ' . $e->getMessage()
    ));
} finally {
    if (isset($conexion)) {
        mysqli_close($conexion);
    }
}
?>
