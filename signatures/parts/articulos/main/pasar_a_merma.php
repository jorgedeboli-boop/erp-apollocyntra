<?php
require_once '../../../include/session.php';

header('Content-Type: application/json');

try {
    // Verificar que sea una petición POST
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        http_response_code(405);
        echo json_encode(array('success' => false, 'error' => 'Método no permitido'));
        exit;
    }
    
    // Validar que se haya proporcionado el ID del artículo
    if (!isset($_POST['id_articulo']) || empty($_POST['id_articulo'])) {
        http_response_code(400);
        echo json_encode(array('success' => false, 'error' => 'ID de artículo es requerido'));
        exit;
    }
    
    $id_articulo = (int)$_POST['id_articulo'];
    $motivo_merma = trim($_POST['motivo_merma']);
    
    // Conectar BD
    $conexion = conectar_bd();
    
    // Verificar que el artículo existe y está en un estado válido para pasar a merma
    $query_check = "SELECT id, estado, id_sucursal_destino FROM articulos_venta WHERE id = ? LIMIT 1";
    $stmt_check = mysqli_prepare($conexion, $query_check);
    mysqli_stmt_bind_param($stmt_check, 'i', $id_articulo);
    mysqli_stmt_execute($stmt_check);
    $result_check = mysqli_stmt_get_result($stmt_check);
    
    if (mysqli_num_rows($result_check) == 0) {
        mysqli_stmt_close($stmt_check);
        mysqli_close($conexion);
        http_response_code(404);
        echo json_encode(array('success' => false, 'error' => 'El artículo no existe'));
        exit;
    }
    
    $articulo = mysqli_fetch_assoc($result_check);
    mysqli_stmt_close($stmt_check);
    
    // Estados válidos para pasar a merma
    $estados_validos = ['noetiquetado_c', 'noetiquetado_u', 'enventa', 'enviado', 'enreparacion'];
    $estado_actual = strtolower($articulo['estado']);
    $id_sucursal = $articulo['id_sucursal_destino'];

    if (!in_array($estado_actual, $estados_validos)) {
        mysqli_close($conexion);
        http_response_code(400);
        echo json_encode(array('success' => false, 'error' => 'El artículo no puede pasar a merma desde el estado actual'));
        exit;
    }
    
    // Iniciar transacción
    mysqli_begin_transaction($conexion);
    
    try {
        // Actualizar el estado a 'mermado'
        $query_update = "UPDATE articulos_venta SET estado = 'mermado', update_register = NOW(), fecha_mermado = NOW(), motivo_merma = ? WHERE id = ?";
        $stmt_update = mysqli_prepare($conexion, $query_update);
        $motivo_merma = trim($_POST['motivo_merma']);
        if (!$stmt_update) {
            throw new Exception("Error al preparar consulta: " . mysqli_error($conexion));
        }
        
        mysqli_stmt_bind_param($stmt_update, 'si', $motivo_merma, $id_articulo);
        
        if (!mysqli_stmt_execute($stmt_update)) {
            throw new Exception("Error al actualizar artículo: " . mysqli_stmt_error($stmt_update));
        }
        
        mysqli_stmt_close($stmt_update);

        // Actualizar también rel_articulos_estados
        $query_update_rel = "
            UPDATE rel_articulos_estados SET
                estado_articulo = 'mermado',
                fecha_mermado = NOW(),
                motivo_merma = ?
            WHERE rel_id_articulo_venta = ? AND rel_id_sucursal_venta = ?
        ";
        
        $stmt_update_rel = mysqli_prepare($conexion, $query_update_rel);
        if (!$stmt_update_rel) {
            throw new Exception("Error al preparar consulta de actualización de estado en rel_articulos_estados: " . mysqli_error($conexion));
        }
        
        mysqli_stmt_bind_param($stmt_update_rel, 'sii', $motivo_merma, $id_articulo, $id_sucursal);
        if (!mysqli_stmt_execute($stmt_update_rel)) {
            mysqli_stmt_close($stmt_update_rel);
            throw new Exception("Error al actualizar estado en rel_articulos_estados: " . mysqli_stmt_error($stmt_update_rel));
        }
        
        mysqli_stmt_close($stmt_update_rel);

        // TRAZABILIDAD mermado
        $accion_trazabilidad_venta = 'mermado';
        $comentarios_trazabilidad_venta = "Artículo pasado a merma en la sucursal " . $id_sucursal . " por el usuario " . $_SESSION['usuario_id'] . ". Motivo: " . $motivo_merma;
        
        try {
            trazabilidad_articulos_venta( 0, $_SESSION['usuario_id'], $accion_trazabilidad_venta, $comentarios_trazabilidad_venta, $id_sucursal, $id_articulo, 0);
        } catch (Exception $e) {
            // Log del error de trazabilidad, pero no detener el proceso
            error_log("Error al insertar trazabilidad: " . $e->getMessage());
        }
        
        // Confirmar transacción
        mysqli_commit($conexion);
        
        // Respuesta exitosa
        echo json_encode(array(
            'success' => true,
            'message' => 'Artículo pasado a merma correctamente'
        ));
        
    } catch (Exception $e) {
        // Rollback en caso de error
        mysqli_rollback($conexion);
        
        http_response_code(500);
        echo json_encode(array(
            'success' => false,
            'error' => $e->getMessage()
        ));
    }
    
    mysqli_close($conexion);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(array(
        'success' => false,
        'error' => $e->getMessage()
    ));
}
?>
