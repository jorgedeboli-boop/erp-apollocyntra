<?php
/**
 * Duplica una factura con el siguiente número correlativo de la sucursal.
 */

require_once '../../../include/session.php';
require_once '../../../include/functions.php';

header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Método no permitido']);
    exit;
}

$id_factura = isset($_POST['id_factura']) ? (int)$_POST['id_factura'] : 0;
if (!$id_factura) {
    echo json_encode(['success' => false, 'message' => 'Falta id_factura']);
    exit;
}

try {
    $conexion = conectar_bd();
    
    $row = mysqli_fetch_assoc(mysqli_query($conexion, "SELECT * FROM facturas WHERE id_factura = " . $id_factura));
    if (!$row) {
        echo json_encode(['success' => false, 'message' => 'Factura no encontrada']);
        exit;
    }
    
    $id_sucursal = (int)$row['id_sucursal'];
    $res = mysqli_query($conexion, "SELECT COALESCE(MAX(numero_factura), 0) + 1 AS siguiente FROM facturas WHERE id_sucursal = " . $id_sucursal);
    $next = mysqli_fetch_assoc($res);
    $numero_factura = (int)$next['siguiente'];
    
    $campos = "id_sucursal, numero_factura, cliente_factura, facturado_por, estado_factura, tipo_pago_factura, total_factura, fecha_factura, hora_factura, rel_id_venta, fecha_anulacion, prefijo_factura, tipo_factura, rel_id_lote, rel_id_renovacion, factura_regimen, id_rel_factura_fiskaly";
    $vals = "'" . mysqli_real_escape_string($conexion, $id_sucursal) . "','" . $numero_factura . "','" . mysqli_real_escape_string($conexion, $row['cliente_factura']) . "','" . mysqli_real_escape_string($conexion, $row['facturado_por']) . "','nopagada','" . mysqli_real_escape_string($conexion, $row['tipo_pago_factura']) . "','" . mysqli_real_escape_string($conexion, $row['total_factura']) . "','" . date('Y-m-d') . "','" . date('H:i:s') . "','0','0000-00-00 00:00:00','" . mysqli_real_escape_string($conexion, $row['prefijo_factura']) . "','" . mysqli_real_escape_string($conexion, $row['tipo_factura']) . "','" . (int)$row['rel_id_lote'] . "','" . (int)$row['rel_id_renovacion'] . "','" . mysqli_real_escape_string($conexion, $row['factura_regimen']) . "','0'";
    
    $insert = "INSERT INTO facturas ($campos) VALUES ($vals)";
    if (!mysqli_query($conexion, $insert)) {
        throw new Exception(mysqli_error($conexion));
    }
    $nuevo_id = mysqli_insert_id($conexion);
    mysqli_close($conexion);
    
    echo json_encode(['success' => true, 'message' => 'Factura duplicada. Nº ' . $numero_factura, 'id_factura' => $nuevo_id]);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>
