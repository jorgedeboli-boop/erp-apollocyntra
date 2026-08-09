<?php
require_once '../../../include/session.php';

header('Content-Type: application/json');

try {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        http_response_code(405);
        echo json_encode(['success' => false, 'message' => 'Método no permitido']);
        exit;
    }

    $idGastoFijo = isset($_POST['id_gasto_fijo']) ? (int)$_POST['id_gasto_fijo'] : 0;
    $estado = isset($_POST['estado_gasto_fijo']) ? trim((string)$_POST['estado_gasto_fijo']) : '';

    if ($idGastoFijo <= 0 || ($estado !== 'true' && $estado !== 'false')) {
        http_response_code(422);
        echo json_encode(['success' => false, 'message' => 'Parámetros inválidos']);
        exit;
    }

    $conexion = conectar_bd();
    if (!$conexion) {
        throw new Exception('Error de conexión');
    }

    $stmt = mysqli_prepare($conexion, "UPDATE gastos_fijos SET estado_gasto_fijo = ? WHERE id_gasto_fijo = ? LIMIT 1");
    if (!$stmt) {
        throw new Exception('Error preparando UPDATE: ' . mysqli_error($conexion));
    }

    mysqli_stmt_bind_param($stmt, 'si', $estado, $idGastoFijo);
    if (!mysqli_stmt_execute($stmt)) {
        throw new Exception('Error actualizando estado: ' . mysqli_stmt_error($stmt));
    }

    mysqli_stmt_close($stmt);
    mysqli_close($conexion);

    echo json_encode(['success' => true, 'estado' => $estado]);
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}

