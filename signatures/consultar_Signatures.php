<?php
require_once '../include/session.php';
require_once '../include/functions.php';

ob_start();
ob_clean();

header('Content-Type: application/json');

try {
    // Verificar que sea una petición POST
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception('Método no permitido');
    }
    
    // Validar parámetros
    if (!isset($_POST['consultaSignature'])) {
        throw new Exception('Parámetro consultaSignature no proporcionado');
    }
    
    // Conectar BD
    $conexion = conectar_bd();
    
    // CONSULTO SI EXISTE ESTA FIRMA
    $query_signature = "SELECT id_signature, ItemId, typeItem FROM Signatures 
                        WHERE state_signature = 'false' 
                        AND sucursalSignature = ? 
                        AND cancel_signature = 'false' 
                        LIMIT 1";
    
    $stmt = mysqli_prepare($conexion, $query_signature);
    
    if (!$stmt) {
        throw new Exception('Error al preparar consulta: ' . mysqli_error($conexion));
    }
    
    mysqli_stmt_bind_param($stmt, 'i', $usuario_sucursal);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    
    if (!$result || mysqli_num_rows($result) === 0) {
        mysqli_stmt_close($stmt);
        mysqli_close($conexion);
        
        echo json_encode(array(
            'success' => false,
            'status' => 'ko',
            'error_desc' => 'ko',
            'message' => 'No hay firmas pendientes'
        ));
        exit;
    }
    
    $signature = mysqli_fetch_assoc($result);
    $id_signature = $signature['id_signature'];
    $ItemId = $signature['ItemId'];
    $typeItem = $signature['typeItem'];
    
    mysqli_stmt_close($stmt);
    
    // Determinar el valor a mostrar según el tipo
    $value_parset = "";
    
    if ($typeItem == "lote") {
        // Consultar precio de compra del lote
        $tabla_lotes = "lotes_" . $usuario_sucursal;
        $query_price = "SELECT precio_compra FROM `" . $tabla_lotes . "` WHERE id_lote = ? AND sucursal = ?";
        
        $stmt_price = mysqli_prepare($conexion, $query_price);
        mysqli_stmt_bind_param($stmt_price, 'ii', $ItemId, $usuario_sucursal);
        mysqli_stmt_execute($stmt_price);
        $result_price = mysqli_stmt_get_result($stmt_price);
        
        if ($result_price && mysqli_num_rows($result_price) > 0) {
            $price_data = mysqli_fetch_assoc($result_price);
            $value_parset = "Usted va a recibir: " . $price_data['precio_compra'];
        } else {
            $value_parset = "Usted va a recibir: --";
        }
        
        mysqli_stmt_close($stmt_price);
        
    } else if ($typeItem == "envio") {
        $value_parset = "Usted envia";
    } else if ($typeItem == "venta") {
        $value_parset = "Usted paga";
    } else if ($typeItem == "user") {
        $value_parset = "Firma de empleado";
    } else if ($typeItem == "cierrecaja") {
        $value_parset = "Cerrar caja";
    } else {
        $value_parset = "--";
    }
    
    mysqli_close($conexion);
    
    // Respuesta exitosa
    echo json_encode(array(
        'success' => true,
        'status' => 'ok',
        'id_signature' => $id_signature,
        'ItemId' => $ItemId,
        'typeItem' => $typeItem,
        'value_parset' => $value_parset
    ));
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(array(
        'success' => false,
        'status' => 'error',
        'error_desc' => 'ko',
        'message' => $e->getMessage()
    ));
}
?>