<?php
require_once '../../../include/session.php';
require_once '../../../include/functions.php';

// Headers para JSON
header('Content-Type: application/json');
header('Cache-Control: no-cache, must-revalidate');

try {
    // Verificar que el usuario sea usuario_root
    if (!isset($_SESSION['usuario_root']) || $_SESSION['usuario_root'] !== true) {
        http_response_code(403);
        echo json_encode(array('success' => false, 'message' => 'Solo los usuarios administradores pueden eliminar gastos'));
        exit;
    }
    
    // Verificar método POST
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        http_response_code(405);
        echo json_encode(array('success' => false, 'message' => 'Método no permitido'));
        exit;
    }
    
    // Obtener ID de la gasto
    $id_gasto = isset($_POST['id_gasto']) ? (int)$_POST['id_gasto'] : 0;
    
    if (!$id_gasto) {
        echo json_encode(array('success' => false, 'message' => 'ID de gasto no válido'));
        exit;
    }
    
    // Conectar a la base de datos
    $conexion = conectar_bd();
    
    // Verificar que la gasto existe
    $query_verificar = "SELECT nombre_gasto, logotipo_gasto FROM gastos WHERE id_gasto = ?";
    $stmt_verificar = mysqli_prepare($conexion, $query_verificar);
    mysqli_stmt_bind_param($stmt_verificar, 'i', $id_gasto);
    mysqli_stmt_execute($stmt_verificar);
    $result_verificar = mysqli_stmt_get_result($stmt_verificar);
    
    if (!$result_verificar || mysqli_num_rows($result_verificar) === 0) {
        mysqli_stmt_close($stmt_verificar);
        mysqli_close($conexion);
        echo json_encode(array('success' => false, 'message' => 'Empresa no encontrada'));
        exit;
    }
    
    $gasto = mysqli_fetch_assoc($result_verificar);
    mysqli_stmt_close($stmt_verificar);
    
    // Iniciar transacción
    mysqli_begin_transaction($conexion);
    
    try {
        // 1. Eliminar logotipo físico si existe
        if (!empty($gasto['logotipo_gasto'])) {
            $ruta_logotipo = '../../../photos/' . $gasto['logotipo_gasto'];
            if (file_exists($ruta_logotipo)) {
                unlink($ruta_logotipo);
            }
        }
        
        // 2. Eliminar cuentas bancarias de la gasto
        $query_eliminar_cuentas = "DELETE FROM cuentas_banco_gastos WHERE gasto_cuenta_id = ?";
        $stmt_eliminar_cuentas = mysqli_prepare($conexion, $query_eliminar_cuentas);
        mysqli_stmt_bind_param($stmt_eliminar_cuentas, 'i', $id_gasto);
        mysqli_stmt_execute($stmt_eliminar_cuentas);
        mysqli_stmt_close($stmt_eliminar_cuentas);
        
        // 3. Eliminar tarjetas banco de la gasto
        $query_eliminar_tarjetas = "DELETE FROM tarjetas_banco_gastos WHERE gasto_tarjeta_id = ?";
        $stmt_eliminar_tarjetas = mysqli_prepare($conexion, $query_eliminar_tarjetas);
        mysqli_stmt_bind_param($stmt_eliminar_tarjetas, 'i', $id_gasto);
        mysqli_stmt_execute($stmt_eliminar_tarjetas);
        mysqli_stmt_close($stmt_eliminar_tarjetas);
        
        // 4. Eliminar sucursales de la gasto (si existen)
        $query_eliminar_sucursales = "DELETE FROM sucursales WHERE gasto_sucursal_id = ?";
        $stmt_eliminar_sucursales = mysqli_prepare($conexion, $query_eliminar_sucursales);
        mysqli_stmt_bind_param($stmt_eliminar_sucursales, 'i', $id_gasto);
        mysqli_stmt_execute($stmt_eliminar_sucursales);
        mysqli_stmt_close($stmt_eliminar_sucursales);
        
        // 5. Finalmente, eliminar la gasto
        $query_eliminar_gasto = "DELETE FROM gastos WHERE id_gasto = ?";
        $stmt_eliminar_gasto = mysqli_prepare($conexion, $query_eliminar_gasto);
        mysqli_stmt_bind_param($stmt_eliminar_gasto, 'i', $id_gasto);
        mysqli_stmt_execute($stmt_eliminar_gasto);
        mysqli_stmt_close($stmt_eliminar_gasto);
        
        // Confirmar transacción
        mysqli_commit($conexion);
        
        echo json_encode(array(
            'success' => true,
            'message' => 'Empresa "' . $gasto['nombre_gasto'] . '" eliminada correctamente junto con todos sus datos asociados'
        ));
        
    } catch (Exception $e) {
        // Revertir transacción en caso de error
        mysqli_rollback($conexion);
        throw $e;
    }
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(array(
        'success' => false,
        'message' => 'Error interno del servidor: ' . $e->getMessage()
    ));
} finally {
    if (isset($conexion)) {
        mysqli_close($conexion);
    }
}
?>
