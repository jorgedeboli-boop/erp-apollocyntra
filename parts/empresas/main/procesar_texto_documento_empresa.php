<?php
/**
 * Actualiza un campo de texto de documentos de la empresa (whitelist).
 */
require_once '../../../include/session.php';
require_once '../../../include/functions.php';

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-cache, must-revalidate');

$sql_por_campo = array(
    'texto_contrato_compra'        => 'UPDATE empresas SET texto_contrato_compra = ? WHERE id_empresa = ?',
    'texto_contrato_empeno'        => 'UPDATE empresas SET texto_contrato_empeno = ? WHERE id_empresa = ?',
    'texto_facturas'               => 'UPDATE empresas SET texto_facturas = ? WHERE id_empresa = ?',
    'texto_facturas_oro_inversion' => 'UPDATE empresas SET texto_facturas_oro_inversion = ? WHERE id_empresa = ?',
    'texto_facturas_regular'       => 'UPDATE empresas SET texto_facturas_regular = ? WHERE id_empresa = ?',
);

try {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        http_response_code(405);
        echo json_encode(array('success' => false, 'message' => 'Método no permitido'));
        exit;
    }

    $id_empresa = isset($_POST['id_empresa']) ? (int)$_POST['id_empresa'] : 0;
    $campo = isset($_POST['campo']) ? trim($_POST['campo']) : '';

    if (!$id_empresa) {
        echo json_encode(array('success' => false, 'message' => 'ID de empresa no válido'));
        exit;
    }

    if (!isset($sql_por_campo[$campo])) {
        echo json_encode(array('success' => false, 'message' => 'Campo no permitido'));
        exit;
    }

    $texto = isset($_POST['texto']) ? $_POST['texto'] : '';
    if (!is_string($texto)) {
        $texto = '';
    }

    $conexion = conectar_bd();
    $sql = $sql_por_campo[$campo];
    $stmt = mysqli_prepare($conexion, $sql);
    if (!$stmt) {
        throw new Exception(mysqli_error($conexion));
    }

    mysqli_stmt_bind_param($stmt, 'si', $texto, $id_empresa);

    if (mysqli_stmt_execute($stmt)) {
        mysqli_stmt_close($stmt);
        mysqli_close($conexion);
        echo json_encode(array(
            'success' => true,
            'message' => 'Texto guardado',
            'campo'   => $campo,
        ));
    } else {
        $err = mysqli_stmt_error($stmt);
        mysqli_stmt_close($stmt);
        mysqli_close($conexion);
        throw new Exception($err ?: 'Error al guardar');
    }
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(array(
        'success' => false,
        'message' => $e->getMessage(),
    ));
}
