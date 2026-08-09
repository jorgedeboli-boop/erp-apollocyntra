<?php
/**
 * Crea un traspaso pendiente y añade el artículo desde la ficha (origen = sucursal actual del artículo).
 */
require_once __DIR__ . '/../../../include/session.php';
require_once __DIR__ . '/../../../include/functions.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('HTTP/1.0 405 Method Not Allowed');
    exit;
}

$id_articulo = isset($_POST['id_articulo']) ? (int) $_POST['id_articulo'] : 0;
$id_sucursal_traspaso = isset($_POST['id_sucursal_traspaso']) ? (int) $_POST['id_sucursal_traspaso'] : 0;
$id_sucursal_destino = isset($_POST['id_sucursal_destino']) ? (int) $_POST['id_sucursal_destino'] : 0;

$sucursal_origen = $id_sucursal_destino;
$sucursal_destino_sel = $id_sucursal_traspaso;

if ($id_articulo <= 0 || $sucursal_destino_sel <= 0 || $sucursal_origen <= 0) {
    header('Location: ' . rtrim(APP_URL, '/') . '/articulo.php?id=' . $id_articulo);
    exit;
}

if ($sucursal_origen === $sucursal_destino_sel) {
    header('Location: ' . rtrim(APP_URL, '/') . '/articulo.php?id=' . $id_articulo . '&err=traspaso_misma_sucursal');
    exit;
}

$creado_por = isset($usuario_id) ? (int) $usuario_id : 0;
if ($creado_por <= 0) {
    header('Location: ' . rtrim(APP_URL, '/') . '/login.php');
    exit;
}

$conexion = conectar_bd();
mysqli_begin_transaction($conexion);

