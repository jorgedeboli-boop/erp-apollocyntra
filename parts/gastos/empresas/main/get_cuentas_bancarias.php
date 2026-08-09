<?php
require_once '../../../include/session.php';
require_once '../../../include/functions.php';

// Headers para JSON
header('Content-Type: application/json');
header('Cache-Control: no-cache, must-revalidate');

try {
    // Verificar permisos
    if (!puede_acceder_a('empresas')) {
        http_response_code(403);
        echo json_encode(array('success' => false, 'message' => 'Acceso denegado'));
        exit;
    }
    
    // Obtener ID de la empresa
    $id_empresa = isset($_GET['id_empresa']) ? (int)$_GET['id_empresa'] : 0;
    
    if (!$id_empresa) {
        echo json_encode(array('success' => false, 'message' => 'ID de empresa no válido'));
        exit;
    }
    
    // Conectar a la base de datos
    $conexion = conectar_bd();
    
    // Consulta para obtener las cuentas bancarias de la empresa
    $query = "SELECT 
                    id_cuenta_banco,
                    numerocuenta,
                    banco_cuenta,
                    empresa_cuenta_id,
                    fecha_creacion,
                    creado_por,
                    por_defecto
                FROM cuentas_banco_empresas 
                WHERE empresa_cuenta_id = ?
                ORDER BY por_defecto DESC, fecha_creacion DESC";
    
    $stmt = mysqli_prepare($conexion, $query);
    mysqli_stmt_bind_param($stmt, 'i', $id_empresa);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    
    $cuentas = array();
    if ($result) {
        while ($row = mysqli_fetch_assoc($result)) {
            $cuentas[] = array(
                'id_cuenta_banco' => $row['id_cuenta_banco'],
                'numerocuenta' => $row['numerocuenta'],
                'banco_cuenta' => $row['banco_cuenta'],
                'empresa_cuenta_id' => $row['empresa_cuenta_id'],
                'fecha_creacion' => $row['fecha_creacion'],
                'creado_por' => $row['creado_por'],
                'por_defecto' => $row['por_defecto']
            );
        }
    }
    
    mysqli_stmt_close($stmt);
    mysqli_close($conexion);
    
    echo json_encode(array(
        'success' => true,
        'cuentas' => $cuentas,
        'total' => count($cuentas)
    ));
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(array(
        'success' => false,
        'message' => 'Error interno del servidor: ' . $e->getMessage()
    ));
}
?>
