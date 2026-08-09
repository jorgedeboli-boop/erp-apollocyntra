<?php
/**
 * Traslada un movimiento de caja a movimientos_tarjeta, movimientos_transferencia o movimientos_bizum.
 */

require_once '../../../include/session.php';

header('Content-Type: application/json');

/**
 * @return float
 */
function traslado_caja_calcular_delta($entrada, $salida)
{
    if ($salida > 0) {
        return (float) $salida;
    }
    if ($entrada > 0) {
        return -1 * (float) $entrada;
    }

    return 0.0;
}

/**
 * @param mysqli $conexion
 * @param string $tabla
 * @param string $fechaApunte
 * @param float $delta
 */
function traslado_caja_ajustar_cierre_dia(mysqli $conexion, $tabla, $fechaApunte, $delta)
{
    if (abs($delta) < 0.00001) {
        return;
    }

    $query = "UPDATE `{$tabla}`
              SET salida = salida + ?
              WHERE fecha_apunte = ?
                AND cierre_caja = 'true'
                AND TRIM(grupos) = 'CAJA FINAL'";
    $stmt = mysqli_prepare($conexion, $query);
    if (!$stmt) {
        throw new Exception('Error al ajustar cierre del día: ' . mysqli_error($conexion));
    }
    mysqli_stmt_bind_param($stmt, 'ds', $delta, $fechaApunte);
    if (!mysqli_stmt_execute($stmt)) {
        $error = mysqli_stmt_error($stmt);
        mysqli_stmt_close($stmt);
        throw new Exception('Error al ajustar cierre del día: ' . $error);
    }
    mysqli_stmt_close($stmt);
}

/**
 * @param mysqli $conexion
 * @param string $tabla
 * @param string $fechaDesde
 * @param float $delta
 */
function traslado_caja_ajustar_dias_posteriores(mysqli $conexion, $tabla, $fechaDesde, $delta)
{
    if (abs($delta) < 0.00001) {
        return;
    }

    $queryFechas = "SELECT DISTINCT fecha_apunte
                    FROM `{$tabla}`
                    WHERE fecha_apunte > ?
                      AND (
                        TRIM(grupos) = 'CAJA INICIO'
                        OR (cierre_caja = 'true' AND TRIM(grupos) = 'CAJA FINAL')
                      )
                    ORDER BY fecha_apunte ASC";
    $stmtFechas = mysqli_prepare($conexion, $queryFechas);
    if (!$stmtFechas) {
        throw new Exception('Error al buscar días posteriores: ' . mysqli_error($conexion));
    }
    mysqli_stmt_bind_param($stmtFechas, 's', $fechaDesde);
    mysqli_stmt_execute($stmtFechas);
    $resultFechas = mysqli_stmt_get_result($stmtFechas);

    $fechas = [];
    while ($row = mysqli_fetch_assoc($resultFechas)) {
        $fechas[] = $row['fecha_apunte'];
    }
    mysqli_stmt_close($stmtFechas);

    foreach ($fechas as $fecha) {
        $queryInicio = "UPDATE `{$tabla}`
                        SET entrada = entrada + ?
                        WHERE fecha_apunte = ?
                          AND cierre_caja = 'false'
                          AND TRIM(grupos) = 'CAJA INICIO'";
        $stmtInicio = mysqli_prepare($conexion, $queryInicio);
        if (!$stmtInicio) {
            throw new Exception('Error al ajustar apertura: ' . mysqli_error($conexion));
        }
        mysqli_stmt_bind_param($stmtInicio, 'ds', $delta, $fecha);
        if (!mysqli_stmt_execute($stmtInicio)) {
            $error = mysqli_stmt_error($stmtInicio);
            mysqli_stmt_close($stmtInicio);
            throw new Exception('Error al ajustar apertura: ' . $error);
        }
        mysqli_stmt_close($stmtInicio);

        $queryFinal = "UPDATE `{$tabla}`
                       SET salida = salida + ?
                       WHERE fecha_apunte = ?
                         AND cierre_caja = 'true'
                         AND TRIM(grupos) = 'CAJA FINAL'";
        $stmtFinal = mysqli_prepare($conexion, $queryFinal);
        if (!$stmtFinal) {
            throw new Exception('Error al ajustar cierre: ' . mysqli_error($conexion));
        }
        mysqli_stmt_bind_param($stmtFinal, 'ds', $delta, $fecha);
        if (!mysqli_stmt_execute($stmtFinal)) {
            $error = mysqli_stmt_error($stmtFinal);
            mysqli_stmt_close($stmtFinal);
            throw new Exception('Error al ajustar cierre: ' . $error);
        }
        mysqli_stmt_close($stmtFinal);
    }
}

/**
 * Ajusta aperturas del mismo día posteriores al movimiento trasladado.
 *
 * @param mysqli $conexion
 * @param string $tabla
 * @param int $idMovimiento
 * @param string $fechaApunte
 * @param float $delta
 */
