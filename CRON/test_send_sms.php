<?php

/**
 * Prueba manual de envio SMS a matermedia (solo para depuracion).
 */

ini_set('display_errors', '1');
error_reporting(E_ALL);
header('Content-Type: text/html; charset=utf-8');

$mensajeEnvio = 'test';
$telefonoDestino = '0034644174243';

$conexionMatermedia = @mysqli_connect(
    'mysql-5707.dinaserver.com',
    'sd3ref4df',
    'Soul@7891',
    'goldservicemater'
);

if (!$conexionMatermedia) {
    http_response_code(500);
    echo 'ERROR conexion matermedia: ' . htmlspecialchars(mysqli_connect_error(), ENT_QUOTES, 'UTF-8');
    exit;
}

mysqli_set_charset($conexionMatermedia, 'utf8');

$sql = "INSERT INTO send_sms_clientes (
    mensaje_envio,
    telefono_destino,
    estado_envio,
    fecha_envio
) VALUES (?, ?, 'pendiente', NOW())";

$stmt = mysqli_prepare($conexionMatermedia, $sql);

if (!$stmt) {
    http_response_code(500);
    echo 'ERROR prepare: ' . htmlspecialchars(mysqli_error($conexionMatermedia), ENT_QUOTES, 'UTF-8');
    mysqli_close($conexionMatermedia);
    exit;
}

mysqli_stmt_bind_param($stmt, 'ss', $mensajeEnvio, $telefonoDestino);

if (!mysqli_stmt_execute($stmt)) {
    http_response_code(500);
    echo 'ERROR insert: ' . htmlspecialchars(mysqli_stmt_error($stmt), ENT_QUOTES, 'UTF-8');
    mysqli_stmt_close($stmt);
    mysqli_close($conexionMatermedia);
    exit;
}

$idInsert = (int) mysqli_insert_id($conexionMatermedia);

mysqli_stmt_close($stmt);
mysqli_close($conexionMatermedia);

echo 'OK: SMS de prueba insertado en send_sms_clientes (id=' . $idInsert . ')';
