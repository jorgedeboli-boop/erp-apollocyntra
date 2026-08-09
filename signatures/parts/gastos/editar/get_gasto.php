<?php
/**
 * Archivo para obtener datos de una gasto específica
 * Compatible con PHP 7.0
 */

require_once '../../../include/session.php';
require_once '../../../include/functions.php';

header('Content-Type: application/json');

try {
    // Verificar que sea una petición GET
    if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
        http_response_code(405);
        echo json_encode(array('error' => 'Método no permitido'));
        exit;
    }
    
    // Obtener ID de la gasto
    $id_gasto = isset($_GET['id']) ? (int)$_GET['id'] : 0;
    
    if (!$id_gasto) {
        http_response_code(400);
        echo json_encode(array('error' => 'ID de gasto no válido'));
        exit;
    }
    
    // Conectar BD
    $conexion = conectar_bd();
    
    // Consulta para obtener datos del gasto
    $query_gasto = "
        SELECT 
            g.id_gasto,
            g.fecha_gasto,
            g.descripcion_gasto,
            g.total_gasto,
            g.estado_gasto,
            g.empresa_gasto,
            g.sucursal_gasto,
            g.proveedor_gasto,
            g.tipo_de_gasto,
            g.forma_pago_gasto,
            g.numero_factura_proveedor,
            g.observaciones_gasto,
            g.usuario_gasto,
            g.fecha_creacion_gasto,
            e.nombre_empresa,
            s.nombre_sucursal,
            p.nombre_proveedor,
            tg.nombre_tipo_gasto,
            fp.nombre_forma_de_pago
        FROM gastos g
        LEFT JOIN empresas e ON g.empresa_gasto = e.id_empresa
        LEFT JOIN sucursal s ON g.sucursal_gasto = s.id_sucursal
        LEFT JOIN proveedores p ON g.proveedor_gasto = p.id_proveedor
        LEFT JOIN tipo_de_gasto tg ON g.tipo_de_gasto = tg.id_tipo_gasto
        LEFT JOIN formas_de_pago fp ON g.forma_pago_gasto = fp.id_forma_de_pago
        WHERE g.id_gasto = ?
    ";
    
    $stmt_gasto = mysqli_prepare($conexion, $query_gasto);
    mysqli_stmt_bind_param($stmt_gasto, 'i', $id_gasto);
    mysqli_stmt_execute($stmt_gasto);
    $result_gasto = mysqli_stmt_get_result($stmt_gasto);
    
    if ($result_gasto && mysqli_num_rows($result_gasto) > 0) {
        $gasto = mysqli_fetch_assoc($result_gasto);
        mysqli_stmt_close($stmt_gasto);
        
        // Respuesta exitosa
        echo json_encode(array(
            'success' => true,
            'gasto' => $gasto
        ));
    } else {
        // Gasto no encontrado
        http_response_code(404);
        echo json_encode(array(
            'success' => false,
            'error' => 'Gasto no encontrado'
        ));
    }
    
    mysqli_close($conexion);
    
} catch (Exception $e) {
    // Error del sistema
    http_response_code(500);
    echo json_encode(array(
        'success' => false,
        'error' => 'Error interno del servidor: ' . $e->getMessage()
    ));
} catch (Error $e) {
    // Error fatal
    http_response_code(500);
    echo json_encode(array(
        'success' => false,
        'error' => 'Error fatal del sistema'
    ));
}
?>
