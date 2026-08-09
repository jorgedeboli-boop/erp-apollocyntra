<?php
/**
 * Archivo para comprobar el código de autorización SMS
 */

require_once '../include/session.php';
require_once '../include/functions.php';

header('Content-Type: application/json');

// Verificar que se hayan enviado los parámetros necesarios
if (!isset($_POST['id_sms']) || empty($_POST['id_sms'])) {
    echo json_encode([
        'same_code' => 'ko',
        'error' => 'No se recibió el ID de SMS'
    ]);
    exit();
}

if (!isset($_POST['codigo_sms']) || empty($_POST['codigo_sms'])) {
    echo json_encode([
        'same_code' => 'ko',
        'error' => 'No se recibió el código SMS'
    ]);
    exit();
}

$id_sms_parset = intval($_POST['id_sms']);
$codigo_sms_parset = strtoupper(trim($_POST['codigo_sms']));

try {
    $conexion = conectar_bd();
    
    if (!$conexion) {
        throw new Exception('Error de conexión a la base de datos');
    }
    
    // Consultar el código SMS almacenado
    $query = "SELECT id_sms, codigo_sms FROM sms_send WHERE id_sms = ?";
    $stmt = mysqli_prepare($conexion, $query);
    
    if (!$stmt) {
        throw new Exception("Error al preparar consulta: " . mysqli_error($conexion));
    }
    
    mysqli_stmt_bind_param($stmt, 'i', $id_sms_parset);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $rsItem = mysqli_fetch_assoc($result);
    mysqli_stmt_close($stmt);
    
    if (!$rsItem || empty($rsItem['id_sms'])) {
        mysqli_close($conexion);
        echo json_encode([
            'same_code' => 'ko',
            'error' => 'SMS no encontrado'
        ]);
        exit();
    }
    
    $id_sms = $rsItem['id_sms'];
    $codigo_sms = $rsItem['codigo_sms'];
    
    // Comparar códigos
    if ($codigo_sms == $codigo_sms_parset) {
        // Actualizar estado del código
        $updateQuery = "UPDATE sms_send SET estado_codigo = 'true' WHERE id_sms = ?";
        $updateStmt = mysqli_prepare($conexion, $updateQuery);
        
        if (!$updateStmt) {
            throw new Exception("Error al preparar actualización: " . mysqli_error($conexion));
        }
        
        mysqli_stmt_bind_param($updateStmt, 'i', $id_sms_parset);
        mysqli_stmt_execute($updateStmt);
        mysqli_stmt_close($updateStmt);
        
        mysqli_close($conexion);
        
        echo json_encode([
            'same_code' => 'ok'
        ]);
    } else {
        mysqli_close($conexion);
        echo json_encode([
            'same_code' => 'ko',
            'error' => 'Código incorrecto'
        ]);
    }
    
} catch (Exception $e) {
    if (isset($conexion)) {
        mysqli_close($conexion);
    }
    echo json_encode([
        'same_code' => 'ko',
        'error' => $e->getMessage()
    ]);
}
?>