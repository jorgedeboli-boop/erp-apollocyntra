<?php
require_once '../include/session.php';
require_once '../include/functions.php';

header('Content-Type: application/json');

try {
    $id_lote = isset($_GET['id_lote']) ? (int)$_GET['id_lote'] : 0;
    
    if (!$id_lote) {
        throw new Exception('ID de lote no válido');
    }
    
    $conexion = conectar_bd();
    
    // Consultar la sucursal del lote en lotes_joyeria
    $query = "SELECT sucursal FROM lotes_joyeria WHERE id_lote = ?";
    $stmt = mysqli_prepare($conexion, $query);
    mysqli_stmt_bind_param($stmt, 'i', $id_lote);
    mysqli_stmt_execute($stmt);
    $resultado = mysqli_stmt_get_result($stmt);
    
    if ($row = mysqli_fetch_assoc($resultado)) {
        mysqli_stmt_close($stmt);
        mysqli_close($conexion);
        
        echo json_encode(array(
            'success' => true,
            'id_sucursal' => $row['sucursal']
        ));
    } else {
        mysqli_stmt_close($stmt);
        mysqli_close($conexion);
        
        throw new Exception('Lote no encontrado');
    }
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(array(
        'success' => false,
        'error' => $e->getMessage()
    ));
}
?>

