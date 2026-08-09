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

// Verificar que se haya enviado el ID de sucursal
if (!isset($_POST['id_sucursal']) || empty($_POST['id_sucursal'])) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => 'ID de sucursal no proporcionado'
    ]);
    exit;
}

$idSucursal = intval($_POST['id_sucursal']);
$usuarioId = $_SESSION['usuario_id'];

// Obtener todos los valores del arqueo
$b500 = isset($_POST['billete500']) ? intval($_POST['billete500']) : 0;
$b200 = isset($_POST['billete200']) ? intval($_POST['billete200']) : 0;
$b100 = isset($_POST['billete100']) ? intval($_POST['billete100']) : 0;
$b50 = isset($_POST['billete50']) ? intval($_POST['billete50']) : 0;
$b20 = isset($_POST['billete20']) ? intval($_POST['billete20']) : 0;
$b10 = isset($_POST['billete10']) ? intval($_POST['billete10']) : 0;
$b5 = isset($_POST['billete5']) ? intval($_POST['billete5']) : 0;
$m2 = isset($_POST['moneda2']) ? intval($_POST['moneda2']) : 0;
$m1 = isset($_POST['moneda1']) ? intval($_POST['moneda1']) : 0;

$t500 = isset($_POST['t500']) ? floatval($_POST['t500']) : 0;
$t200 = isset($_POST['t200']) ? floatval($_POST['t200']) : 0;
$t100 = isset($_POST['t100']) ? floatval($_POST['t100']) : 0;
$t50 = isset($_POST['t50']) ? floatval($_POST['t50']) : 0;
$t20 = isset($_POST['t20']) ? floatval($_POST['t20']) : 0;
$t10 = isset($_POST['t10']) ? floatval($_POST['t10']) : 0;
$t5 = isset($_POST['t5']) ? floatval($_POST['t5']) : 0;
$t2 = isset($_POST['t2']) ? floatval($_POST['t2']) : 0;
$t1 = isset($_POST['t1']) ? floatval($_POST['t1']) : 0;

$t50cent = isset($_POST['t50cent']) ? floatval($_POST['t50cent']) : 0;
$t20cent = isset($_POST['t20cent']) ? floatval($_POST['t20cent']) : 0;
$t10cent = isset($_POST['t10cent']) ? floatval($_POST['t10cent']) : 0;
$t5cent = isset($_POST['t5cent']) ? floatval($_POST['t5cent']) : 0;
$t2cent = isset($_POST['t2cent']) ? floatval($_POST['t2cent']) : 0;
$t1cent = isset($_POST['t1cent']) ? floatval($_POST['t1cent']) : 0;

$cent50 = isset($_POST['50cent']) ? intval($_POST['50cent']) : 0;
$cent20 = isset($_POST['20cent']) ? intval($_POST['20cent']) : 0;
$cent10 = isset($_POST['10cent']) ? intval($_POST['10cent']) : 0;
$cent5 = isset($_POST['5cent']) ? intval($_POST['5cent']) : 0;
$cent2 = isset($_POST['2cent']) ? intval($_POST['2cent']) : 0;
$cent1 = isset($_POST['1cent']) ? intval($_POST['1cent']) : 0;

$efectivo = isset($_POST['efectivo']) ? floatval($_POST['efectivo']) : 0;
$caja = isset($_POST['caja']) ? floatval($_POST['caja']) : 0;
$diferencia = isset($_POST['diferencia']) ? floatval($_POST['diferencia']) : 0;

