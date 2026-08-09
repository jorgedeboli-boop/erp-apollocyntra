<?php

/**
 * Apertura y cierre automático de caja para sucursales con new_sitema_caja = 'true'.
 */

/**
 * @param mysqli $conexion
 * @param array $sucursalesActivas
 * @return array
 */
function cron_apertura_de_caja($conexion, $sucursalesActivas)
{
    cron_linea('>> Tarea: apertura_de_caja');
    if (cron_solo_vista()) {
        cron_linea('  (modo solo vista: no se ejecutaran UPDATE ni INSERT)');
    }

    $resumen = array(
        'sucursales' => 0,
        'sucursales_new_sistema' => 0,
        'aperturas' => 0,
        'cierres' => 0,
        'cajas_no_cerradas' => 0,
        'errores' => 0,
        'detalle' => array(),
    );

    $usuarioCron = '';
    $hoy = date('Y-m-d');

    foreach ($sucursalesActivas as $sucursal) {
        $resumen['sucursales']++;

        $idSucursal = isset($sucursal['id_sucursal']) ? (int) $sucursal['id_sucursal'] : 0;
        $nombreSucursal = isset($sucursal['nombre_sucursal']) ? $sucursal['nombre_sucursal'] : '';
        $newSitemaCaja = isset($sucursal['new_sitema_caja']) ? (string) $sucursal['new_sitema_caja'] : '';

        cron_linea(
            '  - Sucursal ' . $idSucursal . ' (' . $nombreSucursal . '): new_sitema_caja = ' . $newSitemaCaja
        );

        if ($idSucursal <= 0 || $newSitemaCaja !== 'true') {
            continue;
        }

        $resumen['sucursales_new_sistema']++;

        $tablaMovimientos = cron_tabla_movimientos_caja_sucursal($idSucursal);
        if ($tablaMovimientos === false) {
            $resumen['errores']++;
            cron_linea('    ERROR: id de sucursal no valido para tabla de movimientos de caja.');
            continue;
        }

        $sqlInicio = "SELECT id_movimientos, fecha_apunte, entrada
                      FROM {$tablaMovimientos}
                      WHERE cierre_caja = 'false' AND grupos = 'CAJA INICIO'
                      ORDER BY id_movimientos DESC
                      LIMIT 1";
        $resultadoInicio = mysqli_query($conexion, $sqlInicio);

        if (!$resultadoInicio) {
            $resumen['errores']++;
            cron_linea('    Sin tabla o error en ' . $tablaMovimientos . ': ' . mysqli_error($conexion));
            continue;
        }

        $filaInicio = mysqli_fetch_assoc($resultadoInicio);
        mysqli_free_result($resultadoInicio);

        if (!$filaInicio) {
            cron_linea('    Sin apunte CAJA INICIO en ' . $tablaMovimientos . '.');
            continue;
        }

        $fechaApunteInicio = isset($filaInicio['fecha_apunte']) ? (string) $filaInicio['fecha_apunte'] : '';
        $idApunteInicio = isset($filaInicio['id_movimientos']) ? (int) $filaInicio['id_movimientos'] : 0;
        $totalCajaInicio = isset($filaInicio['entrada']) ? (float) $filaInicio['entrada'] : 0;

        $sqlCierre = "SELECT salida, fecha_apunte
                      FROM {$tablaMovimientos}
                      WHERE cierre_caja = 'true' AND grupos = 'CAJA FINAL'
                      ORDER BY id_movimientos DESC
                      LIMIT 1";
        $resultadoCierre = mysqli_query($conexion, $sqlCierre);

        if (!$resultadoCierre) {
            $resumen['errores']++;
            cron_linea('    Error consultando ultimo cierre en ' . $tablaMovimientos . ': ' . mysqli_error($conexion));
            continue;
        }

        $filaCierre = mysqli_fetch_assoc($resultadoCierre);
        mysqli_free_result($resultadoCierre);

        $apunteCierre = $filaCierre && isset($filaCierre['salida']) ? (float) $filaCierre['salida'] : 0;
        $fechaApunteCierre = $filaCierre && isset($filaCierre['fecha_apunte']) ? (string) $filaCierre['fecha_apunte'] : '';

        $accionSucursal = '';
        $detalleSucursal = array(
            'id_sucursal' => $idSucursal,
            'nombre_sucursal' => $nombreSucursal,
            'fecha_apunte_inicio' => $fechaApunteInicio,
            'fecha_apunte_cierre' => $fechaApunteCierre,
            'accion' => '',
        );

        if ($fechaApunteInicio === $fechaApunteCierre) {
            $accionSucursal = 'apertura_automatica';

            if (!cron_solo_vista()) {
                $concepto = 'Apertura de caja del ' . $hoy;
                $sqlInsApertura = "INSERT INTO {$tablaMovimientos} (
                    cierre_caja,
                    usuario,
                    grupos,
                    concepto,
                    entrada,
                    fecha_apunte,
                    hora_de_apunte
                ) VALUES ('false', 'cron', 'CAJA INICIO', ?, ?, NOW(), NOW())";
                $stmtInsApertura = mysqli_prepare($conexion, $sqlInsApertura);

                if (!$stmtInsApertura) {
                    $resumen['errores']++;
                    cron_linea('    ERROR preparando INSERT apertura: ' . mysqli_error($conexion));
                    continue;
                }

                mysqli_stmt_bind_param($stmtInsApertura, 'sd', $concepto, $apunteCierre);

                if (!mysqli_stmt_execute($stmtInsApertura)) {
                    $resumen['errores']++;
                    cron_linea('    ERROR insertando apertura de caja: ' . mysqli_stmt_error($stmtInsApertura));
                    mysqli_stmt_close($stmtInsApertura);
                    continue;
                }

                mysqli_stmt_close($stmtInsApertura);

                $sqlUpdSucursal = "UPDATE sucursal SET caja_cerrada = 'false' WHERE id_sucursal = ?";
                $stmtUpdSucursal = mysqli_prepare($conexion, $sqlUpdSucursal);

                if (!$stmtUpdSucursal) {
                    $resumen['errores']++;
                    cron_linea('    ERROR preparando UPDATE sucursal tras apertura: ' . mysqli_error($conexion));
                    continue;
                }

                mysqli_stmt_bind_param($stmtUpdSucursal, 'i', $idSucursal);

                if (!mysqli_stmt_execute($stmtUpdSucursal)) {
                    $resumen['errores']++;
                    cron_linea('    ERROR actualizando sucursal tras apertura: ' . mysqli_stmt_error($stmtUpdSucursal));
                    mysqli_stmt_close($stmtUpdSucursal);
                    continue;
                }

                mysqli_stmt_close($stmtUpdSucursal);

                insert_global_cron(
                    'Apertura cron de caja Sucursal Nº ' . $idSucursal,
                    $idSucursal,
                    'Aperturacaja',
                    $usuarioCron
                );
            }

            $resumen['aperturas']++;
            $prefijoVista = cron_solo_vista() ? '[SIMULACION] ' : '';
            cron_linea(
                $prefijoVista .
                '    Apertura automatica | inicio=' . $fechaApunteInicio .
                ' | cierre=' . $fechaApunteCierre .
                ' | entrada=' . $apunteCierre
            );
        } else {
            $sqlCount = "SELECT COUNT(id_movimientos) AS total_movimientos
                         FROM {$tablaMovimientos}
                         WHERE fecha_apunte = ?
                           AND cierre_caja = 'false'
                           AND id_movimientos NOT IN (?)";
            $stmtCount = mysqli_prepare($conexion, $sqlCount);

            if (!$stmtCount) {
                $resumen['errores']++;
                cron_linea('    ERROR preparando COUNT movimientos: ' . mysqli_error($conexion));
                continue;
            }

            mysqli_stmt_bind_param($stmtCount, 'si', $fechaApunteInicio, $idApunteInicio);

            if (!mysqli_stmt_execute($stmtCount)) {
                $resumen['errores']++;
                cron_linea('    ERROR contando movimientos: ' . mysqli_stmt_error($stmtCount));
                mysqli_stmt_close($stmtCount);
                continue;
            }

            $resultadoCount = mysqli_stmt_get_result($stmtCount);
            $filaCount = $resultadoCount ? mysqli_fetch_assoc($resultadoCount) : null;
            $totalMovimientos = $filaCount ? (int) $filaCount['total_movimientos'] : 0;
            mysqli_stmt_close($stmtCount);

            if ($totalMovimientos > 0) {
                $accionSucursal = 'caja_no_cerrada';

                if (!cron_solo_vista()) {
                    insert_global_cron(
                        'Caja no cerrada Sucursal Nº ' . $idSucursal,
                        $idSucursal,
                        'Cajanocerrada',
                        $usuarioCron
                    );

                    $sqlUpdSucursal = "UPDATE sucursal SET caja_cerrada = 'false' WHERE id_sucursal = ?";
                    $stmtUpdSucursal = mysqli_prepare($conexion, $sqlUpdSucursal);

                    if (!$stmtUpdSucursal) {
                        $resumen['errores']++;
                        cron_linea('    ERROR preparando UPDATE sucursal: ' . mysqli_error($conexion));
                        continue;
                    }

                    mysqli_stmt_bind_param($stmtUpdSucursal, 'i', $idSucursal);

                    if (!mysqli_stmt_execute($stmtUpdSucursal)) {
                        $resumen['errores']++;
                        cron_linea('    ERROR actualizando sucursal caja no cerrada: ' . mysqli_stmt_error($stmtUpdSucursal));
                        mysqli_stmt_close($stmtUpdSucursal);
                        continue;
                    }

                    mysqli_stmt_close($stmtUpdSucursal);
                }

                $resumen['cajas_no_cerradas']++;
                $prefijoVista = cron_solo_vista() ? '[SIMULACION] ' : '';
                cron_linea(
                    $prefijoVista .
                    '    Caja no cerrada | movimientos=' . $totalMovimientos .
                    ' | fecha_inicio=' . $fechaApunteInicio
                );
            } elseif ($totalMovimientos < 1 || empty($totalMovimientos)) {
                $accionSucursal = 'cierre_y_apertura_sin_movimientos';

                if (!cron_solo_vista()) {
                    $sqlInsCierre = "INSERT INTO {$tablaMovimientos} (
                        cierre_caja,
                        fecha_apunte,
                        grupos,
                        salida,
                        usuario
                    ) VALUES ('true', ?, 'CAJA FINAL', ?, 'cron')";
                    $stmtInsCierre = mysqli_prepare($conexion, $sqlInsCierre);

                    if (!$stmtInsCierre) {
                        $resumen['errores']++;
                        cron_linea('    ERROR preparando INSERT cierre: ' . mysqli_error($conexion));
                        continue;
                    }

                    mysqli_stmt_bind_param($stmtInsCierre, 'sd', $fechaApunteInicio, $totalCajaInicio);

                    if (!mysqli_stmt_execute($stmtInsCierre)) {
                        $resumen['errores']++;
                        cron_linea('    ERROR insertando cierre de caja: ' . mysqli_stmt_error($stmtInsCierre));
                        mysqli_stmt_close($stmtInsCierre);
                        continue;
                    }

                    mysqli_stmt_close($stmtInsCierre);

                    insert_global_cron(
                        'Cierre cron de caja Sucursal Nº ' . $idSucursal . ' no se registraron movimientos.',
                        $idSucursal,
                        'Cajacerrada',
                        $usuarioCron
                    );

                    $sqlUpdSucursal = "UPDATE sucursal SET caja_cerrada = 'true' WHERE id_sucursal = ?";
                    $stmtUpdSucursal = mysqli_prepare($conexion, $sqlUpdSucursal);

                    if (!$stmtUpdSucursal) {
                        $resumen['errores']++;
                        cron_linea('    ERROR preparando UPDATE sucursal: ' . mysqli_error($conexion));
                        continue;
                    }

                    mysqli_stmt_bind_param($stmtUpdSucursal, 'i', $idSucursal);

                    if (!mysqli_stmt_execute($stmtUpdSucursal)) {
                        $resumen['errores']++;
                        cron_linea('    ERROR actualizando sucursal caja cerrada: ' . mysqli_stmt_error($stmtUpdSucursal));
                        mysqli_stmt_close($stmtUpdSucursal);
                        continue;
                    }

                    mysqli_stmt_close($stmtUpdSucursal);

                    $concepto = 'Apertura de caja del ' . $hoy;
                    $sqlInsApertura = "INSERT INTO {$tablaMovimientos} (
                        cierre_caja,
                        usuario,
                        grupos,
                        concepto,
                        entrada,
                        fecha_apunte,
                        hora_de_apunte
                    ) VALUES ('false', 'cron', 'CAJA INICIO', ?, ?, NOW(), NOW())";
                    $stmtInsApertura = mysqli_prepare($conexion, $sqlInsApertura);

                    if (!$stmtInsApertura) {
                        $resumen['errores']++;
                        cron_linea('    ERROR preparando INSERT apertura: ' . mysqli_error($conexion));
                        continue;
                    }

                    mysqli_stmt_bind_param($stmtInsApertura, 'sd', $concepto, $apunteCierre);

                    if (!mysqli_stmt_execute($stmtInsApertura)) {
                        $resumen['errores']++;
                        cron_linea('    ERROR insertando apertura de caja: ' . mysqli_stmt_error($stmtInsApertura));
                        mysqli_stmt_close($stmtInsApertura);
                        continue;
                    }

                    mysqli_stmt_close($stmtInsApertura);

                    $sqlUpdSucursalApertura = "UPDATE sucursal SET caja_cerrada = 'false' WHERE id_sucursal = ?";
                    $stmtUpdSucursalApertura = mysqli_prepare($conexion, $sqlUpdSucursalApertura);

                    if (!$stmtUpdSucursalApertura) {
                        $resumen['errores']++;
                        cron_linea('    ERROR preparando UPDATE sucursal tras apertura: ' . mysqli_error($conexion));
                        continue;
                    }

                    mysqli_stmt_bind_param($stmtUpdSucursalApertura, 'i', $idSucursal);

                    if (!mysqli_stmt_execute($stmtUpdSucursalApertura)) {
                        $resumen['errores']++;
                        cron_linea('    ERROR actualizando sucursal tras apertura: ' . mysqli_stmt_error($stmtUpdSucursalApertura));
                        mysqli_stmt_close($stmtUpdSucursalApertura);
                        continue;
                    }

                    mysqli_stmt_close($stmtUpdSucursalApertura);

                    insert_global_cron(
                        'Apertura cron de caja Sucursal Nº ' . $idSucursal . ' no se registraron movimientos.',
                        $idSucursal,
                        'Aperturacaja',
                        $usuarioCron
                    );
                }

                $resumen['cierres']++;
                $resumen['aperturas']++;
                $prefijoVista = cron_solo_vista() ? '[SIMULACION] ' : '';
                cron_linea(
                    $prefijoVista .
                    '    Cierre y apertura sin movimientos | salida=' . $totalCajaInicio .
                    ' | nueva_entrada=' . $apunteCierre
                );
            }
        }

        $detalleSucursal['accion'] = $accionSucursal;
        $resumen['detalle'][] = $detalleSucursal;
    }

    cron_linea(
        '  Resumen: sucursales=' . $resumen['sucursales'] .
        ', con new_sitema_caja=' . $resumen['sucursales_new_sistema'] .
        ', aperturas=' . $resumen['aperturas'] .
        ', cierres=' . $resumen['cierres'] .
        ', cajas_no_cerradas=' . $resumen['cajas_no_cerradas'] .
        ', errores=' . $resumen['errores']
    );

    return $resumen;
}
