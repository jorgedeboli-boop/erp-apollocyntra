<?php
/**
 * Archivo para obtener el valor de intereses de una sucursal
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

// Verificar que se haya enviado el ID de sucursal
if (!isset($_POST['id_sucursal']) || empty($_POST['id_sucursal'])) {
    mysqli_close($conexion);
    echo json_encode([
        'success' => false,
        'error' => 'No se recibió el ID de sucursal'
    ]);
    exit();
}

$id_sucursal = intval($_POST['id_sucursal']);

try {
    // Consultar el valor de intereses de la sucursal
    $query = "SELECT intereses FROM sucursal WHERE id_sucursal = ?";
    $stmt = mysqli_prepare($conexion, $query);
    
    if (!$stmt) {
        throw new Exception("Error al preparar consulta: " . mysqli_error($conexion));
    }
    
    mysqli_stmt_bind_param($stmt, 'i', $id_sucursal);
    
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
            'error' => 'Sucursal no encontrada'
        ]);
        exit();
    }
    
    // Respuesta exitosa
    echo json_encode([
        'success' => true,
        'intereses' => $row['intereses']
    ]);
    
} catch (Exception $e) {
    mysqli_close($conexion);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
?>

