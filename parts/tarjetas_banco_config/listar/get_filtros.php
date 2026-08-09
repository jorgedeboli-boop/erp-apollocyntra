<?php
require_once '../../../include/session.php';
require_once '../../../include/functions.php';

header('Content-Type: application/json; charset=utf-8');

try {
    $conexion = conectar_bd();
    $bancos = [];
    $empresas = [];

    $rb = mysqli_query(
        $conexion,
        "SELECT id_banco, nombre_banco FROM bancos_config WHERE estado_banco = 'true' ORDER BY nombre_banco ASC"
    );
    if ($rb) {
        while ($row = mysqli_fetch_assoc($rb)) {
            $bancos[] = ['id' => (int) $row['id_banco'], 'text' => (string) $row['nombre_banco']];
        }
    }
    $re = mysqli_query($conexion, 'SELECT id_empresa, nombre_empresa FROM empresas ORDER BY nombre_empresa ASC');
    if ($re) {
        while ($row = mysqli_fetch_assoc($re)) {
            $empresas[] = ['id' => (int) $row['id_empresa'], 'text' => (string) $row['nombre_empresa']];
        }
    }
    mysqli_close($conexion);
    echo json_encode(['success' => true, 'bancos' => $bancos, 'empresas' => $empresas], JSON_UNESCAPED_UNICODE);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => $e->getMessage()], JSON_UNESCAPED_UNICODE);
}
