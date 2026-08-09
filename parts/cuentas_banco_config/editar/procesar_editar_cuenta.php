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

    $id_cuenta = isset($_POST['id_cuenta_banco']) ? (int) $_POST['id_cuenta_banco'] : 0;
    $numerocuenta = isset($_POST['numerocuenta']) ? substr(trim((string) $_POST['numerocuenta']), 0, 124) : '';
    $id_banco = isset($_POST['banco_cuenta']) ? (int) $_POST['banco_cuenta'] : 0;
    $empresa_cuenta_id = isset($_POST['empresa_cuenta_id']) ? (int) $_POST['empresa_cuenta_id'] : 0;
    $por_defecto = isset($_POST['por_defecto']) ? 'true' : 'false';
    $banco_cuenta = (string) $id_banco;

    if ($id_cuenta <= 0 || $numerocuenta === '' || $id_banco <= 0 || $empresa_cuenta_id <= 0) {
        echo json_encode(['success' => false, 'error' => 'Todos los campos son obligatorios']);
        exit;
    }

    $conexion = conectar_bd();
    mysqli_begin_transaction($conexion);

    try {
        if ($por_defecto === 'true') {
            $stmtReset = mysqli_prepare(
                $conexion,
                "UPDATE cuentas_banco_empresas SET por_defecto = 'false' WHERE empresa_cuenta_id = ? AND id_cuenta_banco <> ?"
            );
            mysqli_stmt_bind_param($stmtReset, 'ii', $empresa_cuenta_id, $id_cuenta);
            mysqli_stmt_execute($stmtReset);
            mysqli_stmt_close($stmtReset);
        }

        $stmt = mysqli_prepare(
            $conexion,
            'UPDATE cuentas_banco_empresas SET
                numerocuenta = ?,
                banco_cuenta = ?,
                empresa_cuenta_id = ?,
                por_defecto = ?
             WHERE id_cuenta_banco = ?
             LIMIT 1'
        );
        mysqli_stmt_bind_param(
            $stmt,
            'ssisi',
            $numerocuenta,
            $banco_cuenta,
            $empresa_cuenta_id,
            $por_defecto,
            $id_cuenta
        );
        if (!mysqli_stmt_execute($stmt)) {
            throw new Exception(mysqli_stmt_error($stmt));
        }
        mysqli_stmt_close($stmt);
        mysqli_commit($conexion);
        mysqli_close($conexion);

        echo json_encode([
            'success' => true,
            'message' => 'Cuenta actualizada correctamente',
            'redirect' => 'cuenta_banco_config.php?id=' . $id_cuenta,
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
