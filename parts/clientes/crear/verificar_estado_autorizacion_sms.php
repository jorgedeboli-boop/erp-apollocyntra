<?php
require_once '../../../include/session.php';
require_once '../../../include/functions.php';

header('Content-Type: application/json');

// Verificar que el usuario esté autenticado
if (!isset($_SESSION['usuario_id'])) {
    http_response_code(401);
    echo json_encode([
        'success' => false,
        'error' => 'No autorizado'
    ]);
    exit;
}

// Verificar que se haya enviado el id_sms
if (!isset($_POST['id_sms']) || empty($_POST['id_sms'])) {
    echo json_encode([
        'success' => false,
        'error' => 'No se recibió el ID de SMS'
    ]);
    exit();
}

$id_sms = intval($_POST['id_sms']);

try {
    $conexion = conectar_bd();
    
    if (!$conexion) {
        throw new Exception('Error de conexión a la base de datos');
    }
    
    // Consultar el estado de autorización
    $query = "SELECT autorizado_central, estado_autorizado FROM sms_send WHERE id_sms = ?";
    $stmt = mysqli_prepare($conexion, $query);
    
    if (!$stmt) {
        throw new Exception("Error al preparar consulta: " . mysqli_error($conexion));
    }
    
    mysqli_stmt_bind_param($stmt, 'i', $id_sms);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    
    if (mysqli_num_rows($result) === 0) {
        mysqli_stmt_close($stmt);
        mysqli_close($conexion);
        echo json_encode([
            'success' => false,
            'error' => 'SMS no encontrado',
            'autorizado' => false
        ]);
        exit();
    }
    
    $row = mysqli_fetch_assoc($result);
    $autorizado_central = $row['autorizado_central'];
    $estado_autorizado = $row['estado_autorizado'];
    
    mysqli_stmt_close($stmt);
    mysqli_close($conexion);
    
    // Verificar si ambas condiciones son 'true'
    $autorizado = ($autorizado_central === 'true' && $estado_autorizado === 'true');
    
    echo json_encode([
        'success' => true,
        'autorizado' => $autorizado,
        'autorizado_central' => $autorizado_central,
        'estado_autorizado' => $estado_autorizado
    ]);
    
} catch (Exception $e) {
    if (isset($stmt)) {
        mysqli_stmt_close($stmt);
    }
    if (isset($conexion)) {
        mysqli_close($conexion);
    }
    
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage(),
        'autorizado' => false
    ]);
}
?>
