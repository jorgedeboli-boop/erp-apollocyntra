<?php
require_once '../../../include/session.php';
require_once '../../../include/functions.php';

header('Content-Type: application/json');

try {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        echo json_encode(['success' => false, 'message' => 'Método no permitido']);
        exit;
    }
    
    if (!puede_acceder_a('empresas')) {
        echo json_encode(['success' => false, 'message' => 'Acceso denegado']);
        exit;
    }
    
    $id_tarjeta_banco = isset($_POST['id_tarjeta_banco']) ? (int)$_POST['id_tarjeta_banco'] : 0;
    
    if (!$id_tarjeta_banco) {
        echo json_encode(['success' => false, 'message' => 'ID de tarjeta banco no válido']);
        exit;
    }
    
    $conexion = conectar_bd();
    
    // Verificar que la tarjeta existe y obtener información
    $query = "SELECT id_tarjeta_banco, empresa_tarjeta_id, por_defecto FROM tarjetas_banco_empresas WHERE id_tarjeta_banco = ?";
    $stmt = mysqli_prepare($conexion, $query);
    mysqli_stmt_bind_param($stmt, 'i', $id_tarjeta_banco);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    
    if ($result && mysqli_num_rows($result) > 0) {
        $row = mysqli_fetch_assoc($result);
        
        // No permitir eliminar tarjetas por defecto
        if ($row['por_defecto'] === 'true') {
            echo json_encode(['success' => false, 'message' => 'No se puede eliminar la tarjeta por defecto']);
            exit;
        }
        
        // Eliminar la tarjeta banco
        $query_delete = "DELETE FROM tarjetas_banco_empresas WHERE id_tarjeta_banco = ?";
        $stmt_delete = mysqli_prepare($conexion, $query_delete);
        mysqli_stmt_bind_param($stmt_delete, 'i', $id_tarjeta_banco);
        
        if (mysqli_stmt_execute($stmt_delete)) {
            // Log de la acción
            // log_accion('Tarjeta banco eliminada', 'empresas', $row['empresa_tarjeta_id']);
            echo json_encode(['success' => true, 'message' => 'Tarjeta banco eliminada correctamente']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Error al eliminar la tarjeta banco']);
        }
        
        mysqli_stmt_close($stmt_delete);
    } else {
        echo json_encode(['success' => false, 'message' => 'Tarjeta banco no encontrada']);
    }
    
    mysqli_stmt_close($stmt);
    mysqli_close($conexion);
    
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
}
?>
