<?php
require_once '../../../include/session.php';
require_once '../../../include/functions.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ../../../cuentas_banco_config.php');
    exit;
}

$numerocuenta = isset($_POST['numerocuenta']) ? substr(trim((string) $_POST['numerocuenta']), 0, 124) : '';
$id_banco = isset($_POST['banco_cuenta']) ? (int) $_POST['banco_cuenta'] : 0;
$empresa_cuenta_id = isset($_POST['empresa_cuenta_id']) ? (int) $_POST['empresa_cuenta_id'] : 0;
$sucursal_cuenta_id = isset($_POST['sucursal_cuenta_id']) ? (int) $_POST['sucursal_cuenta_id'] : 0;
$por_defecto = isset($_POST['por_defecto']) ? 'true' : 'false';
$creado_por = (int) $usuario_id;
$banco_cuenta = (string) $id_banco;

if ($numerocuenta === '' || $id_banco <= 0 || $empresa_cuenta_id <= 0) {
    $_SESSION['error'] = 'Todos los campos son obligatorios';
    header('Location: ../../../crear_cuenta_banco_config.php');
    exit;
}

$conexion = conectar_bd();
mysqli_begin_transaction($conexion);

try {
    $stmtB = mysqli_prepare($conexion, 'SELECT id_banco FROM bancos_config WHERE id_banco = ? LIMIT 1');
    mysqli_stmt_bind_param($stmtB, 'i', $id_banco);
    mysqli_stmt_execute($stmtB);
    if (!mysqli_fetch_assoc(mysqli_stmt_get_result($stmtB))) {
        mysqli_stmt_close($stmtB);
        throw new Exception('El banco seleccionado no existe');
    }
    mysqli_stmt_close($stmtB);

    $stmtE = mysqli_prepare($conexion, 'SELECT id_empresa FROM empresas WHERE id_empresa = ? LIMIT 1');
    mysqli_stmt_bind_param($stmtE, 'i', $empresa_cuenta_id);
    mysqli_stmt_execute($stmtE);
    if (!mysqli_fetch_assoc(mysqli_stmt_get_result($stmtE))) {
        mysqli_stmt_close($stmtE);
        throw new Exception('La empresa seleccionada no existe');
    }
    mysqli_stmt_close($stmtE);

    if ($sucursal_cuenta_id > 0) {
        $stmtS = mysqli_prepare(
            $conexion,
            'SELECT id_sucursal FROM sucursal WHERE id_sucursal = ? AND empresa_id = ? LIMIT 1'
        );
        mysqli_stmt_bind_param($stmtS, 'ii', $sucursal_cuenta_id, $empresa_cuenta_id);
        mysqli_stmt_execute($stmtS);
        if (!mysqli_fetch_assoc(mysqli_stmt_get_result($stmtS))) {
            mysqli_stmt_close($stmtS);
            throw new Exception('La sucursal no pertenece a la empresa seleccionada');
        }
        mysqli_stmt_close($stmtS);
    } else {
        $sucursal_cuenta_id = null;
    }

    if ($por_defecto === 'true') {
        $stmtReset = mysqli_prepare(
            $conexion,
            "UPDATE cuentas_banco_empresas SET por_defecto = 'false' WHERE empresa_cuenta_id = ?"
        );
        mysqli_stmt_bind_param($stmtReset, 'i', $empresa_cuenta_id);
        mysqli_stmt_execute($stmtReset);
        mysqli_stmt_close($stmtReset);
    }

    $stmt = mysqli_prepare(
        $conexion,
        'INSERT INTO cuentas_banco_empresas
            (numerocuenta, banco_cuenta, empresa_cuenta_id, sucursal_cuenta_id, fecha_creacion, creado_por, por_defecto)
         VALUES (?, ?, ?, ?, CURDATE(), ?, ?)'
    );
    mysqli_stmt_bind_param(
        $stmt,
        'ssiiss',
        $numerocuenta,
        $banco_cuenta,
        $empresa_cuenta_id,
        $sucursal_cuenta_id,
        $creado_por,
        $por_defecto
    );
    if (!mysqli_stmt_execute($stmt)) {
        throw new Exception(mysqli_stmt_error($stmt));
    }
    $id_nuevo = (int) mysqli_insert_id($conexion);
    mysqli_stmt_close($stmt);
    mysqli_commit($conexion);
    mysqli_close($conexion);

    $_SESSION['success'] = 'Cuenta creada correctamente';
    header('Location: ../../../cuenta_banco_config.php?id=' . $id_nuevo);
    exit;
} catch (Exception $e) {
    mysqli_rollback($conexion);
    mysqli_close($conexion);
    $_SESSION['error'] = 'Error al crear la cuenta: ' . $e->getMessage();
    header('Location: ../../../crear_cuenta_banco_config.php');
    exit;
}
