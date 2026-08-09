<?php
require_once '../../../include/session.php';
require_once '../../../include/functions.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ../../../tarjetas_banco_config.php');
    exit;
}

function tarjeta_solo_digitos($valor)
{
    return preg_replace('/\D+/', '', (string) $valor);
}

function tarjeta_luhn_valido($numero)
{
    $digitos = tarjeta_solo_digitos($numero);
    $len = strlen($digitos);
    if ($len < 13 || $len > 19) {
        return false;
    }
    $suma = 0;
    $alt = false;
    for ($i = $len - 1; $i >= 0; $i--) {
        $n = (int) $digitos[$i];
        if ($alt) {
            $n *= 2;
            if ($n > 9) {
                $n -= 9;
            }
        }
        $suma += $n;
        $alt = !$alt;
    }
    return ($suma % 10) === 0;
}

$numerotarjeta = tarjeta_solo_digitos(isset($_POST['numerotarjeta']) ? $_POST['numerotarjeta'] : '');
$nombre_tarjeta = isset($_POST['nombre_tarjeta']) ? substr(trim((string) $_POST['nombre_tarjeta']), 0, 124) : '';
$mes_vencimiento = isset($_POST['mes_vencimiento']) ? preg_replace('/\D+/', '', (string) $_POST['mes_vencimiento']) : '';
$ano_vencimiento = isset($_POST['ano_vencimiento']) ? preg_replace('/\D+/', '', (string) $_POST['ano_vencimiento']) : '';
$cvv = tarjeta_solo_digitos(isset($_POST['cvv']) ? $_POST['cvv'] : '');
$id_banco = isset($_POST['banco_tarjeta']) ? (int) $_POST['banco_tarjeta'] : 0;
$empresa_tarjeta_id = isset($_POST['empresa_tarjeta_id']) ? (int) $_POST['empresa_tarjeta_id'] : 0;
$sucursal_tarjeta_id = isset($_POST['sucursal_tarjeta_id']) ? (int) $_POST['sucursal_tarjeta_id'] : 0;
$por_defecto = isset($_POST['por_defecto']) ? 'true' : 'false';
$creado_por = (int) $usuario_id;
$banco_tarjeta = (string) $id_banco;

$mes_vencimiento = str_pad($mes_vencimiento, 2, '0', STR_PAD_LEFT);
$mesInt = (int) $mes_vencimiento;
$anoInt = (int) $ano_vencimiento;
$anoActual = (int) date('Y');

if ($nombre_tarjeta === '' || $id_banco <= 0 || $empresa_tarjeta_id <= 0) {
    $_SESSION['error'] = 'Nombre, banco y empresa son obligatorios';
    header('Location: ../../../crear_tarjeta_banco_config.php');
    exit;
}

if (!tarjeta_luhn_valido($numerotarjeta)) {
    $_SESSION['error'] = 'El número de tarjeta no es válido';
    header('Location: ../../../crear_tarjeta_banco_config.php');
    exit;
}

if ($mesInt < 1 || $mesInt > 12) {
    $_SESSION['error'] = 'El mes de vencimiento no es válido';
    header('Location: ../../../crear_tarjeta_banco_config.php');
    exit;
}

if ($anoInt < $anoActual || $anoInt > ($anoActual + 25)) {
    $_SESSION['error'] = 'El año de vencimiento no es válido';
    header('Location: ../../../crear_tarjeta_banco_config.php');
    exit;
}

if ($anoInt === $anoActual && $mesInt < (int) date('n')) {
    $_SESSION['error'] = 'La tarjeta está caducada';
    header('Location: ../../../crear_tarjeta_banco_config.php');
    exit;
}

if (!preg_match('/^\d{3,4}$/', $cvv)) {
    $_SESSION['error'] = 'El CVV debe tener 3 o 4 dígitos';
    header('Location: ../../../crear_tarjeta_banco_config.php');
    exit;
}

$numerotarjeta = substr($numerotarjeta, 0, 19);
$mes_vencimiento = str_pad((string) $mesInt, 2, '0', STR_PAD_LEFT);
$ano_vencimiento = (string) $anoInt;
$cvv = substr($cvv, 0, 4);

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
    mysqli_stmt_bind_param($stmtE, 'i', $empresa_tarjeta_id);
    mysqli_stmt_execute($stmtE);
    if (!mysqli_fetch_assoc(mysqli_stmt_get_result($stmtE))) {
        mysqli_stmt_close($stmtE);
        throw new Exception('La empresa seleccionada no existe');
    }
    mysqli_stmt_close($stmtE);

    if ($sucursal_tarjeta_id > 0) {
        $stmtS = mysqli_prepare(
            $conexion,
            'SELECT id_sucursal FROM sucursal WHERE id_sucursal = ? AND empresa_id = ? LIMIT 1'
        );
        mysqli_stmt_bind_param($stmtS, 'ii', $sucursal_tarjeta_id, $empresa_tarjeta_id);
        mysqli_stmt_execute($stmtS);
        if (!mysqli_fetch_assoc(mysqli_stmt_get_result($stmtS))) {
            mysqli_stmt_close($stmtS);
            throw new Exception('La sucursal no pertenece a la empresa seleccionada');
        }
        mysqli_stmt_close($stmtS);
    } else {
        $sucursal_tarjeta_id = null;
    }

    if ($por_defecto === 'true') {
        $stmtReset = mysqli_prepare(
            $conexion,
            "UPDATE tarjetas_banco_empresas SET por_defecto = 'false' WHERE empresa_tarjeta_id = ?"
        );
        mysqli_stmt_bind_param($stmtReset, 'i', $empresa_tarjeta_id);
        mysqli_stmt_execute($stmtReset);
        mysqli_stmt_close($stmtReset);
    }

    $stmt = mysqli_prepare(
        $conexion,
        'INSERT INTO tarjetas_banco_empresas
            (numerotarjeta, nombre_tarjeta, mes_vencimiento, ano_vencimiento, cvv,
             banco_tarjeta, empresa_tarjeta_id, sucursal_tarjeta_id, fecha_creacion, creado_por, por_defecto)
         VALUES (?, ?, ?, ?, ?, ?, ?, ?, CURDATE(), ?, ?)'
    );
    mysqli_stmt_bind_param(
        $stmt,
        'ssssssiiss',
        $numerotarjeta,
        $nombre_tarjeta,
        $mes_vencimiento,
        $ano_vencimiento,
        $cvv,
        $banco_tarjeta,
        $empresa_tarjeta_id,
        $sucursal_tarjeta_id,
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

    $_SESSION['success'] = 'Tarjeta creada correctamente';
    header('Location: ../../../tarjeta_banco_config.php?id=' . $id_nuevo);
    exit;
} catch (Exception $e) {
    mysqli_rollback($conexion);
    mysqli_close($conexion);
    $_SESSION['error'] = 'Error al crear la tarjeta: ' . $e->getMessage();
    header('Location: ../../../crear_tarjeta_banco_config.php');
    exit;
}
