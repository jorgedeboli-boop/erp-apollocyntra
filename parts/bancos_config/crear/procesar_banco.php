<?php
require_once '../../../include/session.php';
require_once '../../../include/functions.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ../../../bancos_config.php');
    exit;
}

$nombre_banco = isset($_POST['nombre_banco']) ? trim((string) $_POST['nombre_banco']) : '';
$direccion_banco = isset($_POST['direccion_banco']) ? trim((string) $_POST['direccion_banco']) : '';
$pais_banco = isset($_POST['pais']) ? (int) $_POST['pais'] : 0;
$provincia_banco = isset($_POST['c_provincia']) ? (int) $_POST['c_provincia'] : 0;
$poblacion_banco = isset($_POST['c_poblacion']) ? (int) $_POST['c_poblacion'] : 0;
$telefono_banco = isset($_POST['telefono_banco']) ? trim((string) $_POST['telefono_banco']) : '';
$email_banco = isset($_POST['email_banco']) ? trim((string) $_POST['email_banco']) : '';
$contacto_banco = isset($_POST['contacto_banco']) ? trim((string) $_POST['contacto_banco']) : '';
$estado_banco = isset($_POST['estado_banco']) ? 'true' : 'false';

$nombre_banco = substr($nombre_banco, 0, 124);
$direccion_banco = substr($direccion_banco, 0, 168);
$telefono_banco = substr($telefono_banco, 0, 64);
$email_banco = substr($email_banco, 0, 128);
$contacto_banco = substr($contacto_banco, 0, 164);

if (
    $nombre_banco === ''
    || $direccion_banco === ''
    || $pais_banco <= 0
    || $provincia_banco <= 0
    || $poblacion_banco <= 0
    || $telefono_banco === ''
    || $email_banco === ''
    || $contacto_banco === ''
) {
    $_SESSION['error'] = 'Todos los campos son obligatorios';
    header('Location: ../../../crear_banco_config.php');
    exit;
}

if (!filter_var($email_banco, FILTER_VALIDATE_EMAIL)) {
    $_SESSION['error'] = 'El formato del email no es válido';
    header('Location: ../../../crear_banco_config.php');
    exit;
}

$conexion = conectar_bd();
mysqli_begin_transaction($conexion);

try {
    $sql = 'INSERT INTO bancos_config (
        nombre_banco,
        direccion_banco,
        provincia_banco,
        poblacion_banco,
        pais_banco,
        estado_banco,
        telefono_banco,
        email_banco,
        contacto_banco,
        fecha_creacion
    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, CURDATE())';

    $stmt = mysqli_prepare($conexion, $sql);
    if (!$stmt) {
        throw new Exception(mysqli_error($conexion));
    }

    mysqli_stmt_bind_param(
        $stmt,
        'ssiiissss',
        $nombre_banco,
        $direccion_banco,
        $provincia_banco,
        $poblacion_banco,
        $pais_banco,
        $estado_banco,
        $telefono_banco,
        $email_banco,
        $contacto_banco
    );

    if (!mysqli_stmt_execute($stmt)) {
        throw new Exception(mysqli_stmt_error($stmt));
    }

    $id_banco = (int) mysqli_insert_id($conexion);
    mysqli_stmt_close($stmt);
    mysqli_commit($conexion);
    mysqli_close($conexion);

    $_SESSION['success'] = 'Banco creado correctamente';
    header('Location: ../../../banco_config.php?id=' . $id_banco);
    exit;
} catch (Exception $e) {
    mysqli_rollback($conexion);
    mysqli_close($conexion);
    $_SESSION['error'] = 'Error al crear el banco: ' . $e->getMessage();
    header('Location: ../../../crear_banco_config.php');
    exit;
}
