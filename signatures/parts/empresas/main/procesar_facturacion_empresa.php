<?php
/**
 * Actualiza factura digital y régimen (empresas).
 */
require_once '../../../include/session.php';
require_once '../../../include/functions.php';

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-cache, must-revalidate');

try {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        http_response_code(405);
        echo json_encode(array('success' => false, 'message' => 'Método no permitido'));
        exit;
    }

    $id_empresa = isset($_POST['id_empresa']) ? (int)$_POST['id_empresa'] : 0;
    if (!$id_empresa) {
        echo json_encode(array('success' => false, 'message' => 'ID de empresa no válido'));
        exit;
    }

    $factura_digital = isset($_POST['factura_digital']) ? trim($_POST['factura_digital']) : 'false';
    if ($factura_digital !== 'true' && $factura_digital !== 'false') {
        $factura_digital = 'false';
    }

    $region_regimen = isset($_POST['region_regimen']) ? trim($_POST['region_regimen']) : 'false';
    $regiones_validas = array('false', 'General', 'Verifactu', 'TicketBAIBizkaia', 'TicketBAIAlava', 'TicketBAIGipuzkua');
    if (!in_array($region_regimen, $regiones_validas, true)) {
        $region_regimen = 'false';
    }

    $fecha_raw = isset($_POST['fecha_inicio_factura_digital']) ? trim($_POST['fecha_inicio_factura_digital']) : '';
    $fecha_inicio_factura_digital = null;
    if ($fecha_raw !== '') {
        $dt = DateTime::createFromFormat('Y-m-d', $fecha_raw);
        if ($dt && $dt->format('Y-m-d') === $fecha_raw) {
            $fecha_inicio_factura_digital = $fecha_raw;
        }
    }

    $conexion = conectar_bd();

    $sql = "UPDATE empresas SET 
        factura_digital = ?,
        region_regimen = ?,
        fecha_inicio_factura_digital = ?
        WHERE id_empresa = ?";

    $stmt = mysqli_prepare($conexion, $sql);
    if (!$stmt) {
        throw new Exception(mysqli_error($conexion));
    }

    $fd = $factura_digital;
    $rr = $region_regimen;
    $fecha_bind = $fecha_inicio_factura_digital;
    $id_bind = $id_empresa;
    mysqli_stmt_bind_param($stmt, 'sssi', $fd, $rr, $fecha_bind, $id_bind);

    if (mysqli_stmt_execute($stmt)) {
        mysqli_stmt_close($stmt);
        mysqli_close($conexion);
        echo json_encode(array(
            'success' => true,
            'message' => 'Datos de facturación actualizados',
        ));
    } else {
        $err = mysqli_stmt_error($stmt);
        mysqli_stmt_close($stmt);
        mysqli_close($conexion);
        throw new Exception($err ?: 'Error al actualizar');
    }
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(array(
        'success' => false,
        'message' => $e->getMessage(),
    ));
}
