<?php
require_once '../../../include/session.php';
require_once '../../../include/functions.php';

// Headers para JSON
header('Content-Type: application/json');
header('Cache-Control: no-cache, must-revalidate');

try {
    // Verificar que sea una petición POST
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        http_response_code(405);
        echo json_encode(array('success' => false, 'message' => 'Método no permitido'));
        exit;
    }
    
    // Obtener datos del formulario
    $id_proveedor = isset($_POST['id_proveedor']) ? (int)$_POST['id_proveedor'] : 0;
    $numerocuenta = isset($_POST['numerocuenta']) ? trim($_POST['numerocuenta']) : '';
    $banco_cuenta = isset($_POST['banco_cuenta']) ? trim($_POST['banco_cuenta']) : '';
    
    // Validar datos
    if (!$id_proveedor) {
        echo json_encode(array('success' => false, 'message' => 'ID de proveedor no válido'));
        exit;
    }
    
    if (empty($numerocuenta)) {
        echo json_encode(array('success' => false, 'message' => 'El número de cuenta es obligatorio'));
        exit;
    }
    
    if (empty($banco_cuenta)) {
        echo json_encode(array('success' => false, 'message' => 'El nombre del banco es obligatorio'));
        exit;
    }
    
    // Conectar a la base de datos
    $conexion = conectar_bd();
    
    // Verificar que la empresa existe
    $query_check = "SELECT id_empresa FROM empresas WHERE id_empresa = ?";
    $stmt_check = mysqli_prepare($conexion, $query_check);
    mysqli_stmt_bind_param($stmt_check, 'i', $id_empresa);
    mysqli_stmt_execute($stmt_check);
    $result_check = mysqli_stmt_get_result($stmt_check);
    
    if (!$result_check || mysqli_num_rows($result_check) == 0) {
        echo json_encode(array('success' => false, 'message' => 'Empresa no encontrada'));
        exit;
    }
    mysqli_stmt_close($stmt_check);
    
    // Obtener ID del usuario actual
    $usuario_actual = $_SESSION['usuario_id'] ?? 0;
    
    // Insertar nueva cuenta bancaria
    $query_insert = "INSERT INTO cuentas_banco_empresas 
                     (numerocuenta, banco_cuenta, empresa_cuenta_id, fecha_creacion, creado_por, por_defecto) 
                     VALUES (?, ?, ?, CURDATE(), ?, 'false')";
    
    $stmt_insert = mysqli_prepare($conexion, $query_insert);
    mysqli_stmt_bind_param($stmt_insert, 'ssii', $numerocuenta, $banco_cuenta, $id_empresa, $usuario_actual);
    
    if (mysqli_stmt_execute($stmt_insert)) {
        $id_cuenta_nueva = mysqli_insert_id($conexion);
        
        // Log de la acción
        // log_accion('Cuenta bancaria creada', 'empresas', $id_empresa);
        
        echo json_encode(array(
            'success' => true,
            'message' => 'Cuenta bancaria creada correctamente',
            'id_cuenta_banco' => $id_cuenta_nueva,
            'numerocuenta' => $numerocuenta,
            'banco_cuenta' => $banco_cuenta
        ));
    } else {
        throw new Exception('Error al insertar la cuenta bancaria en la base de datos');
    }
    
    mysqli_stmt_close($stmt_insert);
    mysqli_close($conexion);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(array(
        'success' => false,
        'message' => 'Error interno del servidor: ' . $e->getMessage()
    ));
}
?>
