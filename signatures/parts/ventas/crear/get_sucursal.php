<?php
require_once '../../../include/session.php';
require_once '../../../include/functions.php';

header('Content-Type: application/json');

try {
    $id_sucursal = isset($_GET['id_sucursal']) ? (int)$_GET['id_sucursal'] : 0;
    
    if (!$id_sucursal) {
        throw new Exception('ID de sucursal no especificado');
    }
    
    $conexion = conectar_bd();
    
    // Obtener datos de la sucursal
    $query = "SELECT direccion_tienda, poblacion_tienda, codigo_postal_tienda, telefono_tienda 
              FROM sucursal 
              WHERE id_sucursal = ?";
    $stmt = mysqli_prepare($conexion, $query);
    mysqli_stmt_bind_param($stmt, 'i', $id_sucursal);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    
    if ($sucursal = mysqli_fetch_assoc($result)) {
        mysqli_stmt_close($stmt);
        mysqli_close($conexion);
        
        echo json_encode(array(
            'success' => true,
            'sucursal' => $sucursal
        ));
    } else {
        throw new Exception('Sucursal no encontrada');
    }
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(array(
        'success' => false,
        'error' => $e->getMessage()
    ));
}
?>

