<?php

/**
 * Marca renovaciones vencidas del mes actual, genera la nueva cuota y actualiza el lote.
 */

/**
 * @param mysqli $conexion
 * @param array $sucursalesActivas
 * @return array
 */
function cron_historico_empenos_vencidos($conexion, $sucursalesActivas)
{
    cron_linea('>> Tarea: historico_empenos_vencidos');
    if (cron_solo_vista()) {
        cron_linea('  (modo solo vista: no se ejecutaran UPDATE ni INSERT)');
    }

    $resumen = array(
        'sucursales' => 0,
        'sucursales_procesadas' => 0,
        'renovaciones_vencidas_procesadas' => 0,
        'errores' => 0,
        'detalle' => array(),
    );

    $usuarioAccion = 1;
    $usuarioCron = '';

    foreach ($sucursalesActivas as $sucursal) {
        $resumen['sucursales']++;

        $idSucursal = isset($sucursal['id_sucursal']) ? (int) $sucursal['id_sucursal'] : 0;
        $nombreSucursal = isset($sucursal['nombre_sucursal']) ? $sucursal['nombre_sucursal'] : '';

        cron_linea('  - Sucursal ' . $idSucursal . ' (' . $nombreSucursal . ')');

        if ($idSucursal <= 0) {
            continue;
        }

        $resumen['sucursales_procesadas']++;

        $tablaHistorico = cron_tabla_historico_renovaciones_sucursal($idSucursal);
        $tablaLotes = cron_tabla_lotes_sucursal($idSucursal);

        if ($tablaHistorico === false || $tablaLotes === false) {
            $resumen['errores']++;
            cron_linea('    ERROR: id de sucursal no valido para tablas de historico o lotes.');
            continue;
        }

        $sqlRenovaciones = "SELECT id_renovaciones, proximo_vencimiento, lote, importe_renovacion
                            FROM {$tablaHistorico}
                            WHERE estado_historico = 'enfecha'
                              AND proximo_vencimiento < CURDATE()
                              AND YEAR(proximo_vencimiento) = YEAR(CURDATE())";

        $stmtRenovaciones = mysqli_prepare($conexion, $sqlRenovaciones);
        if (!$stmtRenovaciones) {
            $resumen['errores']++;
            cron_linea('    Sin tabla o error en ' . $tablaHistorico . ': ' . mysqli_error($conexion));
            continue;
        }

        if (!mysqli_stmt_execute($stmtRenovaciones)) {
            $resumen['errores']++;
            cron_linea('    Error consultando ' . $tablaHistorico . ': ' . mysqli_stmt_error($stmtRenovaciones));
            mysqli_stmt_close($stmtRenovaciones);
            continue;
        }

        $resultadoRenovaciones = mysqli_stmt_get_result($stmtRenovaciones);
        $renovacionesSucursal = array();

        $sqlMaxRenovacion = "SELECT MAX(id_renovaciones) AS max_id FROM {$tablaHistorico} WHERE lote = ?";
        $stmtMaxRenovacion = mysqli_prepare($conexion, $sqlMaxRenovacion);

        $stmtUpdHistorico = null;
        $stmtInsHistorico = null;
        $stmtUpdLote = null;

        if (!cron_solo_vista()) {
            $sqlUpdHistorico = "UPDATE {$tablaHistorico} SET estado_historico = 'Vencido', fecha_vencido = NOW() WHERE id_renovaciones = ?";
            $stmtUpdHistorico = mysqli_prepare($conexion, $sqlUpdHistorico);

            $sqlInsHistorico = "INSERT INTO {$tablaHistorico} (
                importe_renovacion,
                lote,
                proximo_vencimiento,
                estado_historico,
                fecha_insert
            ) VALUES (?, ?, ?, 'enfecha', NOW())";
            $stmtInsHistorico = mysqli_prepare($conexion, $sqlInsHistorico);

            $sqlUpdLote = "UPDATE {$tablaLotes} SET estado_lote = 'vencido' WHERE id_lote = ?";
            $stmtUpdLote = mysqli_prepare($conexion, $sqlUpdLote);
        }

        if (!$stmtMaxRenovacion || (!cron_solo_vista() && (!$stmtUpdHistorico || !$stmtInsHistorico || !$stmtUpdLote))) {
            $resumen['errores']++;
            cron_linea('    ERROR preparando consultas para ' . $tablaHistorico . ': ' . mysqli_error($conexion));
            mysqli_stmt_close($stmtRenovaciones);
            if ($stmtMaxRenovacion) {
                mysqli_stmt_close($stmtMaxRenovacion);
            }
            if ($stmtUpdHistorico) {
                mysqli_stmt_close($stmtUpdHistorico);
            }
            if ($stmtInsHistorico) {
                mysqli_stmt_close($stmtInsHistorico);
            }
            if ($stmtUpdLote) {
                mysqli_stmt_close($stmtUpdLote);
            }
            continue;
        }

        while ($filaRenovacion = $resultadoRenovaciones ? mysqli_fetch_assoc($resultadoRenovaciones) : null) {
            if (!$filaRenovacion) {
                break;
            }

            $idRenovaciones = (int) $filaRenovacion['id_renovaciones'];
            $idLote = (int) $filaRenovacion['lote'];
            $proximoVencimientoActual = (string) $filaRenovacion['proximo_vencimiento'];
            $importeRenovacion = (float) $filaRenovacion['importe_renovacion'];

            if ($idRenovaciones <= 0 || $idLote <= 0 || $proximoVencimientoActual === '') {
                continue;
            }

            mysqli_stmt_bind_param($stmtMaxRenovacion, 'i', $idLote);
            if (!mysqli_stmt_execute($stmtMaxRenovacion)) {
                $resumen['errores']++;
                cron_linea('      ERROR consultando MAX renovacion lote ' . $idLote . ': ' . mysqli_stmt_error($stmtMaxRenovacion));
                continue;
            }

            $resultadoMax = mysqli_stmt_get_result($stmtMaxRenovacion);
            $filaMax = $resultadoMax ? mysqli_fetch_assoc($resultadoMax) : null;
            $maxIdRenovaciones = $filaMax ? (int) $filaMax['max_id'] : 0;

            if ($idRenovaciones !== $maxIdRenovaciones) {
                continue;
            }

            $proximoVencimiento = date('Y-m-d', strtotime($proximoVencimientoActual . ' +1 month'));
            $idRenovacionesEnfecha = 0;

            if (!cron_solo_vista()) {
                mysqli_stmt_bind_param($stmtUpdHistorico, 'i', $idRenovaciones);
                if (!mysqli_stmt_execute($stmtUpdHistorico)) {
                    $resumen['errores']++;
                    cron_linea('      ERROR actualizando historico ' . $idRenovaciones . ' a vencido: ' . mysqli_stmt_error($stmtUpdHistorico));
                    continue;
                }

                $accionHistoricoRenovacion = 'el historico id ' . $idRenovaciones . ' ha sido actualizado a vencido';
                insert_accion_historico_renovaciones($idSucursal, $accionHistoricoRenovacion, $idLote, $idRenovaciones);

                mysqli_stmt_bind_param($stmtInsHistorico, 'dis', $importeRenovacion, $idLote, $proximoVencimiento);
                if (!mysqli_stmt_execute($stmtInsHistorico)) {
                    $resumen['errores']++;
                    cron_linea('      ERROR insertando nueva cuota lote ' . $idLote . ': ' . mysqli_stmt_error($stmtInsHistorico));
                    continue;
                }

                $idRenovacionesEnfecha = (int) mysqli_insert_id($conexion);

                $accionHistoricoRenovacion = 'el historico id ' . $idRenovacionesEnfecha . ' ha sido insertado en fecha desde el vencido';
                insert_accion_historico_renovaciones($idSucursal, $accionHistoricoRenovacion, $idLote, $idRenovacionesEnfecha);

                mysqli_stmt_bind_param($stmtUpdLote, 'i', $idLote);
                if (!mysqli_stmt_execute($stmtUpdLote)) {
                    $resumen['errores']++;
                    cron_linea('      ERROR actualizando lote ' . $idLote . ' a vencido: ' . mysqli_stmt_error($stmtUpdLote));
                    continue;
                }

                $descripcionEvento = 'ACTUALIZAR lote vencido ' . $idLote . ' Sucursal ' . $idSucursal;
                registrar_tareas_cron($descripcionEvento);

                $descripcionCron = 'empeño vencido por el cron Nº ' . $idLote . ' de la sucursal Nº ' . $idSucursal;
                $tipoDeOperacion = 'Empenovencido';
                insert_global_cron($descripcionCron, $idSucursal, $tipoDeOperacion, $usuarioCron);

                $accionTrazabilidad = 'vencido';
                $comentariosAccion = 'Lote ' . $idLote . ' vencido automaticamente';
                registrar_trazabilidad_lote_cron($idLote, $usuarioAccion, $accionTrazabilidad, $comentariosAccion, $idSucursal);
            }

            $renovacion = array(
                'id_renovaciones' => $idRenovaciones,
                'id_renovaciones_enfecha' => $idRenovacionesEnfecha,
                'lote' => $idLote,
                'proximo_vencimiento_anterior' => $proximoVencimientoActual,
                'proximo_vencimiento_nuevo' => $proximoVencimiento,
                'importe_renovacion' => $importeRenovacion,
            );

            $renovacionesSucursal[] = $renovacion;
            $resumen['renovaciones_vencidas_procesadas']++;

            $prefijoVista = cron_solo_vista() ? '[SIMULACION] ' : '';
            cron_linea(
                $prefijoVista .
                '      * Renovacion ' . $idRenovaciones .
                ' | lote=' . $idLote .
                ' | vencimiento=' . $proximoVencimientoActual .
                ' -> ' . $proximoVencimiento .
                ' | importe=' . $importeRenovacion
            );
        }

        if ($stmtMaxRenovacion) {
            mysqli_stmt_close($stmtMaxRenovacion);
        }
        if ($stmtUpdHistorico) {
            mysqli_stmt_close($stmtUpdHistorico);
        }
        if ($stmtInsHistorico) {
            mysqli_stmt_close($stmtInsHistorico);
        }
        if ($stmtUpdLote) {
            mysqli_stmt_close($stmtUpdLote);
        }
        mysqli_stmt_close($stmtRenovaciones);

        if (!$renovacionesSucursal) {
            cron_linea('    Sin renovaciones vencidas pendientes en ' . $tablaHistorico . '.');
        }

        $resumen['detalle'][] = array(
            'id_sucursal' => $idSucursal,
            'nombre_sucursal' => $nombreSucursal,
            'tabla_historico' => $tablaHistorico,
            'tabla_lotes' => $tablaLotes,
            'renovaciones' => $renovacionesSucursal,
        );
    }

    cron_linea(
        '  Resumen: sucursales=' . $resumen['sucursales'] .
        ', procesadas=' . $resumen['sucursales_procesadas'] .
        ', renovaciones_vencidas_procesadas=' . $resumen['renovaciones_vencidas_procesadas'] .
        ', errores=' . $resumen['errores']
    );

    return $resumen;
}
