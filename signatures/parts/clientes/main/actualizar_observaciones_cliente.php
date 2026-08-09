<?php
/**
 * Actualiza solo observaciones en datos_clientes (rel_id_cliente = id_cliente).
 */

require_once '../../../include/session.php';
require_once '../../../include/functions.php';

ob_start();
ob_clean();

header('Content-Type: application/json; charset=utf-8');

try {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        http_response_code(405);
        echo json_encode(['success' => false, 'error' => 'Método no permitido']);
        exit;
    }

    $id_cliente = isset($_POST['id_cliente']) ? (int) $_POST['id_cliente'] : 0;
    $observaciones = isset($_POST['observaciones']) ? (string) $_POST['observaciones'] : '';

    if ($id_cliente <= 0) {
        throw new Exception('ID de cliente no válido');
    }

    $conexion = conectar_bd();
    if (!$conexion) {
        throw new Exception('No se pudo conectar a la base de datos.');
    }

    $stmtSel = mysqli_prepare(
        $conexion,
        'SELECT id_datos_cliente FROM datos_clientes WHERE rel_id_cliente = ? LIMIT 1'
    );
    if (!$stmtSel) {
        throw new Exception('Error al consultar datos_clientes: ' . mysqli_error($conexion));
    }
    mysqli_stmt_bind_param($stmtSel, 'i', $id_cliente);
    mysqli_stmt_execute($stmtSel);
    $resSel = mysqli_stmt_get_result($stmtSel);
    $existe = $resSel && mysqli_num_rows($resSel) > 0;
    mysqli_stmt_close($stmtSel);

    if (!$existe) {
        throw new Exception('No existe registro en datos_clientes para este cliente.');
    }

    $stmtUp = mysqli_prepare(
        $conexion,
        'UPDATE datos_clientes SET observaciones = ? WHERE rel_id_cliente = ?'
    );
    if (!$stmtUp) {
        throw new Exception('Error al preparar actualización: ' . mysqli_error($conexion));
    }
    mysqli_stmt_bind_param($stmtUp, 'si', $observaciones, $id_cliente);
    if (!mysqli_stmt_execute($stmtUp)) {
        throw new Exception('Error al guardar: ' . mysqli_stmt_error($stmtUp));
    }
    mysqli_stmt_close($stmtUp);

    echo json_encode([
        'success' => true,
        'message' => 'Comentarios actualizados correctamente.',
    ]);
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage(),
    ]);
} finally {
    if (isset($conexion) && $conexion instanceof mysqli) {
        mysqli_close($conexion);
    }
}
