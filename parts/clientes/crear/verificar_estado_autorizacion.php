<?php
/**
 * Archivo para verificar el estado de una autorización
 */

require_once '../../../include/session.php';

header('Content-Type: application/json');

// Verificar conexión a la base de datos
$conexion = conectar_bd();

if (!$conexion) {
    echo json_encode([
        'success' => false,
        'error' => 'Error de conexión a la base de datos'
    ]);
    exit();
}

// Verificar que se haya enviado el ID de autorización
if (!isset($_POST['id_autorizacion']) || empty($_POST['id_autorizacion'])) {
    mysqli_close($conexion);
    echo json_encode([
        'success' => false,
        'error' => 'No se recibió el ID de autorización'
    ]);
    exit();
}

$id_autorizacion = intval($_POST['id_autorizacion']);

try {
    // Consultar el estado de la autorización
    $query = "SELECT estado_autorizacion, intereses_lote FROM autorizaciones_porcentajes WHERE id_autorizacion = ?";
    $stmt = mysqli_prepare($conexion, $query);
    
    if (!$stmt) {
        throw new Exception("Error al preparar consulta: " . mysqli_error($conexion));
    }
    
    mysqli_stmt_bind_param($stmt, 'i', $id_autorizacion);
    
    if (!mysqli_stmt_execute($stmt)) {
        throw new Exception("Error al ejecutar consulta: " . mysqli_stmt_error($stmt));
    }
    
    $result = mysqli_stmt_get_result($stmt);
    $row = mysqli_fetch_assoc($result);
    
    mysqli_stmt_close($stmt);
    mysqli_close($conexion);
    
    if (!$row) {
        echo json_encode([
            'success' => false,
            'error' => 'Autorización no encontrada'
        ]);
        exit();
    }
    
    // Respuesta exitosa
    echo json_encode([
        'success' => true,
        'estado_autorizacion' => $row['estado_autorizacion'],
        'intereses_lote' => $row['intereses_lote'],
        'autorizada' => ($row['estado_autorizacion'] === 'autorizada')
    ]);
    
} catch (Exception $e) {
    mysqli_close($conexion);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
?>

