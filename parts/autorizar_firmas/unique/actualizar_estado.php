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
$usuario_id = $_SESSION['usuario_id'];

// Convertir estado a formato de la base de datos
// 'pendiente' -> 'false', 'autorizada' -> 'true'
$estado_db = ($estado === 'autorizada') ? 'true' : 'false';

try {
    $conexion = conectar_bd();
    
    if (!$conexion) {
        throw new Exception('Error de conexión a la base de datos');
    }
    
    // Actualizar el estado de la autorización
    // Si se autoriza, también actualizar user_auth_no_signature
    if ($estado_db === 'true') {
        $query = "UPDATE Signatures SET auth_no_signature = ?, user_auth_no_signature = ? WHERE id_signature = ?";
        $stmt = mysqli_prepare($conexion, $query);
        
        if (!$stmt) {
            throw new Exception("Error al preparar consulta: " . mysqli_error($conexion));
        }
        
        mysqli_stmt_bind_param($stmt, 'sii', $estado_db, $usuario_id, $id_autorizacion);
    } else {
        $query = "UPDATE Signatures SET auth_no_signature = ? WHERE id_signature = ?";
        $stmt = mysqli_prepare($conexion, $query);
        
        if (!$stmt) {
            throw new Exception("Error al preparar consulta: " . mysqli_error($conexion));
        }
        
        mysqli_stmt_bind_param($stmt, 'si', $estado_db, $id_autorizacion);
    }
    
    if (!mysqli_stmt_execute($stmt)) {
        throw new Exception("Error al actualizar autorización: " . mysqli_stmt_error($stmt));
    }
    
    mysqli_stmt_close($stmt);
    mysqli_close($conexion);
    
    // Mensaje según el estado
    $mensaje = '';
    if ($estado === 'autorizada') {
        $mensaje = 'La autorización ha sido aprobada correctamente';
    } else if ($estado === 'pendiente') {
        $mensaje = 'La autorización ha sido actualizada';
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
