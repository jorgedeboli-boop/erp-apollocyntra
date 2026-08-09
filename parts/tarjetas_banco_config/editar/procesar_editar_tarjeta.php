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

    $id_tarjeta = isset($_POST['id_tarjeta_banco']) ? (int) $_POST['id_tarjeta_banco'] : 0;
    $numerotarjeta = isset($_POST['numerotarjeta']) ? substr(trim((string) $_POST['numerotarjeta']), 0, 124) : '';
    $id_banco = isset($_POST['banco_tarjeta']) ? (int) $_POST['banco_tarjeta'] : 0;
    $empresa_tarjeta_id = isset($_POST['empresa_tarjeta_id']) ? (int) $_POST['empresa_tarjeta_id'] : 0;
    $por_defecto = isset($_POST['por_defecto']) ? 'true' : 'false';
    $banco_tarjeta = (string) $id_banco;

    if ($id_tarjeta <= 0 || $numerotarjeta === '' || $id_banco <= 0 || $empresa_tarjeta_id <= 0) {
        echo json_encode(['success' => false, 'error' => 'Todos los campos son obligatorios']);
        exit;
    }

    $conexion = conectar_bd();
    mysqli_begin_transaction($conexion);

    try {
        if ($por_defecto === 'true') {
            $stmtReset = mysqli_prepare(
                $conexion,
                "UPDATE tarjetas_banco_empresas SET por_defecto = 'false' WHERE empresa_tarjeta_id = ? AND id_tarjeta_banco <> ?"
            );
            mysqli_stmt_bind_param($stmtReset, 'ii', $empresa_tarjeta_id, $id_tarjeta);
            mysqli_stmt_execute($stmtReset);
            mysqli_stmt_close($stmtReset);
        }

        $stmt = mysqli_prepare(
            $conexion,
            'UPDATE tarjetas_banco_empresas SET
                numerotarjeta = ?,
                banco_tarjeta = ?,
                empresa_tarjeta_id = ?,
                por_defecto = ?
             WHERE id_tarjeta_banco = ?
             LIMIT 1'
        );
        mysqli_stmt_bind_param(
            $stmt,
            'ssisi',
            $numerotarjeta,
            $banco_tarjeta,
            $empresa_tarjeta_id,
            $por_defecto,
            $id_tarjeta
        );
        if (!mysqli_stmt_execute($stmt)) {
            throw new Exception(mysqli_stmt_error($stmt));
        }
        mysqli_stmt_close($stmt);
        mysqli_commit($conexion);
        mysqli_close($conexion);

        echo json_encode([
            'success' => true,
            'message' => 'Tarjeta actualizada correctamente',
            'redirect' => 'tarjeta_banco_config.php?id=' . $id_tarjeta,
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
