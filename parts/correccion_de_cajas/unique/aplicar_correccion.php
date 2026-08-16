<?php
require_once '../../../include/session.php';
require_once '../../../include/functions.php';
require_once __DIR__ . '/correccion_cajas_helper.php';

header('Content-Type: application/json; charset=utf-8');

if (empty($_SESSION['usuario_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'No autorizado'], JSON_UNESCAPED_UNICODE);
    exit;
}

$idTabla = isset($_POST['id_tabla']) ? (int) $_POST['id_tabla'] : 0;
$fecha = isset($_POST['fecha']) ? trim($_POST['fecha']) : '';
$agregarApertura = !empty($_POST['agregar_apertura']);
$agregarCierre = !empty($_POST['agregar_cierre']);
$importeApertura = isset($_POST['importe_apertura']) ? (float) $_POST['importe_apertura'] : 0;
$importeCierre = isset($_POST['importe_cierre']) ? (float) $_POST['importe_cierre'] : 0;
$usuarioId = (int) $_SESSION['usuario_id'];

if ($idTabla <= 0 || $fecha === '' || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $fecha)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Parámetros inválidos'], JSON_UNESCAPED_UNICODE);
    exit;
}

if (!$agregarApertura && !$agregarCierre) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Seleccione al menos una corrección'], JSON_UNESCAPED_UNICODE);
    exit;
}

try {
    $conexion = conectar_bd();
    if (!$conexion) {
        throw new Exception('Error de conexión a la base de datos');
    }

    $tabla = correccion_cajas_tabla_movimientos($idTabla);
    if (!correccion_cajas_tabla_existe($conexion, $tabla)) {
        throw new Exception('No existe tabla de movimientos para esta caja');
    }

    if ($agregarApertura && correccion_cajas_tiene_apertura_dia($conexion, $tabla, $fecha)) {
        throw new Exception('Ya existe apertura de caja para este día');
    }
    if ($agregarCierre && correccion_cajas_tiene_cierre_dia($conexion, $tabla, $fecha)) {
        throw new Exception('Ya existe cierre de caja para este día');
    }
    if ($agregarCierre && !$agregarApertura && !correccion_cajas_tiene_apertura_dia($conexion, $tabla, $fecha)) {
        throw new Exception('Debe agregar primero la apertura de caja');
    }

    mysqli_begin_transaction($conexion);

    $fechaTexto = date('d-m-Y', strtotime($fecha));

    if ($agregarApertura) {
        $idCierreAnterior = correccion_cajas_obtener_id_cierre_dia_anterior($conexion, $tabla, $fecha);
        $idInsercionApertura = $idCierreAnterior !== null ? $idCierreAnterior + 1 : 1;

        if ($idCierreAnterior === null) {
            $queryMin = "SELECT MIN(id_movimientos) AS min_id FROM `{$tabla}` WHERE fecha_apunte = ?";
            $stmtMin = mysqli_prepare($conexion, $queryMin);
            mysqli_stmt_bind_param($stmtMin, 's', $fecha);
            mysqli_stmt_execute($stmtMin);
            $rowMin = mysqli_fetch_assoc(mysqli_stmt_get_result($stmtMin));
            mysqli_stmt_close($stmtMin);
            $minIdDia = (int) ($rowMin['min_id'] ?? 0);
            if ($minIdDia > 0) {
                $idInsercionApertura = $minIdDia;
            }
        }

        correccion_cajas_insertar_movimiento(
            $conexion,
            $tabla,
            $idInsercionApertura,
            'CAJA INICIO',
            'Apertura de caja (corrección) del ' . $fechaTexto,
            $importeApertura,
            0,
            $usuarioId,
            $fecha,
            '08:00:00',
            'false'
        );
    }

    if ($agregarCierre) {
        $ultimoIdDia = correccion_cajas_obtener_ultimo_id_del_dia($conexion, $tabla, $fecha);
        $idInsercionCierre = $ultimoIdDia + 1;

        correccion_cajas_insertar_movimiento(
            $conexion,
            $tabla,
            $idInsercionCierre,
            'CAJA FINAL',
            'Cierre de caja (corrección) del ' . $fechaTexto,
            0,
            $importeCierre,
            $usuarioId,
            $fecha,
            '23:59:00',
            'true'
        );
    }

    mysqli_commit($conexion);
    mysqli_close($conexion);

    echo json_encode([
        'success' => true,
        'message' => 'Corrección aplicada correctamente',
    ], JSON_UNESCAPED_UNICODE);
} catch (Exception $e) {
    if (isset($conexion) && mysqli_ping($conexion)) {
        mysqli_rollback($conexion);
        mysqli_close($conexion);
    }

    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage(),
    ], JSON_UNESCAPED_UNICODE);
}
