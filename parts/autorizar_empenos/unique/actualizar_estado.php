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

// Verificar que se hayan enviado los datos necesarios
if (!isset($_POST['id_autorizacion']) || empty($_POST['id_autorizacion'])) {
    echo json_encode([
        'success' => false,
        'error' => 'No se recibió el ID de autorización'
    ]);
    exit();
}

if (!isset($_POST['estado']) || empty($_POST['estado'])) {
    echo json_encode([
        'success' => false,
        'error' => 'No se recibió el estado'
    ]);
    exit();
}

$id_autorizacion = intval($_POST['id_autorizacion']);
$estado = $_POST['estado'];

// Validar que el estado sea válido
$estados_validos = ['pendiente', 'autorizada', 'cancelada'];
if (!in_array($estado, $estados_validos)) {
    echo json_encode([
        'success' => false,
        'error' => 'Estado no válido'
    ]);
    exit();
}

try {
    $conexion = conectar_bd();
    
    if (!$conexion) {
        throw new Exception('Error de conexión a la base de datos');
    }
    
    // Actualizar el estado de la autorización
    $query = "UPDATE autorizaciones_porcentajes SET estado_autorizacion = ? WHERE id_autorizacion = ?";
    $stmt = mysqli_prepare($conexion, $query);
    
    if (!$stmt) {
        throw new Exception("Error al preparar consulta: " . mysqli_error($conexion));
    }
    
    mysqli_stmt_bind_param($stmt, 'si', $estado, $id_autorizacion);
    
    if (!mysqli_stmt_execute($stmt)) {
        throw new Exception("Error al actualizar autorización: " . mysqli_stmt_error($stmt));
    }
    
    mysqli_stmt_close($stmt);
    mysqli_close($conexion);
    
    // Mensaje según el estado
    $mensaje = '';
    if ($estado === 'autorizada') {
        $mensaje = 'La autorización ha sido aprobada correctamente';
    } else if ($estado === 'cancelada') {
        $mensaje = 'La autorización ha sido cancelada';
    }
    
    echo json_encode([
        'success' => true,
        'message' => $mensaje
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
        'error' => $e->getMessage()
    ]);
}
?>

