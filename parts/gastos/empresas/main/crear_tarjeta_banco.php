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
    
    // Verificar permisos
    if (!puede_acceder_a('empresas')) {
        http_response_code(403);
        echo json_encode(array('success' => false, 'message' => 'Acceso denegado'));
        exit;
    }
    
    // Obtener datos del formulario
    $id_empresa = isset($_POST['id_empresa']) ? (int)$_POST['id_empresa'] : 0;
    $numerotarjeta = isset($_POST['numerotarjeta']) ? trim($_POST['numerotarjeta']) : '';
    $banco_tarjeta = isset($_POST['banco_tarjeta']) ? trim($_POST['banco_tarjeta']) : '';
    
    // Validar datos
    if (!$id_empresa) {
        echo json_encode(array('success' => false, 'message' => 'ID de empresa no válido'));
        exit;
    }
    
    if (empty($numerotarjeta)) {
        echo json_encode(array('success' => false, 'message' => 'El número de tarjeta es obligatorio'));
        exit;
    }
    
    if (empty($banco_tarjeta)) {
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
    
    // Insertar nueva tarjeta banco
    $query_insert = "INSERT INTO tarjetas_banco_empresas 
                     (numerotarjeta, banco_tarjeta, empresa_tarjeta_id, fecha_creacion, creado_por, por_defecto) 
                     VALUES (?, ?, ?, CURDATE(), ?, 'false')";
    
    $stmt_insert = mysqli_prepare($conexion, $query_insert);
    mysqli_stmt_bind_param($stmt_insert, 'ssii', $numerotarjeta, $banco_tarjeta, $id_empresa, $usuario_actual);
    
    if (mysqli_stmt_execute($stmt_insert)) {
        $id_tarjeta_nueva = mysqli_insert_id($conexion);
        
        // Log de la acción
        // log_accion('Tarjeta banco creada', 'empresas', $id_empresa);
        
        echo json_encode(array(
            'success' => true,
            'message' => 'Tarjeta banco creada correctamente',
            'id_tarjeta_banco' => $id_tarjeta_nueva,
            'numerotarjeta' => $numerotarjeta,
            'banco_tarjeta' => $banco_tarjeta
        ));
    } else {
        throw new Exception('Error al insertar la tarjeta banco en la base de datos');
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
