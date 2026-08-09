<?php

/**
 * Genera gastos variables a partir de gastos_fijos activos.
 */

/**
 * @param mysqli $conexion
 * @return array
 */
function cron_generar_gastos_variables($conexion)
{
    cron_linea('>> Tarea: generar_gastos_variables');
    if (cron_solo_vista()) {
        cron_linea('  (modo solo vista: no se ejecutaran INSERT)');
    }

    $resumen = array(
        'gastos_fijos' => 0,
        'gastos_creados' => 0,
        'gastos_existentes' => 0,
        'errores' => 0,
        'detalle' => array(),
    );

    $sqlGastosFijos = "SELECT * FROM gastos_fijos WHERE estado_gasto_fijo = 'true'";
    $resultadoGastosFijos = mysqli_query($conexion, $sqlGastosFijos);

    if (!$resultadoGastosFijos) {
        $resumen['errores']++;
        cron_linea('  ERROR consultando gastos_fijos: ' . mysqli_error($conexion));
        return $resumen;
    }

    $fechaActual = date('Y-m-d');

    $sqlNumeroFormaPago = "SELECT numero_forma_pago
                           FROM rel_gastos_forma_pago
                           WHERE gasto_fijo_id = ? AND forma_de_pago_id = ?
                           LIMIT 1";
    $stmtNumeroFormaPago = mysqli_prepare($conexion, $sqlNumeroFormaPago);

    $sqlExisteGasto = "SELECT id_gasto
                       FROM gastos
                       WHERE rel_id_gasto_fijo = ?
                         AND DATE(fecha_gasto) = ?
                       LIMIT 1";
    $stmtExisteGasto = mysqli_prepare($conexion, $sqlExisteGasto);

    $stmtInsertGasto = null;
    $stmtInsertRelPago = null;

    if (!cron_solo_vista()) {
        $sqlInsertGasto = "INSERT INTO gastos (
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
        ) VALUES (?, ?, ?, ?, 1, 1, ?, ?, ?, ?, ?, ?, 'Cron', 'gasto_fijo', ?, 'pagado')";
        $stmtInsertGasto = mysqli_prepare($conexion, $sqlInsertGasto);

        $sqlInsertRelPago = "INSERT INTO rel_gastos_forma_pago (
            gasto_id,
            forma_de_pago_id,
            numero_forma_pago,
            empresa_id_rel,
            fecha_rel
        ) VALUES (?, ?, ?, ?, NOW())";
        $stmtInsertRelPago = mysqli_prepare($conexion, $sqlInsertRelPago);
    }

    if (!$stmtNumeroFormaPago || !$stmtExisteGasto || (!cron_solo_vista() && (!$stmtInsertGasto || !$stmtInsertRelPago))) {
        $resumen['errores']++;
        cron_linea('  ERROR preparando consultas de gastos: ' . mysqli_error($conexion));
        if ($resultadoGastosFijos) {
            mysqli_free_result($resultadoGastosFijos);
        }
        if ($stmtNumeroFormaPago) {
            mysqli_stmt_close($stmtNumeroFormaPago);
        }
        if ($stmtExisteGasto) {
            mysqli_stmt_close($stmtExisteGasto);
        }
        if ($stmtInsertGasto) {
            mysqli_stmt_close($stmtInsertGasto);
        }
        if ($stmtInsertRelPago) {
            mysqli_stmt_close($stmtInsertRelPago);
        }
        return $resumen;
    }

    while ($gastoFijo = mysqli_fetch_assoc($resultadoGastosFijos)) {
        $resumen['gastos_fijos']++;

        $idGastoFijo = isset($gastoFijo['id_gasto_fijo']) ? (int) $gastoFijo['id_gasto_fijo'] : 0;
        $fechaInicioGastoFijo = isset($gastoFijo['fecha_inicio_gasto_fijo']) ? (string) $gastoFijo['fecha_inicio_gasto_fijo'] : '';
        $totalGastoFijo = isset($gastoFijo['total_gasto_fijo']) ? (float) $gastoFijo['total_gasto_fijo'] : 0;
        $tipoDeGastoFijo = isset($gastoFijo['tipo_de_gasto_fijo']) ? (int) $gastoFijo['tipo_de_gasto_fijo'] : 0;
        $descripcionGastoFijo = isset($gastoFijo['descripcion_gasto_fijo']) ? (string) $gastoFijo['descripcion_gasto_fijo'] : '';
        $proveedorGastoFijo = isset($gastoFijo['proveedor_gasto_fijo']) ? (int) $gastoFijo['proveedor_gasto_fijo'] : 0;
        $sucursalGastoFijo = isset($gastoFijo['sucursal_gasto_fijo']) ? (int) $gastoFijo['sucursal_gasto_fijo'] : 0;
        $formaPagoGastoFijo = isset($gastoFijo['forma_pago_gasto_fijo']) ? (int) $gastoFijo['forma_pago_gasto_fijo'] : 0;
        $empresaGastoFijo = isset($gastoFijo['empresa_gasto_fijo']) ? (int) $gastoFijo['empresa_gasto_fijo'] : 0;
        $gastoTipo = isset($gastoFijo['gasto_tipo']) ? (string) $gastoFijo['gasto_tipo'] : '';

        if ($idGastoFijo <= 0 || $fechaInicioGastoFijo === '') {
            $resumen['errores']++;
            continue;
        }

        cron_linea('  - Gasto fijo ' . $idGastoFijo . ' | inicio=' . $fechaInicioGastoFijo);

        $numeroFormaPago = '';
        if ($formaPagoGastoFijo > 0) {
            mysqli_stmt_bind_param($stmtNumeroFormaPago, 'ii', $idGastoFijo, $formaPagoGastoFijo);
            if (mysqli_stmt_execute($stmtNumeroFormaPago)) {
                $resultadoNumero = mysqli_stmt_get_result($stmtNumeroFormaPago);
                $filaNumero = $resultadoNumero ? mysqli_fetch_assoc($resultadoNumero) : null;
                if ($filaNumero && isset($filaNumero['numero_forma_pago'])) {
                    $numeroFormaPago = (string) $filaNumero['numero_forma_pago'];
                }
            }
        }

        $fechaInicio = new DateTime($fechaInicioGastoFijo);
        $fechaFin = new DateTime($fechaActual);
        $diferencia = $fechaInicio->diff($fechaFin);
        $mesesTranscurridos = ($diferencia->y * 12) + $diferencia->m;

        $fechasPorMes = array();
        $fechaTemporal = new DateTime($fechaInicioGastoFijo);

        for ($i = 0; $i <= $mesesTranscurridos; $i++) {
            $fechasPorMes[] = $fechaTemporal->format('Y-m-d');
            $fechaTemporal->modify('+1 month');
        }

        $gastosGastoFijo = array();

        foreach ($fechasPorMes as $fecha) {
            mysqli_stmt_bind_param($stmtExisteGasto, 'is', $idGastoFijo, $fecha);
            if (!mysqli_stmt_execute($stmtExisteGasto)) {
                $resumen['errores']++;
                cron_linea('      ERROR consultando gasto existente fecha ' . $fecha . ': ' . mysqli_stmt_error($stmtExisteGasto));
                continue;
            }

            $resultadoExiste = mysqli_stmt_get_result($stmtExisteGasto);
            $filaExiste = $resultadoExiste ? mysqli_fetch_assoc($resultadoExiste) : null;
            $idGasto = $filaExiste && isset($filaExiste['id_gasto']) ? (int) $filaExiste['id_gasto'] : 0;

            if ($idGasto > 0) {
                $resumen['gastos_existentes']++;
                continue;
            }

            if (!cron_solo_vista()) {
                mysqli_stmt_bind_param(
                    $stmtInsertGasto,
                    'iissiidiiss',
                    $idGastoFijo,
                    $empresaGastoFijo,
                    $fecha,
                    $fecha,
                    $sucursalGastoFijo,
                    $proveedorGastoFijo,
                    $totalGastoFijo,
                    $formaPagoGastoFijo,
                    $tipoDeGastoFijo,
                    $descripcionGastoFijo,
                    $gastoTipo
                );

                if (!mysqli_stmt_execute($stmtInsertGasto)) {
                    $resumen['errores']++;
                    cron_linea('      ERROR insertando gasto fecha ' . $fecha . ': ' . mysqli_stmt_error($stmtInsertGasto));
                    continue;
                }

                $idGasto = (int) mysqli_insert_id($conexion);

                mysqli_stmt_bind_param(
                    $stmtInsertRelPago,
                    'iisi',
                    $idGasto,
                    $formaPagoGastoFijo,
                    $numeroFormaPago,
                    $empresaGastoFijo
                );

                if (!mysqli_stmt_execute($stmtInsertRelPago)) {
                    $resumen['errores']++;
                    cron_linea('      ERROR insertando rel forma pago gasto ' . $idGasto . ': ' . mysqli_stmt_error($stmtInsertRelPago));
                    continue;
                }
            }

            $gastosGastoFijo[] = array(
                'fecha' => $fecha,
                'id_gasto' => $idGasto,
            );
            $resumen['gastos_creados']++;

            $prefijoVista = cron_solo_vista() ? '[SIMULACION] ' : '';
            cron_linea($prefijoVista . '      * Gasto creado fijo=' . $idGastoFijo . ' | fecha=' . $fecha . ' | total=' . $totalGastoFijo);
        }

        $resumen['detalle'][] = array(
            'id_gasto_fijo' => $idGastoFijo,
            'fechas_generadas' => count($fechasPorMes),
            'gastos_creados' => $gastosGastoFijo,
        );
    }

    mysqli_free_result($resultadoGastosFijos);
    mysqli_stmt_close($stmtNumeroFormaPago);
    mysqli_stmt_close($stmtExisteGasto);
    if ($stmtInsertGasto) {
        mysqli_stmt_close($stmtInsertGasto);
    }
    if ($stmtInsertRelPago) {
        mysqli_stmt_close($stmtInsertRelPago);
    }

    cron_linea(
        '  Resumen: gastos_fijos=' . $resumen['gastos_fijos'] .
        ', gastos_creados=' . $resumen['gastos_creados'] .
        ', gastos_existentes=' . $resumen['gastos_existentes'] .
        ', errores=' . $resumen['errores']
    );

    return $resumen;
}
