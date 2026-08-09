<?php
require_once '../../../include/session.php';
require_once '../../../include/functions.php';

header('Content-Type: application/json');

try {
    $sku = isset($_GET['sku']) ? (int)$_GET['sku'] : 0;
    $id_sucursal = isset($_GET['id_sucursal']) ? (int)$_GET['id_sucursal'] : 0;
    
    if (!$sku) {
        throw new Exception('Debe proporcionar el SKU del artículo');
    }
    
    if (!$id_sucursal) {
        throw new Exception('Debe seleccionar una sucursal');
    }
    
    $conexion = conectar_bd();
    
    // Buscar artículo por ID (SKU), comparando con id_sucursal_destino y estado enventa
    $query = "SELECT 
                av.id,
                av.id as sku,
                av.descripcion,
                av.peso,
                av.precio,
                av.tipo_articulo as tipo
              FROM articulos_venta av
              WHERE av.id = ? 
              AND av.id_sucursal_destino = ?
              AND av.estado = 'enventa'
              LIMIT 1";
    
    $stmt = mysqli_prepare($conexion, $query);
    
    if (!$stmt) {
        throw new Exception('Error en preparación de consulta: ' . mysqli_error($conexion));
    }
    
    mysqli_stmt_bind_param($stmt, 'ii', $sku, $id_sucursal);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    
    // Debug: contar resultados
    $num_rows = mysqli_num_rows($result);
    
    if ($articulo = mysqli_fetch_assoc($result)) {
        mysqli_stmt_close($stmt);
        mysqli_close($conexion);
        
        echo json_encode(array(
            'success' => true,
            'encontrado' => true,
            'articulo' => $articulo,
            'debug' => array(
                'sku_buscado' => $sku,
                'id_sucursal_buscado' => $id_sucursal,
                'num_resultados' => $num_rows
            )
        ));
    } else {
        mysqli_stmt_close($stmt);
        mysqli_close($conexion);
        
        echo json_encode(array(
            'success' => true,
            'encontrado' => false,
            'message' => 'Artículo no encontrado en esta sucursal o no está disponible para la venta',
            'debug' => array(
                'sku_buscado' => $sku,
                'id_sucursal_buscado' => $id_sucursal,
                'num_resultados' => $num_rows
            )
        ));
    }
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(array(
        'success' => false,
        'error' => $e->getMessage()
    ));
}
?>

