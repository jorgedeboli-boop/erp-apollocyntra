<?php
/**
 * Archivo para generar código de autorización para editar porcentaje de recompra
 */

require_once '../../../include/session.php';

header('Content-Type: application/json');

// Verificar conexión a la base de datos
$conexion = conectar_bd();

if (!$conexion) {
    echo json_encode([
        'success' => false,
        'error' => 'Error de conexión a la base de datos'
    ]);
    exit();
}

// Verificar que se hayan enviado los datos necesarios
if (!isset($_POST['sucursal_autorizacion']) || empty($_POST['sucursal_autorizacion'])) {
    mysqli_close($conexion);
    echo json_encode([
        'success' => false,
        'error' => 'No se recibió la sucursal'
    ]);
    exit();
}

if (!isset($_POST['lote_autorizacion']) || empty($_POST['lote_autorizacion'])) {
    mysqli_close($conexion);
    echo json_encode([
        'success' => false,
        'error' => 'No se recibió el lote'
    ]);
    exit();
}

if (!isset($_POST['intereses_originales'])) {
    mysqli_close($conexion);
    echo json_encode([
        'success' => false,
        'error' => 'No se recibieron los intereses originales'
    ]);
    exit();
}

if (!isset($_POST['nuevo_porcentaje_recompra'])) {
    mysqli_close($conexion);
    echo json_encode([
        'success' => false,
        'error' => 'No se recibió el nuevo porcentaje de recompra'
    ]);
    exit();
}

$sucursal_autorizacion = intval($_POST['sucursal_autorizacion']);
$usuario_autorizacion = isset($_SESSION['usuario_id']) ? intval($_SESSION['usuario_id']) : 0;
$lote_autorizacion = intval($_POST['lote_autorizacion']);
$intereses_originales = floatval($_POST['intereses_originales']);
$nuevo_porcentaje_recompra = intval($_POST['nuevo_porcentaje_recompra']);

try {
    // Función para generar clave (adaptada a funciones modernas de PHP)
    function generar_clave($longitud) {
        $cadena = "/[^A-Z0-9]/";
        $clave1 = preg_replace($cadena, "", md5(rand()));
        $clave2 = preg_replace($cadena, "", md5(rand()));
        $clave3 = preg_replace($cadena, "", md5(rand()));
        return substr($clave1 . $clave2 . $clave3, 0, $longitud);
    }
    
    $codigo_autorizacion = generar_clave(5);
    
    
    // Insertar en la tabla autorizaciones_porcentajes
    $query = "INSERT INTO autorizaciones_porcentajes (
        sucursal_autorizacion,
        usuario_autorizacion,
        codigo_autorizacion,
        lote_autorizacion,
        intereses_originales,
        intereses_lote,
        fecha_autorizacion
    ) VALUES (?, ?, ?, ?, ?, ?, NOW())";
    
    $stmt = mysqli_prepare($conexion, $query);
    
    if (!$stmt) {
        throw new Exception("Error al preparar consulta: " . mysqli_error($conexion));
    }
    
    mysqli_stmt_bind_param($stmt, 'iisidi',
        $sucursal_autorizacion,
        $usuario_autorizacion,
        $codigo_autorizacion,
        $lote_autorizacion,
        $intereses_originales,
        $nuevo_porcentaje_recompra
    );
    
    if (!mysqli_stmt_execute($stmt)) {
        throw new Exception("Error al insertar autorización: " . mysqli_stmt_error($stmt));
    }
    
    $idauto = mysqli_insert_id($conexion);
    mysqli_stmt_close($stmt);
    mysqli_close($conexion);

    // Insertar notificación
    insertar_notificacion($usuario_autorizacion, $sucursal_autorizacion, 222, $idauto );
    
    // Respuesta exitosa
    echo json_encode([
        'success' => true,
        'id_autorizacion' => $idauto,
        'codigo_autorizacion' => $codigo_autorizacion
    ]);
    
} catch (Exception $e) {
    mysqli_close($conexion);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
?>

