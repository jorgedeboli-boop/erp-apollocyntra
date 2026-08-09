<?php
require_once '../../../include/session.php';
require_once '../../../include/functions.php';

header('Content-Type: application/json; charset=utf-8');

try {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        http_response_code(405);
        echo json_encode(['success' => false, 'error' => 'Método no permitido']);
        exit;
    }

    $tiposOk = [
        'transferencia_saliente',
        'transferencia_entrante',
        'cobro_tarjeta',
        'pago_tarjeta',
        'retiro_tarjeta',
        'retiro_cuenta',
        'ingreso_cuenta',
    ];

    $id_config = isset($_POST['id_config']) ? (int) $_POST['id_config'] : 0;
    $nombre_config = isset($_POST['nombre_config']) ? substr(trim((string) $_POST['nombre_config']), 0, 124) : '';
    $rel_id_tipo_movimiento = isset($_POST['rel_id_tipo_movimiento']) ? (int) $_POST['rel_id_tipo_movimiento'] : 0;
    $tipo_config = isset($_POST['tipo_config']) ? trim((string) $_POST['tipo_config']) : '';
    $estado_config = isset($_POST['estado_config']) ? 'true' : 'false';

    if ($id_config <= 0 || $nombre_config === '' || $rel_id_tipo_movimiento <= 0 || !in_array($tipo_config, $tiposOk, true)) {
        echo json_encode(['success' => false, 'error' => 'Todos los campos son obligatorios']);
        exit;
    }

    $conexion = conectar_bd();
    mysqli_begin_transaction($conexion);

    try {
        $stmtG = mysqli_prepare($conexion, 'SELECT id_grupo FROM grupos_movimientos WHERE id_grupo = ? LIMIT 1');
        mysqli_stmt_bind_param($stmtG, 'i', $rel_id_tipo_movimiento);
        mysqli_stmt_execute($stmtG);
        if (!mysqli_fetch_assoc(mysqli_stmt_get_result($stmtG))) {
            mysqli_stmt_close($stmtG);
            throw new Exception('El grupo de movimiento seleccionado no existe');
        }
        mysqli_stmt_close($stmtG);

        $stmt = mysqli_prepare(
            $conexion,
            'UPDATE config_movimientos_bancos SET
                nombre_config = ?,
                rel_id_tipo_movimiento = ?,
                tipo_config = ?,
                estado_config = ?
             WHERE id_config = ?
             LIMIT 1'
        );
        mysqli_stmt_bind_param(
            $stmt,
            'sissi',
            $nombre_config,
            $rel_id_tipo_movimiento,
            $tipo_config,
            $estado_config,
            $id_config
        );
        if (!mysqli_stmt_execute($stmt)) {
            throw new Exception(mysqli_stmt_error($stmt));
        }
        mysqli_stmt_close($stmt);
        mysqli_commit($conexion);
        mysqli_close($conexion);

        echo json_encode([
            'success' => true,
            'message' => 'Configuración actualizada correctamente',
            'redirect' => 'config_movmiento_banco.php?id=' . $id_config,
        ], JSON_UNESCAPED_UNICODE);
    } catch (Exception $e) {
        mysqli_rollback($conexion);
        mysqli_close($conexion);
        throw $e;
    }
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => $e->getMessage()], JSON_UNESCAPED_UNICODE);
}
