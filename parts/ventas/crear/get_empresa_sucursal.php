<?php
/**
 * Datos de la empresa del usuario logueado (o id_empresa por GET).
 * Ya no se resuelve vía sucursal.
 */
require_once '../../../include/session.php';
require_once '../../../include/functions.php';

header('Content-Type: application/json');

try {
    $id_empresa = isset($_GET['id_empresa']) ? (int) $_GET['id_empresa'] : 0;
    if ($id_empresa <= 0) {
        $id_empresa = obtener_rel_id_empresa_sesion();
    }
    if ($id_empresa <= 0) {
        throw new Exception('No se pudo determinar la empresa del usuario');
    }

    $conexion = conectar_bd();
    $query_empresa = 'SELECT * FROM empresas WHERE id_empresa = ? LIMIT 1';
    $stmt_empresa = mysqli_prepare($conexion, $query_empresa);
    if (!$stmt_empresa) {
        throw new Exception(mysqli_error($conexion));
    }
    mysqli_stmt_bind_param($stmt_empresa, 'i', $id_empresa);
    mysqli_stmt_execute($stmt_empresa);
    $result_empresa = mysqli_stmt_get_result($stmt_empresa);
    $empresa = $result_empresa ? mysqli_fetch_assoc($result_empresa) : null;
    mysqli_stmt_close($stmt_empresa);
    mysqli_close($conexion);

    if (!$empresa) {
        throw new Exception('No se encontró la empresa');
    }

    echo json_encode(array(
        'success' => true,
        'empresa' => $empresa,
    ));
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(array(
        'success' => false,
        'error' => $e->getMessage(),
    ));
}
