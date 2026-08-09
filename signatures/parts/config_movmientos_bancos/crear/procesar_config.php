<?php
require_once '../../../include/session.php';
require_once '../../../include/functions.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ../../../config_movmientos_bancos.php');
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

$nombre_config = isset($_POST['nombre_config']) ? substr(trim((string) $_POST['nombre_config']), 0, 124) : '';
$rel_id_tipo_movimiento = isset($_POST['rel_id_tipo_movimiento']) ? (int) $_POST['rel_id_tipo_movimiento'] : 0;
$tipo_config = isset($_POST['tipo_config']) ? trim((string) $_POST['tipo_config']) : '';
$estado_config = isset($_POST['estado_config']) ? 'true' : 'false';

if ($nombre_config === '' || $rel_id_tipo_movimiento <= 0 || !in_array($tipo_config, $tiposOk, true)) {
    $_SESSION['error'] = 'Todos los campos son obligatorios';
    header('Location: ../../../crear_config_movmiento_banco.php');
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
        'INSERT INTO config_movimientos_bancos
            (nombre_config, rel_id_tipo_movimiento, tipo_config, estado_config, fecha_creacion)
         VALUES (?, ?, ?, ?, CURDATE())'
    );
    mysqli_stmt_bind_param(
        $stmt,
        'siss',
        $nombre_config,
        $rel_id_tipo_movimiento,
        $tipo_config,
        $estado_config
    );
    if (!mysqli_stmt_execute($stmt)) {
        throw new Exception(mysqli_stmt_error($stmt));
    }
    $id_nuevo = (int) mysqli_insert_id($conexion);
    mysqli_stmt_close($stmt);
    mysqli_commit($conexion);
    mysqli_close($conexion);

    $_SESSION['success'] = 'Configuración creada correctamente';
    header('Location: ../../../config_movmiento_banco.php?id=' . $id_nuevo);
    exit;
} catch (Exception $e) {
    mysqli_rollback($conexion);
    mysqli_close($conexion);
    $_SESSION['error'] = 'Error al crear: ' . $e->getMessage();
    header('Location: ../../../crear_config_movmiento_banco.php');
    exit;
}
