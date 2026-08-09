<?php
/**
 * Inserta nuevos precios oro 24k por proveedor de fundición.
 */

require_once __DIR__ . '/../../include/session.php';
require_once __DIR__ . '/../../include/functions.php';

header('Content-Type: application/json; charset=utf-8');

try {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        http_response_code(405);
        echo json_encode(['success' => false, 'message' => 'Método no permitido'], JSON_UNESCAPED_UNICODE);
        exit;
    }

    if (!isset($_SESSION['usuario_autenticado']) || $_SESSION['usuario_autenticado'] !== true) {
        http_response_code(401);
        echo json_encode(['success' => false, 'message' => 'No autorizado'], JSON_UNESCAPED_UNICODE);
        exit;
    }

    $precios = isset($_POST['precio_24k']) && is_array($_POST['precio_24k']) ? $_POST['precio_24k'] : [];
    $fechas = isset($_POST['fecha_standby']) && is_array($_POST['fecha_standby']) ? $_POST['fecha_standby'] : [];

    if (empty($precios)) {
        throw new Exception('No se recibieron precios para actualizar');
    }

    $usuarioAccion = isset($_SESSION['usuario_id']) ? (int) $_SESSION['usuario_id'] : 0;
    if ($usuarioAccion <= 0) {
        throw new Exception('Usuario de sesión no válido');
    }

    $conexion = conectar_bd();
    if (!$conexion) {
        throw new Exception('Sin conexión a la base de datos');
    }

    mysqli_begin_transaction($conexion);

    $stmtValidar = mysqli_prepare(
        $conexion,
        'SELECT nombre_proveedor FROM proveedores WHERE id_proveedor = ? AND fundicion = \'true\' LIMIT 1'
    );
    $stmtInsertSinFecha = mysqli_prepare(
        $conexion,
        'INSERT INTO precios_oro_proveedores (precio_gramo_24k, precio_gramo_0725, proveedor_id, metal, timestamp_api, usuario_accion) VALUES (?, ?, ?, ?, ?, ?)'
    );
    $stmtInsertConFecha = mysqli_prepare(
        $conexion,
        'INSERT INTO precios_oro_proveedores (precio_gramo_24k, precio_gramo_0725, proveedor_id, metal, fecha_standby, timestamp_api, usuario_accion) VALUES (?, ?, ?, ?, ?, ?, ?)'
    );

    if (!$stmtValidar || !$stmtInsertSinFecha || !$stmtInsertConFecha) {
        throw new Exception(mysqli_error($conexion));
    }

    $metal = 'oro';
    $timestampApi = time();
    $insertados = 0;

    foreach ($precios as $proveedorIdRaw => $precioRaw) {
        $proveedorId = (int) $proveedorIdRaw;
        if ($proveedorId <= 0) {
            continue;
        }

        $precioTexto = trim((string) $precioRaw);
        if ($precioTexto === '') {
            throw new Exception('El precio 24kl es obligatorio para todos los proveedores');
        }

        if (strpos($precioTexto, ',') !== false) {
            $precio = (float) str_replace(',', '.', str_replace('.', '', $precioTexto));
        } else {
            $precio = (float) str_replace(',', '.', $precioTexto);
        }

        if ($precio <= 0) {
            mysqli_stmt_bind_param($stmtValidar, 'i', $proveedorId);
            mysqli_stmt_execute($stmtValidar);
            $resNombre = mysqli_stmt_get_result($stmtValidar);
            $rowNombre = $resNombre ? mysqli_fetch_assoc($resNombre) : null;
            if ($resNombre) {
                mysqli_free_result($resNombre);
            }
            $nombreProv = isset($rowNombre['nombre_proveedor']) ? $rowNombre['nombre_proveedor'] : 'proveedor';
            throw new Exception('El precio 24kl debe ser mayor que 0 (' . $nombreProv . ')');
        }

        mysqli_stmt_bind_param($stmtValidar, 'i', $proveedorId);
        mysqli_stmt_execute($stmtValidar);
        $resValidar = mysqli_stmt_get_result($stmtValidar);
        $rowValidar = $resValidar ? mysqli_fetch_assoc($resValidar) : null;
        if ($resValidar) {
            mysqli_free_result($resValidar);
        }

        if (!$rowValidar) {
            throw new Exception('Proveedor no válido o sin fundición');
        }

        $fechaStandbyYmd = null;
        $fechaInput = isset($fechas[$proveedorIdRaw]) ? trim((string) $fechas[$proveedorIdRaw]) : '';
        if ($fechaInput !== '') {
            $fechaStandbyYmd = parse_fecha_dmY_a_ymd($fechaInput);
            if ($fechaStandbyYmd === null) {
                throw new Exception('Fecha standby no válida para ' . $rowValidar['nombre_proveedor']);
            }
        }

        if ($fechaStandbyYmd !== null) {
            $precio0725 = round($precio * 0.725, 4);
            mysqli_stmt_bind_param($stmtInsertConFecha, 'ddissii', $precio, $precio0725, $proveedorId, $metal, $fechaStandbyYmd, $timestampApi, $usuarioAccion);
            if (!mysqli_stmt_execute($stmtInsertConFecha)) {
                throw new Exception(mysqli_stmt_error($stmtInsertConFecha));
            }
        } else {
            $precio0725 = round($precio * 0.725, 4);
            mysqli_stmt_bind_param($stmtInsertSinFecha, 'ddisii', $precio, $precio0725, $proveedorId, $metal, $timestampApi, $usuarioAccion);
            if (!mysqli_stmt_execute($stmtInsertSinFecha)) {
                throw new Exception(mysqli_stmt_error($stmtInsertSinFecha));
            }
        }

        $insertados++;
    }

    if ($insertados === 0) {
        throw new Exception('No se actualizó ningún proveedor');
    }

    mysqli_commit($conexion);
    mysqli_stmt_close($stmtValidar);
    mysqli_stmt_close($stmtInsertSinFecha);
    mysqli_stmt_close($stmtInsertConFecha);
    mysqli_close($conexion);

    echo json_encode([
        'success' => true,
        'message' => 'El precio fue actualizado con éxito',
        'insertados' => $insertados,
    ], JSON_UNESCAPED_UNICODE);
} catch (Exception $e) {
    if (isset($conexion) && $conexion) {
        mysqli_rollback($conexion);
        mysqli_close($conexion);
    }

    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage(),
    ], JSON_UNESCAPED_UNICODE);
}