try {
    $st = mysqli_prepare(
        $conexion,
        "SELECT id, id_sucursal_destino, estado FROM articulos_venta WHERE id = ? LIMIT 1"
    );
    if (!$st) {
        throw new Exception(mysqli_error($conexion));
    }
    mysqli_stmt_bind_param($st, 'i', $id_articulo);
    mysqli_stmt_execute($st);
    $res = mysqli_stmt_get_result($st);
    $rowArt = mysqli_fetch_assoc($res);
    mysqli_stmt_close($st);

    if (!$rowArt) {
        throw new Exception('Artículo no encontrado.');
    }
    if (strtolower((string) $rowArt['estado']) !== 'enventa') {
        throw new Exception('El artículo debe estar en venta para traspasarlo.');
    }
    if ((int) $rowArt['id_sucursal_destino'] !== $sucursal_origen) {
        throw new Exception('La sucursal de origen no coincide con la ubicación del artículo.');
    }

    $chk = mysqli_prepare(
        $conexion,
        'SELECT id_sucursal FROM sucursal WHERE id_sucursal = ? AND estado_tienda = \'habilitada\' LIMIT 1'
    );
    mysqli_stmt_bind_param($chk, 'i', $sucursal_origen);
    mysqli_stmt_execute($chk);
    $r1 = mysqli_stmt_get_result($chk);
    $ok1 = mysqli_fetch_assoc($r1);
    mysqli_stmt_close($chk);
    $chk2 = mysqli_prepare(
        $conexion,
        'SELECT id_sucursal FROM sucursal WHERE id_sucursal = ? AND estado_tienda = \'habilitada\' LIMIT 1'
    );
    mysqli_stmt_bind_param($chk2, 'i', $sucursal_destino_sel);
    mysqli_stmt_execute($chk2);
    $r2 = mysqli_stmt_get_result($chk2);
    $ok2 = mysqli_fetch_assoc($r2);
    mysqli_stmt_close($chk2);
    if (!$ok1 || !$ok2) {
        throw new Exception('Sucursales no válidas o no habilitadas.');
    }

    $stOtro = mysqli_prepare(
        $conexion,
        "SELECT t.id_traspaso
         FROM rel_articulos_traspaso r
         INNER JOIN traspasos t ON t.id_traspaso = r.id_traspaso_rel
         WHERE r.id_articulo_rel = ?
           AND t.estado_traspaso IN ('PENDIENTEDERECIBIR', 'PENDIENTEENVIO')
         LIMIT 1"
    );
    if (!$stOtro) {
        throw new Exception(mysqli_error($conexion));
    }
    mysqli_stmt_bind_param($stOtro, 'i', $id_articulo);
    mysqli_stmt_execute($stOtro);
    $ro = mysqli_stmt_get_result($stOtro);
    if ($ro && mysqli_fetch_assoc($ro)) {
        mysqli_stmt_close($stOtro);
        throw new Exception(
            'Este artículo ya está en otro traspaso pendiente y no puede trasladarse de nuevo.'
        );
    }
    mysqli_stmt_close($stOtro);

    $sql = "INSERT INTO traspasos (
                sucursal_traspaso,
                sucursal_destino,
                fecha_traspaso,
                creado_por,
                estado_traspaso,
                skus_traspaso,
                total_articulos_traspaso,
                traspaso_web
            ) VALUES (?, ?, NOW(), ?, 'PENDIENTEENVIO', '', 0, 'false')";

    $stmt = mysqli_prepare($conexion, $sql);
    if (!$stmt) {
        throw new Exception(mysqli_error($conexion));
    }
    mysqli_stmt_bind_param($stmt, 'iii', $sucursal_origen, $sucursal_destino_sel, $creado_por);
    if (!mysqli_stmt_execute($stmt)) {
        mysqli_stmt_close($stmt);
        throw new Exception('No se pudo crear el traspaso: ' . mysqli_error($conexion));
    }
    $id_traspaso = (int) mysqli_insert_id($conexion);
    mysqli_stmt_close($stmt);

    if ($id_traspaso <= 0) {
        throw new Exception('No se obtuvo el id del traspaso.');
    }

    $ins = mysqli_prepare(
        $conexion,
        'INSERT INTO rel_articulos_traspaso (
            id_articulo_rel,
            id_traspaso_rel,
            sucursal_origen_rel,
            sucursal_destino_rel,
            fecha_creacion_rel
        ) VALUES (?, ?, ?, ?, NOW())'
    );
    if (!$ins) {
        throw new Exception('Error INSERT rel: ' . mysqli_error($conexion));
    }
    mysqli_stmt_bind_param($ins, 'iiii', $id_articulo, $id_traspaso, $sucursal_origen, $sucursal_destino_sel);
    if (!mysqli_stmt_execute($ins)) {
        mysqli_stmt_close($ins);
        throw new Exception('No se pudo guardar la línea: ' . mysqli_error($conexion));
    }
    mysqli_stmt_close($ins);

    $skus_nuevo = (string) $id_articulo;
    $up = mysqli_prepare(
        $conexion,
        'UPDATE traspasos SET skus_traspaso = ?, total_articulos_traspaso = 1 WHERE id_traspaso = ?'
    );
    if (!$up) {
        throw new Exception(mysqli_error($conexion));
    }
    mysqli_stmt_bind_param($up, 'si', $skus_nuevo, $id_traspaso);
    if (!mysqli_stmt_execute($up)) {
        mysqli_stmt_close($up);
        throw new Exception('No se pudo actualizar skus: ' . mysqli_error($conexion));
    }
    mysqli_stmt_close($up);

    mysqli_commit($conexion);
    mysqli_close($conexion);

    header('Location: ' . rtrim(APP_URL, '/') . '/editar_traspaso.php?id=' . $id_traspaso);
    exit;
} catch (Exception $e) {
    mysqli_rollback($conexion);
    mysqli_close($conexion);
    $msg = rawurlencode($e->getMessage());
    header('Location: ' . rtrim(APP_URL, '/') . '/articulo.php?id=' . $id_articulo . '&err=' . $msg);
    exit;
}
