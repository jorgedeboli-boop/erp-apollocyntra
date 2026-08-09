<?php
require_once '../../../include/session.php';
require_once '../../../include/functions.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ../../../sellos.php');
    exit;
}

$nombre_sello = isset($_POST['nombre_sello']) ? trim((string) $_POST['nombre_sello']) : '';
$sello_logotipo = isset($_POST['sello_logotipo']) ? trim((string) $_POST['sello_logotipo']) : 'false';
$creado_por = isset($_SESSION['usuario_id']) ? (int) $_SESSION['usuario_id'] : 0;

$nombre_sello = substr($nombre_sello, 0, 164);

if ($nombre_sello === '') {
    $_SESSION['error'] = 'El nombre del sello es obligatorio';
    header('Location: ../../../crear_sello.php');
    exit;
}

if ($sello_logotipo !== 'true' && $sello_logotipo !== 'false') {
    $sello_logotipo = 'false';
}

if ($creado_por <= 0) {
    $_SESSION['error'] = 'Sesión no válida';
    header('Location: ../../../crear_sello.php');
    exit;
}

$conexion = conectar_bd();
if (!$conexion) {
    $_SESSION['error'] = 'Error de conexión a la base de datos';
    header('Location: ../../../crear_sello.php');
    exit;
}

mysqli_begin_transaction($conexion);

try {
    $sql = 'INSERT INTO sellos (
        nombre_sello,
        sello_logotipo,
        fecha_creacion,
        creado_por
    ) VALUES (?, ?, NOW(), ?)';

    $stmt = mysqli_prepare($conexion, $sql);
    if (!$stmt) {
        throw new Exception(mysqli_error($conexion));
    }

    mysqli_stmt_bind_param($stmt, 'ssi', $nombre_sello, $sello_logotipo, $creado_por);

    if (!mysqli_stmt_execute($stmt)) {
        throw new Exception(mysqli_stmt_error($stmt));
    }

    $id_sello = (int) mysqli_insert_id($conexion);
    mysqli_stmt_close($stmt);

    // Réplica opcional en Figueredo (como en la app antigua)
    if (function_exists('get_mysqli_figueredoapp')) {
        $mysqli_figueredoapp = get_mysqli_figueredoapp();
        if ($mysqli_figueredoapp) {
            $stmtFig = mysqli_prepare(
                $mysqli_figueredoapp,
                'INSERT INTO sellos (nombre_sello, sello_logotipo, fecha_creacion, creado_por) VALUES (?, ?, NOW(), ?)'
            );
            if ($stmtFig) {
                mysqli_stmt_bind_param($stmtFig, 'ssi', $nombre_sello, $sello_logotipo, $creado_por);
                mysqli_stmt_execute($stmtFig);
                mysqli_stmt_close($stmtFig);
            }
        }
    }

    mysqli_commit($conexion);
    mysqli_close($conexion);

    $_SESSION['success'] = 'Sello creado correctamente';
    header('Location: ../../../sello.php?id=' . $id_sello);
    exit;
} catch (Exception $e) {
    mysqli_rollback($conexion);
    mysqli_close($conexion);
    $_SESSION['error'] = 'Error al crear el sello: ' . $e->getMessage();
    header('Location: ../../../crear_sello.php');
    exit;
}