function traslado_caja_ajustar_aperturas_posteriores_mismo_dia(
    mysqli $conexion,
    $tabla,
    $idMovimiento,
    $fechaApunte,
    $delta
) {
    if (abs($delta) < 0.00001) {
        return;
    }

    $queryInicio = "UPDATE `{$tabla}`
                    SET entrada = entrada + ?
                    WHERE fecha_apunte = ?
                      AND id_movimientos > ?
                      AND cierre_caja = 'false'
                      AND TRIM(grupos) = 'CAJA INICIO'";
    $stmtInicio = mysqli_prepare($conexion, $queryInicio);
    if (!$stmtInicio) {
        throw new Exception('Error al ajustar aperturas del día: ' . mysqli_error($conexion));
    }
    mysqli_stmt_bind_param($stmtInicio, 'dsi', $delta, $fechaApunte, $idMovimiento);
    if (!mysqli_stmt_execute($stmtInicio)) {
        $error = mysqli_stmt_error($stmtInicio);
        mysqli_stmt_close($stmtInicio);
        throw new Exception('Error al ajustar aperturas del día: ' . $error);
    }
    mysqli_stmt_close($stmtInicio);
}

try {
    if (!isset($_POST['id_movimiento'], $_POST['id_sucursal'], $_POST['tipo'])) {
        throw new Exception('Parámetros incompletos');
    }

    $idMovimiento = (int) $_POST['id_movimiento'];
    $idSucursal = (int) $_POST['id_sucursal'];
    $tipo = strtolower(trim($_POST['tipo']));

    $tiposValidos = [
        'tarjeta' => 'tarjetas',
        'transferencia' => 'transferencia',
        'bizum' => 'bizum',
    ];

    if ($idMovimiento <= 0 || $idSucursal <= 0) {
        throw new Exception('Parámetros inválidos');
    }

    if (!isset($tiposValidos[$tipo])) {
        throw new Exception('Tipo de traslado no válido');
    }

    $tipoCajaTexto = $tiposValidos[$tipo];
    $tableName = 'movimientos_de_caja_' . $idSucursal;

    $conexion = conectar_bd();
    if (!$conexion) {
        throw new Exception('Error de conexión a la base de datos');
    }

    $checkTable = mysqli_query($conexion, "SHOW TABLES LIKE '" . mysqli_real_escape_string($conexion, $tableName) . "'");
    if (!$checkTable || mysqli_num_rows($checkTable) === 0) {
        throw new Exception('Tabla de movimientos no encontrada');
    }

    $queryMov = "SELECT id_movimientos, fecha_apunte, grupos, concepto, salida, entrada, cierre_caja
                 FROM `{$tableName}`
                 WHERE id_movimientos = ?";
    $stmtMov = mysqli_prepare($conexion, $queryMov);
    if (!$stmtMov) {
        throw new Exception('Error al obtener movimiento: ' . mysqli_error($conexion));
    }
    mysqli_stmt_bind_param($stmtMov, 'i', $idMovimiento);
    mysqli_stmt_execute($stmtMov);
    $resultMov = mysqli_stmt_get_result($stmtMov);
    $movimiento = mysqli_fetch_assoc($resultMov);
    mysqli_stmt_close($stmtMov);

    if (!$movimiento) {
        throw new Exception('Movimiento no encontrado');
    }

    $gruposOriginal = trim((string) $movimiento['grupos']);
    $conceptoOriginal = trim((string) $movimiento['concepto']);
    $entradaOriginal = (float) $movimiento['entrada'];
    $salidaOriginal = (float) $movimiento['salida'];
    $fechaApunte = $movimiento['fecha_apunte'];

    if ($gruposOriginal === 'CAJA INICIO' || $gruposOriginal === 'CAJA FINAL' || (string) $movimiento['cierre_caja'] === 'true') {
        throw new Exception('No se puede trasladar un apunte de apertura o cierre de caja');
    }

    if ($entradaOriginal <= 0 && $salidaOriginal <= 0) {
        throw new Exception('El movimiento no tiene importe en entrada ni salida');
    }

    if ($entradaOriginal > 0 && $salidaOriginal > 0) {
        throw new Exception('El movimiento tiene entrada y salida; no se puede trasladar automáticamente');
    }

    $esEntrada = $entradaOriginal > 0;
    $importeMovimiento = $esEntrada ? $entradaOriginal : $salidaOriginal;
    $tipoImporteTexto = $esEntrada ? 'entrada' : 'salida';
    $importeTexto = number_format($importeMovimiento, 2, ',', '.') . '€';
    $deltaCaja = traslado_caja_calcular_delta($entradaOriginal, $salidaOriginal);

    $nombreSucursal = obtener_nombre_sucursal($idSucursal);
    if (!$nombreSucursal) {
        $nombreSucursal = 'Sucursal ' . $idSucursal;
    }

    $uidMov = (int) $usuario_id;
    $gruposDestino = $gruposOriginal !== '' ? $gruposOriginal : 'General';

    mysqli_begin_transaction($conexion);

    if ($tipo === 'transferencia') {
        $queryInsert = "INSERT INTO movimientos_transferencia (
                            sucursal, id_lote, id_venta, descripcion, entrada, salida, usuario, grupos, fecha
                        ) VALUES (?, 0, 0, ?, ?, ?, ?, ?, NOW())";
        $stmtInsert = mysqli_prepare($conexion, $queryInsert);
        if (!$stmtInsert) {
            throw new Exception('Error al insertar en transferencias: ' . mysqli_error($conexion));
        }
        mysqli_stmt_bind_param(
            $stmtInsert,
            'isddis',
            $idSucursal,
            $conceptoOriginal,
            $entradaOriginal,
            $salidaOriginal,
            $uidMov,
            $gruposDestino
        );
    } elseif ($tipo === 'tarjeta') {
        $queryInsert = "INSERT INTO movimientos_tarjeta (
                            id_venta, sucursal, id_lote, descripcion, importe, usuario, grupos, fecha
                        ) VALUES (0, ?, 0, ?, ?, ?, ?, NOW())";
        $stmtInsert = mysqli_prepare($conexion, $queryInsert);
        if (!$stmtInsert) {
            throw new Exception('Error al insertar en tarjetas: ' . mysqli_error($conexion));
        }
        mysqli_stmt_bind_param(
            $stmtInsert,
            'isdis',
            $idSucursal,
            $conceptoOriginal,
            $importeMovimiento,
            $uidMov,
            $gruposDestino
        );
    } else {
        $queryInsert = "INSERT INTO movimientos_bizum (
                            sucursal, id_venta, id_lote, descripcion, importe, usuario, grupos, fecha
                        ) VALUES (?, 0, 0, ?, ?, ?, ?, NOW())";
        $stmtInsert = mysqli_prepare($conexion, $queryInsert);
        if (!$stmtInsert) {
            throw new Exception('Error al insertar en bizum: ' . mysqli_error($conexion));
        }
        mysqli_stmt_bind_param(
            $stmtInsert,
            'isdis',
            $idSucursal,
            $conceptoOriginal,
            $importeMovimiento,
            $uidMov,
            $gruposDestino
        );
    }

    if (!mysqli_stmt_execute($stmtInsert)) {
        $error = mysqli_stmt_error($stmtInsert);
        mysqli_stmt_close($stmtInsert);
        throw new Exception('Error al insertar movimiento destino: ' . $error);
    }
    mysqli_stmt_close($stmtInsert);

    $nuevoConcepto = 'Movimiento transferido a la caja: '
        . $tipoCajaTexto
        . ' por valor de '
        . $tipoImporteTexto
        . ' de '
        . $importeTexto
        . ' ('
        . $conceptoOriginal
        . ')';
    $gruposCorreccion = 'Corrección de errores';

    $queryUpdate = "UPDATE `{$tableName}`
                    SET grupos = ?, concepto = ?, entrada = 0, salida = 0
                    WHERE id_movimientos = ?";
    $stmtUpdate = mysqli_prepare($conexion, $queryUpdate);
    if (!$stmtUpdate) {
        throw new Exception('Error al actualizar movimiento de caja: ' . mysqli_error($conexion));
    }
    mysqli_stmt_bind_param($stmtUpdate, 'ssi', $gruposCorreccion, $nuevoConcepto, $idMovimiento);
    if (!mysqli_stmt_execute($stmtUpdate)) {
        $error = mysqli_stmt_error($stmtUpdate);
        mysqli_stmt_close($stmtUpdate);
        throw new Exception('Error al actualizar movimiento de caja: ' . $error);
    }
    if (mysqli_stmt_affected_rows($stmtUpdate) === 0) {
        mysqli_stmt_close($stmtUpdate);
        throw new Exception('No se pudo actualizar el movimiento de caja');
    }
    mysqli_stmt_close($stmtUpdate);

    traslado_caja_ajustar_cierre_dia($conexion, $tableName, $fechaApunte, $deltaCaja);
    traslado_caja_ajustar_aperturas_posteriores_mismo_dia(
        $conexion,
        $tableName,
        $idMovimiento,
        $fechaApunte,
        $deltaCaja
    );
    traslado_caja_ajustar_dias_posteriores($conexion, $tableName, $fechaApunte, $deltaCaja);

    mysqli_commit($conexion);
    mysqli_close($conexion);

    $nombreUsuarioAction = $usuario !== '' ? $usuario : (string) obtenerNombreUsuario($usuario_id);
    if ($nombreUsuarioAction === '') {
        $nombreUsuarioAction = 'Usuario';
    }

    $textoActionUser = $nombreUsuarioAction
        . ' traslado movimiento de caja nº '
        . $idMovimiento
        . ' de la sucursal '
        . $nombreSucursal
        . ' a caja '
        . $tipoCajaTexto;
    $idActionUser = '25';
    $relItemAction = isset($_SESSION['relItemAction']) ? $_SESSION['relItemAction'] : '0';
    registrar_accion_usuario($usuario_id, $idActionUser, $textoActionUser, $usuario_sucursal, $relItemAction);

    echo json_encode([
        'success' => true,
        'message' => 'Movimiento trasladado correctamente a ' . $tipoCajaTexto,
    ]);
} catch (Exception $e) {
    if (isset($conexion) && $conexion instanceof mysqli) {
        @mysqli_rollback($conexion);
        @mysqli_close($conexion);
    }

    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage(),
    ]);
}
