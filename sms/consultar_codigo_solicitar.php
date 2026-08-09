<?php
/**
 * Archivo para consultar el estado de autorización de un SMS
 */

require_once '../include/session.php';
require_once '../include/functions.php';

header('Content-Type: application/json');

// Verificar que el usuario esté autenticado
if (!isset($_SESSION['usuario_id'])) {
    echo json_encode([
        'statelogsms' => 'ko',
        'error' => 'No autorizado'
    ]);
    exit();
}

// Verificar que se haya enviado el ID de SMS
if (!isset($_POST['id_sms_parset']) || empty($_POST['id_sms_parset'])) {
    echo json_encode([
        'statelogsms' => 'ko',
        'error' => 'No se recibió el ID de SMS'
    ]);
    exit();
}

$id_sms_parset = intval($_POST['id_sms_parset']);

try {
    $conexion = conectar_bd();
    
    if (!$conexion) {
        throw new Exception('Error de conexión a la base de datos');
    }
    
    // Consultar el estado de autorización
    $query = "SELECT estado_autorizado FROM sms_send WHERE id_sms = ?";
    $stmt = mysqli_prepare($conexion, $query);
    
    if (!$stmt) {
        throw new Exception("Error al preparar consulta: " . mysqli_error($conexion));
    }
    
    mysqli_stmt_bind_param($stmt, 'i', $id_sms_parset);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $rsItem = mysqli_fetch_assoc($result);
    mysqli_stmt_close($stmt);
    
    if (!$rsItem) {
        mysqli_close($conexion);
        echo json_encode([
            'statelogsms' => 'ko',
            'error' => 'SMS no encontrado'
        ]);
        exit();
    }
    
    $estado_autorizado = $rsItem['estado_autorizado'];
    mysqli_close($conexion);
    
    echo json_encode([
        'statelogsms' => 'ok',
        'autorizado' => $estado_autorizado,
        'id_sms_parset' => $id_sms_parset
    ]);
    
} catch (Exception $e) {
    if (isset($conexion)) {
        mysqli_close($conexion);
    }
    echo json_encode([
        'statelogsms' => 'ko',
        'error' => $e->getMessage()
    ]);
}
?>