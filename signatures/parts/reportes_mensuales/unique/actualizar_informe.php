<?php
require_once '../../../include/session.php';
require_once '../../../include/functions.php';

header('Content-Type: application/json; charset=utf-8');

if (!isset($_SESSION['usuario_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'No autorizado']);
    exit;
}

$id_informe = isset($_POST['id_informe']) ? (int) $_POST['id_informe'] : 0;
$total_gastos = isset($_POST['total_gastos']) ? str_replace(',', '.', trim($_POST['total_gastos'])) : null;
$yulinfo = isset($_POST['yulinfo']) ? str_replace(',', '.', trim($_POST['yulinfo'])) : null;

if ($id_informe <= 0) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Informe no válido']);
    exit;
}

if ($total_gastos === null || $total_gastos === '' || !is_numeric($total_gastos)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Total gastos no válido']);
    exit;
}

if ($yulinfo === null || $yulinfo === '' || !is_numeric($yulinfo)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Yulinfo no válido']);
    exit;
}

$total_gastos = (float) $total_gastos;
$yulinfo = (float) $yulinfo;

function formatear_euro_informe_mensual($valor)
{
    return number_format((float) $valor, 2, ',', '.') . ' €';
}

try {
    $conexion = conectar_bd();
    if (!$conexion) {
        throw new Exception('Error de conexión a la base de datos');
    }

    $stmt = mysqli_prepare(
        $conexion,
        "UPDATE informe_mensual SET total_gastos = ?, yulinfo = ? WHERE id_informe = ? LIMIT 1"
    );

    if (!$stmt) {
        throw new Exception('Error al preparar la actualización');
    }

    mysqli_stmt_bind_param($stmt, 'ddi', $total_gastos, $yulinfo, $id_informe);

    if (!mysqli_stmt_execute($stmt)) {
        mysqli_stmt_close($stmt);
        throw new Exception('No se pudo actualizar el informe');
    }

    mysqli_stmt_close($stmt);
    mysqli_close($conexion);

    echo json_encode([
        'success' => true,
        'message' => 'Informe actualizado correctamente',
        'total_gastos' => $total_gastos,
        'yulinfo' => $yulinfo,
        'total_gastos_formatted' => formatear_euro_informe_mensual($total_gastos),
        'yulinfo_formatted' => formatear_euro_informe_mensual($yulinfo)
    ]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
