<?php
require_once '../../../include/session.php';
require_once '../../../include/functions.php';

// Headers para JSON
header('Content-Type: application/json');
header('Cache-Control: no-cache, must-revalidate');

try {
    // Verificar permisos de empresas
    if (!puede_acceder_a('empresas')) {
        http_response_code(403);
        echo json_encode(array('success' => false, 'message' => 'Acceso denegado al módulo empresas'));
        exit;
    }
    
    // Verificar que el usuario sea usuario_root
    if (!isset($_SESSION['usuario_root']) || $_SESSION['usuario_root'] !== true) {
        http_response_code(403);
        echo json_encode(array('success' => false, 'message' => 'Solo los usuarios administradores pueden eliminar empresas'));
        exit;
    }
    
    // Verificar método POST
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        http_response_code(405);
        echo json_encode(array('success' => false, 'message' => 'Método no permitido'));
        exit;
    }
    
    // Obtener ID de la empresa
    $id_empresa = isset($_POST['id_empresa']) ? (int)$_POST['id_empresa'] : 0;
    
    if (!$id_empresa) {
        echo json_encode(array('success' => false, 'message' => 'ID de empresa no válido'));
        exit;
    }
    
    // Conectar a la base de datos
    $conexion = conectar_bd();
    
    // Verificar que la empresa existe
    $query_verificar = "SELECT nombre_empresa, logotipo_empresa FROM empresas WHERE id_empresa = ?";
    $stmt_verificar = mysqli_prepare($conexion, $query_verificar);
    mysqli_stmt_bind_param($stmt_verificar, 'i', $id_empresa);
    mysqli_stmt_execute($stmt_verificar);
    $result_verificar = mysqli_stmt_get_result($stmt_verificar);
    
    if (!$result_verificar || mysqli_num_rows($result_verificar) === 0) {
        mysqli_stmt_close($stmt_verificar);
        mysqli_close($conexion);
        echo json_encode(array('success' => false, 'message' => 'Empresa no encontrada'));
        exit;
    }
    
    $empresa = mysqli_fetch_assoc($result_verificar);
    mysqli_stmt_close($stmt_verificar);
    
    // Iniciar transacción
    mysqli_begin_transaction($conexion);
    
    try {
        // 1. Eliminar logotipo físico si existe
        if (!empty($empresa['logotipo_empresa'])) {
            $ruta_logotipo = '../../../photos/' . $empresa['logotipo_empresa'];
            if (file_exists($ruta_logotipo)) {
                unlink($ruta_logotipo);
            }
        }
        
        // 2. Eliminar cuentas bancarias de la empresa
        $query_eliminar_cuentas = "DELETE FROM cuentas_banco_empresas WHERE empresa_cuenta_id = ?";
        $stmt_eliminar_cuentas = mysqli_prepare($conexion, $query_eliminar_cuentas);
        mysqli_stmt_bind_param($stmt_eliminar_cuentas, 'i', $id_empresa);
        mysqli_stmt_execute($stmt_eliminar_cuentas);
        mysqli_stmt_close($stmt_eliminar_cuentas);
        
        // 3. Eliminar tarjetas banco de la empresa
        $query_eliminar_tarjetas = "DELETE FROM tarjetas_banco_empresas WHERE empresa_tarjeta_id = ?";
        $stmt_eliminar_tarjetas = mysqli_prepare($conexion, $query_eliminar_tarjetas);
        mysqli_stmt_bind_param($stmt_eliminar_tarjetas, 'i', $id_empresa);
        mysqli_stmt_execute($stmt_eliminar_tarjetas);
        mysqli_stmt_close($stmt_eliminar_tarjetas);
        
        // 4. Eliminar sucursales de la empresa (si existen)
        $query_eliminar_sucursales = "DELETE FROM sucursales WHERE empresa_sucursal_id = ?";
        $stmt_eliminar_sucursales = mysqli_prepare($conexion, $query_eliminar_sucursales);
        mysqli_stmt_bind_param($stmt_eliminar_sucursales, 'i', $id_empresa);
        mysqli_stmt_execute($stmt_eliminar_sucursales);
        mysqli_stmt_close($stmt_eliminar_sucursales);
        
        // 5. Finalmente, eliminar la empresa
        $query_eliminar_empresa = "DELETE FROM empresas WHERE id_empresa = ?";
        $stmt_eliminar_empresa = mysqli_prepare($conexion, $query_eliminar_empresa);
        mysqli_stmt_bind_param($stmt_eliminar_empresa, 'i', $id_empresa);
        mysqli_stmt_execute($stmt_eliminar_empresa);
        mysqli_stmt_close($stmt_eliminar_empresa);
        
        // Confirmar transacción
        mysqli_commit($conexion);
        
        echo json_encode(array(
            'success' => true,
            'message' => 'Empresa "' . $empresa['nombre_empresa'] . '" eliminada correctamente junto con todos sus datos asociados'
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
