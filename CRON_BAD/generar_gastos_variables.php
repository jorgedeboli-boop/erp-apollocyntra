<?php
declare(strict_types=1);

/**
 * Genera gastos variables a partir de `gastos_fijos` activos.
 * Para cada gasto fijo:
 * - Genera un gasto en `gastos` por cada mes desde `fecha_inicio_gasto_fijo` hasta hoy (inclusive),
 *   usando la misma fecha para `fecha_gasto` y `fecha_pago_gasto`.
 * - Si ya existe un gasto para esa fecha (mismo rel_id_gasto_fijo + DATE(fecha_gasto)), no crea duplicado.
 * - Inserta también el registro en `rel_gastos_forma_pago`.
 */

function cron_generar_gastos_variables(mysqli $conexion): array
{
    $resumen = [
        'gastos_fijos' => 0,
        'gastos_creados' => 0,
        'gastos_existentes' => 0,
        'errores' => 0,
    ];

    @mysqli_set_charset($conexion, 'utf8');

    $rGF = mysqli_query($conexion, "SELECT * FROM gastos_fijos WHERE estado_gasto_fijo = 'true'");
    if (!$rGF) {
        throw new RuntimeException("Error consultando gastos_fijos: " . mysqli_error($conexion));
    }

    $stNumeroFormaPago = mysqli_prepare(
        $conexion,
        "SELECT numero_forma_pago
         FROM rel_gastos_forma_pago
         WHERE gasto_fijo_id = ? AND forma_de_pago_id = ?
         ORDER BY id_rel_gastos_forma_pago DESC
         LIMIT 1"
    );

    $stExisteGasto = mysqli_prepare(
        $conexion,
        "SELECT id_gasto
         FROM gastos
         WHERE rel_id_gasto_fijo = ?
           AND DATE(fecha_gasto) = ?
         LIMIT 1"
    );

    $stInsertGasto = mysqli_prepare(
        $conexion,
        "INSERT INTO gastos (
            rel_id_gasto_fijo,
            empresa_gasto,
            fecha_gasto,
            fecha_pago_gasto,
            usuario_creacion_gasto,
            usuario_pago_gasto,
            sucursal_gasto,
            proveedor_gasto,
            total_gasto,
            forma_pago_gasto,
            tipo_de_gasto,
            descripcion_gasto,
            creado_desde,
            origen_gasto_variable,
            gasto_tipo,
            estado_gasto
        ) VALUES (
            ?, ?, ?, ?, 1, 1, ?, ?, ?, ?, ?, ?, 'Cron', 'gasto_fijo', ?, 'pagado'
        )"
    );

    $stInsertRelPago = mysqli_prepare(
        $conexion,
        "INSERT INTO rel_gastos_forma_pago (
            gasto_id,
            forma_de_pago_id,
            numero_forma_pago,
            empresa_id_rel,
            fecha_rel
        ) VALUES (
            ?, ?, ?, ?, NOW()
        )"
    );

    if (!$stExisteGasto || !$stInsertGasto || !$stInsertRelPago) {
        throw new RuntimeException("Error preparando statements de gastos: " . mysqli_error($conexion));
    }

    $hoy = new DateTimeImmutable('today');

    while ($gf = mysqli_fetch_assoc($rGF)) {
        $resumen['gastos_fijos']++;

        $idGastoFijo = (int)($gf['id_gasto_fijo'] ?? 0);
        $fechaInicioStr = (string)($gf['fecha_inicio_gasto_fijo'] ?? '');

        if ($idGastoFijo <= 0 || $fechaInicioStr === '') {
            $resumen['errores']++;
            continue;
        }

        // Datos para el INSERT en gastos
        $empresa = (int)($gf['empresa_gasto_fijo'] ?? 0);
        $sucursal = (int)($gf['sucursal_gasto_fijo'] ?? 0);
        $proveedor = (int)($gf['proveedor_gasto_fijo'] ?? 0);
        $total = (float)($gf['total_gasto_fijo'] ?? 0);
        $formaPago = (int)($gf['forma_pago_gasto_fijo'] ?? 0);
        $tipoGasto = (int)($gf['tipo_de_gasto_fijo'] ?? 0);
        $descripcion = (string)($gf['descripcion_gasto_fijo'] ?? '');
        $gastoTipo = (string)($gf['gasto_tipo'] ?? '');

        // numero_forma_pago (si existe)
        $numeroFormaPago = null;
        if ($stNumeroFormaPago && $formaPago > 0) {
            mysqli_stmt_bind_param($stNumeroFormaPago, 'ii', $idGastoFijo, $formaPago);
            mysqli_stmt_execute($stNumeroFormaPago);
            $rn = mysqli_stmt_get_result($stNumeroFormaPago);
            $rowN = $rn ? mysqli_fetch_assoc($rn) : null;
            if ($rowN && $rowN['numero_forma_pago'] !== null && $rowN['numero_forma_pago'] !== '') {
                $numeroFormaPago = (string)$rowN['numero_forma_pago'];
            }
        }

        // Generar fechas mensuales desde inicio hasta hoy inclusive
        try {
            $fechaTemporal = new DateTimeImmutable($fechaInicioStr);
        } catch (Throwable $e) {
            $resumen['errores']++;
            continue;
        }

        if ($fechaTemporal > $hoy) {
            continue;
        }

        // Normalizar al día exacto de inicio, sumando meses (misma idea que tu código)
        while ($fechaTemporal <= $hoy) {
            $fecha = $fechaTemporal->format('Y-m-d');

            // ¿Ya existe gasto para ese mes/día?
            mysqli_stmt_bind_param($stExisteGasto, 'is', $idGastoFijo, $fecha);
            mysqli_stmt_execute($stExisteGasto);
            $re = mysqli_stmt_get_result($stExisteGasto);
            $rowE = $re ? mysqli_fetch_assoc($re) : null;
            $idGasto = $rowE ? (int)$rowE['id_gasto'] : 0;

            if ($idGasto > 0) {
                $resumen['gastos_existentes']++;
            } else {
                mysqli_begin_transaction($conexion);
                try {
                    mysqli_stmt_bind_param(
                        $stInsertGasto,
                        'iissiiidiiis',
                        $idGastoFijo,
                        $empresa,
                        $fecha,
                        $fecha,
                        $sucursal,
                        $proveedor,
                        $total,
                        $formaPago,
                        $tipoGasto,
                        $descripcion,
                        $gastoTipo
                    );
                    if (!mysqli_stmt_execute($stInsertGasto)) {
                        throw new RuntimeException("Error insertando gasto (fijo {$idGastoFijo}): " . mysqli_stmt_error($stInsertGasto));
                    }
                    $nuevoId = (int)mysqli_insert_id($conexion);

                    // Insert rel forma pago
                    $numRel = $numeroFormaPago ?? '';
                    mysqli_stmt_bind_param($stInsertRelPago, 'iisi', $nuevoId, $formaPago, $numRel, $empresa);
                    if (!mysqli_stmt_execute($stInsertRelPago)) {
                        throw new RuntimeException("Error insertando rel_gastos_forma_pago (gasto {$nuevoId}): " . mysqli_stmt_error($stInsertRelPago));
                    }

                    mysqli_commit($conexion);
                    $resumen['gastos_creados']++;
                } catch (Throwable $e) {
                    mysqli_rollback($conexion);
                    $resumen['errores']++;
                    fwrite(STDERR, "[generar_gastos_variables] gasto_fijo {$idGastoFijo} fecha {$fecha}: {$e->getMessage()}\n");
                }
            }

            $fechaTemporal = $fechaTemporal->modify('+1 month');
        }
    }

    if ($stNumeroFormaPago) {
        mysqli_stmt_close($stNumeroFormaPago);
    }
    mysqli_stmt_close($stExisteGasto);
    mysqli_stmt_close($stInsertGasto);
    mysqli_stmt_close($stInsertRelPago);

    return $resumen;
}

