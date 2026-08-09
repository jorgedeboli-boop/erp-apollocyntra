<?php
/**
 * Archivo para insertar un registro SMS en la base de datos
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

// Obtener datos de la sesión
$id_usuario = isset($_SESSION['usuario_id']) ? intval($_SESSION['usuario_id']) : 0;

// Validar datos POST requeridos
$campos_requeridos = ['movil_sms', 'type_item_sms', 'estado_sms', 'codigo_sms', 'rel_item_sms', 'sucursal_sms'];
foreach ($campos_requeridos as $campo) {
    if (!isset($_POST[$campo]) || empty($_POST[$campo])) {
        echo json_encode([
            'statelogsms' => 'ko',
            'error' => 'Falta el campo: ' . $campo
        ]);
        exit();
    }
}

$movil_sms = trim($_POST['movil_sms']);
$type_item_sms = trim($_POST['type_item_sms']);
$estado_sms = trim($_POST['estado_sms']);
$codigo_sms = trim($_POST['codigo_sms']);
$rel_item_sms = intval($_POST['rel_item_sms']);
$sucursal_sms = intval($_POST['sucursal_sms']);
$mensaje_sms = trim($_POST['mensaje_sms']);

try {
    $conexion = conectar_bd();
    
    if (!$conexion) {
        throw new Exception('Error de conexión a la base de datos');
    }
    
    // Insertar registro SMS
    $query = "INSERT INTO sms_send (
                movil_sms,
                type_item_sms,
                estado_sms,
                codigo_sms,
                rel_item_sms,
                usuario_sms,
                surusal_sms,
                mensaje_sms,
                fecha_sms
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW())";
    
    $stmt = mysqli_prepare($conexion, $query);
    
    if (!$stmt) {
        throw new Exception("Error al preparar consulta: " . mysqli_error($conexion));
    }
    
    mysqli_stmt_bind_param($stmt, 'ssssiiis', 
        $movil_sms,
        $type_item_sms,
        $estado_sms,
        $codigo_sms,
        $rel_item_sms,
        $id_usuario,
        $sucursal_sms,
        $mensaje_sms    
    );
    
    if (!mysqli_stmt_execute($stmt)) {
        throw new Exception("Error al insertar SMS: " . mysqli_stmt_error($stmt));
    }
    
    $id_sms = mysqli_insert_id($conexion);
    mysqli_stmt_close($stmt);
    mysqli_close($conexion);
    
    echo json_encode([
        'statelogsms' => 'ok',
        'id_sms' => $id_sms
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