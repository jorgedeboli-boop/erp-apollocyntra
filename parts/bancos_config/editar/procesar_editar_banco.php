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

    $id_banco = isset($_POST['id_banco']) ? (int) $_POST['id_banco'] : 0;
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
        $id_banco <= 0
        || $nombre_banco === ''
        || $direccion_banco === ''
        || $pais_banco <= 0
        || $provincia_banco <= 0
        || $poblacion_banco <= 0
        || $telefono_banco === ''
        || $email_banco === ''
        || $contacto_banco === ''
    ) {
        echo json_encode(['success' => false, 'error' => 'Todos los campos son obligatorios']);
        exit;
    }

    if (!filter_var($email_banco, FILTER_VALIDATE_EMAIL)) {
        echo json_encode(['success' => false, 'error' => 'El formato del email no es válido']);
        exit;
    }

    $conexion = conectar_bd();
    mysqli_begin_transaction($conexion);

    try {
        $sql = 'UPDATE bancos_config SET
            nombre_banco = ?,
            direccion_banco = ?,
            provincia_banco = ?,
            poblacion_banco = ?,
            pais_banco = ?,
            estado_banco = ?,
            telefono_banco = ?,
            email_banco = ?,
            contacto_banco = ?
            WHERE id_banco = ?
            LIMIT 1';

        $stmt = mysqli_prepare($conexion, $sql);
        if (!$stmt) {
            throw new Exception(mysqli_error($conexion));
        }

        mysqli_stmt_bind_param(
            $stmt,
            'ssiiissssi',
            $nombre_banco,
            $direccion_banco,
            $provincia_banco,
            $poblacion_banco,
            $pais_banco,
            $estado_banco,
            $telefono_banco,
            $email_banco,
            $contacto_banco,
            $id_banco
        );

        if (!mysqli_stmt_execute($stmt)) {
            throw new Exception(mysqli_stmt_error($stmt));
        }

        mysqli_stmt_close($stmt);
        mysqli_commit($conexion);
        mysqli_close($conexion);

        echo json_encode([
            'success' => true,
            'message' => 'Banco actualizado correctamente',
            'id_banco' => $id_banco,
            'redirect' => 'banco_config.php?id=' . $id_banco,
        ], JSON_UNESCAPED_UNICODE);
    } catch (Exception $e) {
        mysqli_rollback($conexion);
        mysqli_close($conexion);
        throw $e;
    }
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage(),
    ], JSON_UNESCAPED_UNICODE);
}
