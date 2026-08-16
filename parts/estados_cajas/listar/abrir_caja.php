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

if (!isset($_POST['id_tabla']) || empty($_POST['id_tabla'])) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => 'ID de caja no proporcionado'
    ]);
    exit;
}

// Verificar que se haya enviado el importe de apertura
if (!isset($_POST['importe_apertura'])) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => 'Importe de apertura no proporcionado'
    ]);
    exit;
}

$idTabla = intval($_POST['id_tabla']);
$importeApertura = floatval($_POST['importe_apertura']);
$usuarioId = $_SESSION['usuario_id'];

try {
    $conexion = conectar_bd();
    
    if (!$conexion) {
        throw new Exception('Error de conexión a la base de datos');
    }
    
    // 1. Verificar que exista un cierre de caja en el mismo día
    $tableName = "movimientos_de_caja_" . $idTabla;
    
    // Verificar si la tabla existe
    $tableCheck = mysqli_query($conexion, "SHOW TABLES LIKE '$tableName'");
    
    if (mysqli_num_rows($tableCheck) > 0) {
        // Buscar si hay un cierre de caja del día actual (ordenar por ID para consistencia)
        $queryCierre = "SELECT COUNT(*) as tiene_cierre 
                       FROM $tableName 
                       WHERE cierre_caja = 'true' 
                       AND fecha_apunte = CURDATE() 
                       ORDER BY id_movimientos DESC";
        $resultCierre = mysqli_query($conexion, $queryCierre);
        
        if ($resultCierre) {
            $rowCierre = mysqli_fetch_assoc($resultCierre);
            if ($rowCierre['tiene_cierre'] == 0) {
                // No hay cierre del día actual
                mysqli_close($conexion);
                echo json_encode([
                    'success' => false,
                    'message' => 'No se puede abrir la caja. Debe realizar un cierre de caja del día de hoy primero.'
                ]);
                exit;
            }
        }
    } else {
        // Si no existe la tabla de movimientos, no se puede abrir
        mysqli_close($conexion);
        echo json_encode([
            'success' => false,
            'message' => 'No existe tabla de movimientos para esta caja'
        ]);
        exit;
    }
    
    $grupos_caja = 'CAJA INICIO';
    $concepto_caja = 'Apertura de caja';
    $total_entrada = $importeApertura;
    $total_salida = 0;
    
    mysqli_close($conexion);
    
    try {
        insertar_movimiento_caja($grupos_caja, $concepto_caja, $total_entrada, $total_salida, $usuarioId, $idTabla);
    } catch (Exception $e) {
        throw new Exception('Error al insertar movimiento de caja: ' . $e->getMessage());
    }
    
    // Respuesta exitosa
    echo json_encode([
        'success' => true,
        'message' => 'Caja abierta correctamente'
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

