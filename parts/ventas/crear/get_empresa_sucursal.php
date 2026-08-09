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
    
    // Obtener empresa_id de la sucursal
    $query = "SELECT empresa_id FROM sucursal WHERE id_sucursal = ?";
    $stmt = mysqli_prepare($conexion, $query);
    mysqli_stmt_bind_param($stmt, 'i', $id_sucursal);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    
    if ($row = mysqli_fetch_assoc($result)) {
        $empresa_id = $row['empresa_id'];
        mysqli_stmt_close($stmt);
        
        // Obtener datos de la empresa
        $query_empresa = "SELECT * FROM empresas WHERE id_empresa = ?";
        $stmt_empresa = mysqli_prepare($conexion, $query_empresa);
        mysqli_stmt_bind_param($stmt_empresa, 'i', $empresa_id);
        mysqli_stmt_execute($stmt_empresa);
        $result_empresa = mysqli_stmt_get_result($stmt_empresa);
        
        if ($empresa = mysqli_fetch_assoc($result_empresa)) {
            mysqli_stmt_close($stmt_empresa);
            mysqli_close($conexion);
            
            echo json_encode(array(
                'success' => true,
                'empresa' => $empresa
            ));
        } else {
            throw new Exception('No se encontró la empresa');
        }
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

