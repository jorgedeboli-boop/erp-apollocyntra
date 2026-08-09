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
    if (!isset($_POST['id_signature']) || empty($_POST['id_signature'])) {
        throw new Exception('ID de firma no proporcionado');
    }
    
    $id_signature = (int)$_POST['id_signature'];
    
    // Conectar BD
    $conexion = conectar_bd();
    
    // CONSULTO SI EXISTE ESTA FIRMA
    $query_signature = "SELECT ItemId, typeItem, signature_value, state_signature, cancel_signature, auth_no_signature, sucursalSignature 
                        FROM Signatures 
                        WHERE id_signature = ?";
    
    $stmt = mysqli_prepare($conexion, $query_signature);
    
    if (!$stmt) {
        throw new Exception('Error al preparar consulta: ' . mysqli_error($conexion));
    }
    
    mysqli_stmt_bind_param($stmt, 'i', $id_signature);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    
    if (!$result || mysqli_num_rows($result) === 0) {
        mysqli_stmt_close($stmt);
        mysqli_close($conexion);
        throw new Exception('Firma no encontrada');
    }
    
    $signature = mysqli_fetch_assoc($result);
    $ItemId = $signature['ItemId'];
    $typeItem = $signature['typeItem'];
    $signature_value = $signature['signature_value'];
    $state_signature = $signature['state_signature'];
    $cancel_signature = $signature['cancel_signature'];
    $auth_no_signature = $signature['auth_no_signature'];
    $sucursalSignature = $signature['sucursalSignature'];
    mysqli_stmt_close($stmt);
    
    // Si está autorizada sin firma
    if ($auth_no_signature == "true") {
        $state_signature = "autorizada_no_firma";
    }
    
    // Actualizar firma según tipo
    if ($typeItem == "lote") {
        // Obtener cliente del lote
        $tabla_lotes = "lotes_" . $sucursalSignature;
        $query_cliente = "SELECT cliente FROM `" . $tabla_lotes . "` WHERE id_lote = ? LIMIT 1";
        
        $stmt_cliente = mysqli_prepare($conexion, $query_cliente);
        mysqli_stmt_bind_param($stmt_cliente, 'i', $ItemId);
        mysqli_stmt_execute($stmt_cliente);
        $result_cliente = mysqli_stmt_get_result($stmt_cliente);
        
        if ($result_cliente && mysqli_num_rows($result_cliente) > 0) {
            $lote_data = mysqli_fetch_assoc($result_cliente);
            $id_cliente = $lote_data['cliente'];
            
            // Actualizar firma en tabla clientes
            $query_update_cliente = "UPDATE datos_clientes SET firma_cliente = ? WHERE rel_id_cliente = ?";
            $stmt_update = mysqli_prepare($conexion, $query_update_cliente);
            mysqli_stmt_bind_param($stmt_update, 'si', $signature_value, $id_cliente);
            
            if (!mysqli_stmt_execute($stmt_update)) {
                throw new Exception('Error al actualizar firma del cliente: '.$tabla_lotes.' - '.$id_cliente.' - ' . mysqli_stmt_error($stmt_update));
            }
            
            mysqli_stmt_close($stmt_update);
            
            // Actualizar en base de datos secundaria (figueredo, solo production)
            $mysqli_figueredoapp = get_mysqli_figueredoapp();
            if ($mysqli_figueredoapp) {
                $query_update_figueredo = "UPDATE clientes SET firma_cliente = ? WHERE id_cliente = ?";
                $stmt_figueredo = mysqli_prepare($mysqli_figueredoapp, $query_update_figueredo);
                mysqli_stmt_bind_param($stmt_figueredo, 'si', $signature_value, $id_cliente);
                mysqli_stmt_execute($stmt_figueredo);
                mysqli_stmt_close($stmt_figueredo);
            }
        }
        
        mysqli_stmt_close($stmt_cliente);
        
    } elseif ($typeItem == "user") {
        // Actualizar firma en tabla usuarios
        $query_update_usuario = "UPDATE usuarios SET firma_usuario = ? WHERE id_usuario = ?";
        $stmt_update = mysqli_prepare($conexion, $query_update_usuario);
        mysqli_stmt_bind_param($stmt_update, 'si', $signature_value, $ItemId);
        
        if (!mysqli_stmt_execute($stmt_update)) {
            throw new Exception('Error al actualizar firma del usuario: ' . mysqli_stmt_error($stmt_update));
        }
        
        mysqli_stmt_close($stmt_update);
    }
    
    mysqli_close($conexion);
    
    // Respuesta exitosa
    echo json_encode(array(
        'success' => true,
        'status' => 'ok',
        'id_signature' => $id_signature,
        'state_signature' => $state_signature,
        'cancel_signature' => $cancel_signature
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