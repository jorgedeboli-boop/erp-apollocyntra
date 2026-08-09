<?php
require_once '../../../include/session.php';
require_once '../../../include/functions.php';
/**
 * Archivo para verificar si el teléfono o identificación ya existen
 * Versión para EDITAR - excluye el cliente actual
 * Compatible con PHP 7.0+
 */

// Asegurar que no haya salida antes del JSON
ob_clean();

header('Content-Type: application/json');

// Obtener parámetros
$action = $_GET['action'] ?? '';
$valor = $_GET['valor'] ?? '';
$id_cliente = $_GET['id_cliente'] ?? 0;

try {
    $conexion = conectar_bd();
    
    switch ($action) {
        case 'verificar_telefono':
            $resultado = verificarTelefono($conexion, $valor, $id_cliente);
            break;
            
        case 'verificar_identificacion':
            $resultado = verificarIdentificacion($conexion, $valor, $id_cliente);
            break;
            
        default:
            throw new Exception('Acción no válida: ' . $action);
    }
    
    mysqli_close($conexion);
    echo json_encode($resultado);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'error' => true,
        'message' => $e->getMessage()
    ]);
}

/**
 * Verificar si el teléfono ya existe (excluyendo el cliente actual)
 */
function verificarTelefono($conexion, $telefono, $id_cliente_actual) {
    if (empty($telefono)) {
        return [
            'existe' => false,
            'message' => ''
        ];
    }
    
    $sql = "SELECT id_cliente, nombre, apellido FROM clientes WHERE telefono = ? AND id_cliente != ?";
    $stmt = mysqli_prepare($conexion, $sql);
    mysqli_stmt_bind_param($stmt, 'si', $telefono, $id_cliente_actual);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    
    if ($row = mysqli_fetch_assoc($result)) {
        mysqli_stmt_close($stmt);
        return [
            'existe' => true,
            'message' => 'Este número de teléfono ya está registrado para el cliente: ' . $row['nombre'] . ' ' . $row['apellido'],
            'cliente' => $row
        ];
    } else {
        mysqli_stmt_close($stmt);
        return [
            'existe' => false,
            'message' => ''
        ];
    }
}

/**
 * Verificar si la identificación ya existe (excluyendo el cliente actual)
 */
function verificarIdentificacion($conexion, $identificacion, $id_cliente_actual) {
    if (empty($identificacion)) {
        return [
            'existe' => false,
            'message' => ''
        ];
    }
    
    $sql = "SELECT id_cliente, nombre, apellido, tipo_identificacion FROM clientes WHERE identificacion = ? AND id_cliente != ?";
    $stmt = mysqli_prepare($conexion, $sql);
    mysqli_stmt_bind_param($stmt, 'si', $identificacion, $id_cliente_actual);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    
    if ($row = mysqli_fetch_assoc($result)) {
        mysqli_stmt_close($stmt);
        return [
            'existe' => true,
            'message' => 'Esta identificación (' . $row['tipo_identificacion'] . ') ya está registrada para el cliente: ' . $row['nombre'] . ' ' . $row['apellido'],
            'cliente' => $row
        ];
    } else {
        mysqli_stmt_close($stmt);
        return [
            'existe' => false,
            'message' => ''
        ];
    }
}
?>