try {
    $conexion = conectar_bd();
    
    if (!$conexion) {
        throw new Exception('Error de conexión a la base de datos');
    }
    
    // Fecha y hora actual
    $ahora = date("d-m-Y H:i:s");
    $horaAhora = date("H:i:s");
    
    // Nombre de las tablas
    $tablaCierre = "cierre_caja_" . $idSucursal;
    $tablaMovimientos = "movimientos_de_caja_" . $idSucursal;
    
    // Iniciar transacción
    mysqli_begin_transaction($conexion);
    
    // 1. Insertar en cierre_caja_$sucursal (según estructura real de la tabla)
    $queryInsertCierre = "INSERT INTO $tablaCierre (
        fecha_cierre, caja,
        b500, b200, b100, b50, b20, b10, b5,
        m2, m1,
        efectivo, diferencia,
        t500, t200, t100, t50, t20, t10, t5,
        t2, t1,
        `10cent`, `50cent`, `20cent`, `5cent`,
        t5cent, t10cent, t20cent, t50cent,
        `2cent`, `1cent`,
        t2cent, t1cent, usuario_cierre
    ) VALUES (
        NOW(), ?,
        ?, ?, ?, ?, ?, ?, ?,
        ?, ?,
        ?, ?,
        ?, ?, ?, ?, ?, ?, ?,
        ?, ?,
        ?, ?, ?, ?,
        ?, ?, ?, ?,
        ?, ?,
        ?, ?, ?
    )";
    
    $stmtCierre = mysqli_prepare($conexion, $queryInsertCierre);
    
    if (!$stmtCierre) {
        throw new Exception('Error al preparar consulta de cierre: ' . mysqli_error($conexion));
    }
    
    // Tipos: d=decimal, i=int
    // caja(d), b500-b5(7i), m2(i), m1(d), efectivo(d), diferencia(d), 
    // t500-t5(7i), t2(i), t1(d), 10cent-5cent(4i), 
    // t5cent-t50cent(4d), 2cent-1cent(2i), t2cent-t1cent(2d)
    // TOTAL: 33 parámetros
    // caja(1d) + b500-b5(7i) + m2(1i) + m1(1d) + efectivo,diferencia(2d) + t500-t5(7i) + t2(1i) + t1(1d) + centimos(4i) + tcentimos(4d) + 2cent,1cent(2i) + t2cent,t1cent(2d) = 33
    mysqli_stmt_bind_param($stmtCierre, 'diiiiiiiidddiiiiiiiidiiiiddddiiddi',
        $caja,                                          // 1: d
        $b500, $b200, $b100, $b50, $b20, $b10, $b5,   // 2-8: iiiiiii (7)
        $m2,                                            // 9: i
        $m1,                                            // 10: d
        $efectivo, $diferencia,                         // 11-12: dd (2)
        $t500, $t200, $t100, $t50, $t20, $t10, $t5,   // 13-19: iiiiiii (7)
        $t2,                                            // 20: i
        $t1,                                            // 21: d
        $cent10, $cent50, $cent20, $cent5,             // 22-25: iiii (4)
        $t5cent, $t10cent, $t20cent, $t50cent,         // 26-29: dddd (4)
        $cent2, $cent1,                                 // 30-31: ii (2)
        $t2cent, $t1cent ,                                // 33-34: dd (2)
        $usuarioId                                        // 35: i
    );
    
    if (!mysqli_stmt_execute($stmtCierre)) {
        throw new Exception('Error al insertar cierre de caja: ' . mysqli_stmt_error($stmtCierre));
    }
    mysqli_stmt_close($stmtCierre);
    
    // 2. Insertar movimiento en movimientos_de_caja_$sucursal
    $conceptoArqueo = "Arqueo del " . $ahora . ". / Caja: " . round($caja, 2) . " € / Efectivo: " . round($efectivo, 2) . " € / Diferencia: " . round($diferencia, 2) . " €";
    
    $queryInsertMovimiento = "INSERT INTO $tablaMovimientos (
        grupos,
        concepto,
        usuario,
        fecha_apunte,
        hora_de_apunte
    ) VALUES (
        'Arqueos',
        ?,
        ?,
        CURDATE(),
        ?
    )";
    
    $stmtMovimiento = mysqli_prepare($conexion, $queryInsertMovimiento);
    
    if (!$stmtMovimiento) {
        throw new Exception('Error al preparar consulta de movimiento: ' . mysqli_error($conexion));
    }
    
    mysqli_stmt_bind_param($stmtMovimiento, 'sis', $conceptoArqueo, $usuarioId, $horaAhora);
    
    if (!mysqli_stmt_execute($stmtMovimiento)) {
        throw new Exception('Error al insertar movimiento de arqueo: ' . mysqli_stmt_error($stmtMovimiento));
    }
    mysqli_stmt_close($stmtMovimiento);
    
    // 3. Actualizar estado de la caja en sucursal (marcarla como abierta)
    $queryUpdateSucursal = "UPDATE sucursal SET caja_cerrada = 'false' WHERE id_sucursal = ?";
    $stmtUpdate = mysqli_prepare($conexion, $queryUpdateSucursal);
    
    if (!$stmtUpdate) {
        throw new Exception('Error al preparar consulta de actualización: ' . mysqli_error($conexion));
    }
    
    mysqli_stmt_bind_param($stmtUpdate, 'i', $idSucursal);
    
    if (!mysqli_stmt_execute($stmtUpdate)) {
        throw new Exception('Error al actualizar estado de caja: ' . mysqli_stmt_error($stmtUpdate));
    }
    mysqli_stmt_close($stmtUpdate);
    
    // Confirmar transacción
    mysqli_commit($conexion);
    mysqli_close($conexion);
    
    // Respuesta exitosa
    echo json_encode([
        'success' => true,
        'message' => 'Arqueo de caja guardado correctamente'
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

