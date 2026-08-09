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
    $id_empresa = isset($_POST['id_empresa']) ? (int)$_POST['id_empresa'] : 0;
    
    if (!$id_cuenta_banco || !$id_empresa) {
        echo json_encode(['success' => false, 'message' => 'ID de cuenta bancaria o empresa no válido']);
        exit;
    }
    
    $conexion = conectar_bd();
    
    // Verificar que la cuenta existe y pertenece a la empresa
    $query = "SELECT id_cuenta_banco, empresa_cuenta_id FROM cuentas_banco_empresas WHERE id_cuenta_banco = ? AND empresa_cuenta_id = ?";
    $stmt = mysqli_prepare($conexion, $query);
    mysqli_stmt_bind_param($stmt, 'ii', $id_cuenta_banco, $id_empresa);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    
    if ($result && mysqli_num_rows($result) > 0) {
        // Primero, quitar el flag de por defecto de todas las cuentas de la empresa
        $query_reset = "UPDATE cuentas_banco_empresas SET por_defecto = 'false' WHERE empresa_cuenta_id = ?";
        $stmt_reset = mysqli_prepare($conexion, $query_reset);
        mysqli_stmt_bind_param($stmt_reset, 'i', $id_empresa);
        
        if (mysqli_stmt_execute($stmt_reset)) {
            // Ahora marcar la cuenta seleccionada como por defecto
            $query_update = "UPDATE cuentas_banco_empresas SET por_defecto = 'true' WHERE id_cuenta_banco = ?";
            $stmt_update = mysqli_prepare($conexion, $query_update);
            mysqli_stmt_bind_param($stmt_update, 'i', $id_cuenta_banco);
            
            if (mysqli_stmt_execute($stmt_update)) {
                // Log de la acción
                // log_accion('Cuenta bancaria marcada como por defecto', 'empresas', $id_empresa);
                echo json_encode(['success' => true, 'message' => 'Cuenta bancaria marcada como por defecto correctamente']);
            } else {
                echo json_encode(['success' => false, 'message' => 'Error al marcar la cuenta como por defecto']);
            }
            
            mysqli_stmt_close($stmt_update);
        } else {
            echo json_encode(['success' => false, 'message' => 'Error al resetear cuentas por defecto']);
        }
        
        mysqli_stmt_close($stmt_reset);
    } else {
        echo json_encode(['success' => false, 'message' => 'Cuenta bancaria no encontrada o no pertenece a la empresa']);
    }
    
    mysqli_stmt_close($stmt);
    mysqli_close($conexion);
    
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
}
?>
