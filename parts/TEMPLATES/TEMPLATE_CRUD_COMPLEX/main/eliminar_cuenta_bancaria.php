<?php
require_once '../../../include/session.php';
require_once '../../../include/functions.php';

header('Content-Type: application/json');

try {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        echo json_encode(['success' => false, 'message' => 'Método no permitido']);
        exit;
    }
    
    $id_cuenta_banco = isset($_POST['id_cuenta_banco']) ? (int)$_POST['id_cuenta_banco'] : 0;
    
    if (!$id_cuenta_banco) {
        echo json_encode(['success' => false, 'message' => 'ID de cuenta bancaria no válido']);
        exit;
    }
    
    $conexion = conectar_bd();
    
    // Verificar que la cuenta existe y obtener información
    $query = "SELECT id_cuenta_banco, gasto_cuenta_id, por_defecto FROM cuentas_banco_gastos WHERE id_cuenta_banco = ?";
    $stmt = mysqli_prepare($conexion, $query);
    mysqli_stmt_bind_param($stmt, 'i', $id_cuenta_banco);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    
    if ($result && mysqli_num_rows($result) > 0) {
        $row = mysqli_fetch_assoc($result);
        
        // No permitir eliminar cuentas por defecto
        if ($row['por_defecto'] === 'true') {
            echo json_encode(['success' => false, 'message' => 'No se puede eliminar la cuenta por defecto']);
            exit;
        }
        
        // Eliminar la cuenta bancaria
        $query_delete = "DELETE FROM cuentas_banco_gastos WHERE id_cuenta_banco = ?";
        $stmt_delete = mysqli_prepare($conexion, $query_delete);
        mysqli_stmt_bind_param($stmt_delete, 'i', $id_cuenta_banco);
        
        if (mysqli_stmt_execute($stmt_delete)) {
            // Log de la acción
            // log_accion('Cuenta bancaria eliminada', 'gastos', $row['gasto_cuenta_id']);
            echo json_encode(['success' => true, 'message' => 'Cuenta bancaria eliminada correctamente']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Error al eliminar la cuenta bancaria']);
        }
        
        mysqli_stmt_close($stmt_delete);
    } else {
        echo json_encode(['success' => false, 'message' => 'Cuenta bancaria no encontrada']);
    }
    
    mysqli_stmt_close($stmt);
    mysqli_close($conexion);
    
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
}
?>
