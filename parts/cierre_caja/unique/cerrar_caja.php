<?php
require_once '../../../include/session.php';
require_once '../../../include/functions.php';

// Verificar que el usuario esté autenticado
if (!isset($_SESSION['usuario_id'])) {
    http_response_code(401);
    echo json_encode([
        'success' => false,
        'message' => 'No autorizado'
    ]);
    exit;
}

// Verificar que se hayan enviado los parámetros
if (!isset($_POST['id_sucursal']) || empty($_POST['id_sucursal'])) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => 'ID de sucursal no proporcionado'
    ]);
    exit;
}

if (!isset($_POST['total_caja'])) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => 'Total de caja no proporcionado'
    ]);
    exit;
}

$idSucursal = intval($_POST['id_sucursal']);
$totalCaja = floatval($_POST['total_caja']);
$usuarioId = $_SESSION['usuario_id'];

try {
    $conexion = conectar_bd();
    
    if (!$conexion) {
        throw new Exception('Error de conexión a la base de datos');
    }
    
    // Iniciar transacción
    mysqli_begin_transaction($conexion);
    
    // 1. Actualizar el estado de la caja en la tabla sucursal
    $queryUpdate = "UPDATE sucursal SET caja_cerrada = 'true' WHERE id_sucursal = ?";
    $stmtUpdate = mysqli_prepare($conexion, $queryUpdate);
    
    if (!$stmtUpdate) {
        throw new Exception('Error al preparar consulta de actualización: ' . mysqli_error($conexion));
    }
    
    mysqli_stmt_bind_param($stmtUpdate, 'i', $idSucursal);
    $resultUpdate = mysqli_stmt_execute($stmtUpdate);
    mysqli_stmt_close($stmtUpdate);
    
    if (!$resultUpdate) {
        throw new Exception('Error al actualizar el estado de la caja');
    }
    
    // Confirmar transacción
    mysqli_commit($conexion);
    mysqli_close($conexion);
    
    // 2. Insertar movimiento de cierre manualmente (con cierre_caja = 'true')
    $conexion2 = conectar_bd();
    $tablaMovimientos = "movimientos_de_caja_" . $idSucursal;
    
    $queryInsertMovimiento = "INSERT INTO $tablaMovimientos (
        grupos,
        concepto,
        entrada,
        salida,
        usuario,
        fecha_apunte,
        hora_de_apunte,
        cierre_caja
    ) VALUES (
        'CAJA FINAL',
        'Cierre de caja',
        0,
        ?,
        ?,
        CURDATE(),
        CURTIME(),
        'true'
    )";
    
    $stmtMovimiento = mysqli_prepare($conexion2, $queryInsertMovimiento);
    
    if (!$stmtMovimiento) {
        throw new Exception('Error al preparar consulta de movimiento: ' . mysqli_error($conexion2));
    }
    
    mysqli_stmt_bind_param($stmtMovimiento, 'di', $totalCaja, $usuarioId);
    
    if (!mysqli_stmt_execute($stmtMovimiento)) {
        throw new Exception('Error al insertar movimiento de cierre: ' . mysqli_stmt_error($stmtMovimiento));
    }
    
    mysqli_stmt_close($stmtMovimiento);
    mysqli_close($conexion2);
    
    // Respuesta exitosa
    echo json_encode([
        'success' => true,
        'message' => 'Caja cerrada correctamente'
    ]);
    
} catch (Exception $e) {
    // Revertir transacción si hay error
    if (isset($conexion) && mysqli_ping($conexion)) {
        mysqli_rollback($conexion);
        mysqli_close($conexion);
    }
    
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
?>

