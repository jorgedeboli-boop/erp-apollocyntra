<?php

/**
 * Marca empeños como perdidos según valor_meses_perdidos_empenos de cada sucursal.
 */

/**
 * @param mysqli $conexion
 * @param array $sucursalesActivas
 * @param array|null $numeroSemanaEnvio
 * @return array
 */
function cron_empenos_perdidos($conexion, $sucursalesActivas, $numeroSemanaEnvio)
{
    cron_linea('>> Tarea: empenos_perdidos');
    if (cron_solo_vista()) {
        cron_linea('  (modo solo vista: no se ejecutaran UPDATE ni INSERT)');
    }

    $resumen = array(
        'sucursales' => 0,
        'sucursales_con_meses' => 0,
        'lotes_evaluados' => 0,
        'lotes_perdidos' => 0,
        'errores' => 0,
        'detalle' => array(),
    );

    if (!is_array($numeroSemanaEnvio) || !isset($numeroSemanaEnvio['numero_semana'], $numeroSemanaEnvio['anyo_listado'])) {
        $resumen['errores']++;
        cron_linea('  ERROR: no se pudo obtener numeroSemanaEnvio.');
        return $resumen;
    }

    $numeroSemana = (int) $numeroSemanaEnvio['numero_semana'];
    $anyoListado = (int) $numeroSemanaEnvio['anyo_listado'];
    $usuarioAccion = 1;
    $usuarioCron = '';

    foreach ($sucursalesActivas as $sucursal) {
        $resumen['sucursales']++;

        $idSucursal = isset($sucursal['id_sucursal']) ? (int) $sucursal['id_sucursal'] : 0;
        $nombreSucursal = isset($sucursal['nombre_sucursal']) ? $sucursal['nombre_sucursal'] : '';
        $valorMesesPerdidosEmpenos = isset($sucursal['valor_meses_perdidos_empenos'])
            ? (int) $sucursal['valor_meses_perdidos_empenos']
            : 0;

        cron_linea(
            '  - Sucursal ' . $idSucursal . ' (' . $nombreSucursal . '): valor_meses_perdidos_empenos = ' . $valorMesesPerdidosEmpenos
        );

        if ($idSucursal <= 0 || $valorMesesPerdidosEmpenos <= 0) {
            continue;
        }

        $resumen['sucursales_con_meses']++;

        $tablaLotes = cron_tabla_lotes_sucursal($idSucursal);
        $tablaHistorico = cron_tabla_historico_renovaciones_sucursal($idSucursal);

        if ($tablaLotes === false || $tablaHistorico === false) {
            $resumen['errores']++;
            cron_linea('    ERROR: id de sucursal no valido para tablas de lotes o historico.');
            continue;
        }

        $sqlLotes = "SELECT id_lote
                     FROM {$tablaLotes}
                     WHERE estado_lote = 'vencido' AND lote_perdible = 'true'";

        $stmtLotes = mysqli_prepare($conexion, $sqlLotes);
        if (!$stmtLotes) {
            $resumen['errores']++;
            cron_linea('    Sin tabla o error en ' . $tablaLotes . ': ' . mysqli_error($conexion));
            continue;
        }

        if (!mysqli_stmt_execute($stmtLotes)) {
            $resumen['errores']++;
            cron_linea('    Error consultando ' . $tablaLotes . ': ' . mysqli_stmt_error($stmtLotes));
            mysqli_stmt_close($stmtLotes);
            continue;
        }

        $resultadoLotes = mysqli_stmt_get_result($stmtLotes);
        $lotesSucursal = array();

        $sqlCountVencidas = "SELECT COUNT(id_renovaciones) AS total_renovaciones_vencidas
                             FROM {$tablaHistorico}
                             WHERE lote = ? AND estado_historico = 'Vencido'";
        $stmtCountVencidas = mysqli_prepare($conexion, $sqlCountVencidas);

        $sqlRenovacionEnfecha = "SELECT id_renovaciones
                                 FROM {$tablaHistorico}
                                 WHERE lote = ? AND estado_historico = 'enfecha'
                                 ORDER BY id_renovaciones DESC
                                 LIMIT 1";
        $stmtRenovacionEnfecha = mysqli_prepare($conexion, $sqlRenovacionEnfecha);

        $stmtUpdLote = null;
        $stmtUpdRel = null;
        $stmtUpdHistorico = null;

        if (!cron_solo_vista()) {
            $sqlUpdLote = "UPDATE {$tablaLotes}
                           SET estado_lote = 'perdido',
                               fecha_perdido = CURDATE(),
                               numero_semana_empenio_perdido = ?,
                               year_empenio_perdido = ?,
                               estado_envio = 'pendiente_enviar',
                               envio_numero = 0
                           WHERE id_lote = ?";
            $stmtUpdLote = mysqli_prepare($conexion, $sqlUpdLote);

            $sqlUpdRel = "UPDATE rel_articulos_estados
                          SET fecha_perdido_empenio = CURDATE(),
                              rel_numero_semana_empenio_perdido = ?,
                              year_rel_empenio_perdido = ?,
                              estado_articulo = 'pendiente_enviar'
                          WHERE rel_id_lote = ? AND rel_id_sucursal = ?";
            $stmtUpdRel = mysqli_prepare($conexion, $sqlUpdRel);

            $sqlUpdHistorico = "UPDATE {$tablaHistorico}
                                SET estado_historico = 'Perdido',
                                    fecha_vencido = CURDATE(),
                                    fecha_perdido = CURDATE()
                                WHERE id_renovaciones = ?";
            $stmtUpdHistorico = mysqli_prepare($conexion, $sqlUpdHistorico);
        }

        if (!$stmtCountVencidas || !$stmtRenovacionEnfecha || (!cron_solo_vista() && (!$stmtUpdLote || !$stmtUpdRel || !$stmtUpdHistorico))) {
            $resumen['errores']++;
            cron_linea('    ERROR preparando consultas para ' . $tablaLotes . ': ' . mysqli_error($conexion));
            mysqli_stmt_close($stmtLotes);
            if ($stmtCountVencidas) {
                mysqli_stmt_close($stmtCountVencidas);
            }
            if ($stmtRenovacionEnfecha) {
                mysqli_stmt_close($stmtRenovacionEnfecha);
            }
            if ($stmtUpdLote) {
                mysqli_stmt_close($stmtUpdLote);
            }
            if ($stmtUpdRel) {
                mysqli_stmt_close($stmtUpdRel);
            }
            if ($stmtUpdHistorico) {
                mysqli_stmt_close($stmtUpdHistorico);
            }
            continue;
        }

        while ($filaLote = $resultadoLotes ? mysqli_fetch_assoc($resultadoLotes) : null) {
            if (!$filaLote) {
                break;
            }

            $idLote = (int) $filaLote['id_lote'];
            if ($idLote <= 0) {
                continue;
            }

            $resumen['lotes_evaluados']++;

            mysqli_stmt_bind_param($stmtCountVencidas, 'i', $idLote);
            if (!mysqli_stmt_execute($stmtCountVencidas)) {
                $resumen['errores']++;
                cron_linea('      ERROR contando renovaciones vencidas lote ' . $idLote . ': ' . mysqli_stmt_error($stmtCountVencidas));
                continue;
            }

            $resultadoCount = mysqli_stmt_get_result($stmtCountVencidas);
            $filaCount = $resultadoCount ? mysqli_fetch_assoc($resultadoCount) : null;
            $renovacionesVencidas = $filaCount ? (int) $filaCount['total_renovaciones_vencidas'] : 0;

            if ($renovacionesVencidas < $valorMesesPerdidosEmpenos) {
                continue;
            }

            $idRenovaciones = 0;

            if (!cron_solo_vista()) {
                mysqli_stmt_bind_param($stmtUpdLote, 'iii', $numeroSemana, $anyoListado, $idLote);
                if (!mysqli_stmt_execute($stmtUpdLote)) {
                    $resumen['errores']++;
                    cron_linea('      ERROR actualizando lote ' . $idLote . ' a perdido: ' . mysqli_stmt_error($stmtUpdLote));
                    continue;
                }

                mysqli_stmt_bind_param($stmtUpdRel, 'iiii', $numeroSemana, $anyoListado, $idLote, $idSucursal);
                if (!mysqli_stmt_execute($stmtUpdRel)) {
                    $resumen['errores']++;
                    cron_linea('      ERROR actualizando rel_articulos_estados lote ' . $idLote . ': ' . mysqli_stmt_error($stmtUpdRel));
                    continue;
                }

                registrar_tareas_cron('ACTUALIZAR empeño perdido ' . $idLote . ' Sucursal ' . $idSucursal);
                registrar_tareas_cron('ACTUALIZAR empeño perdido ' . $idLote . ' listo para enviar Sucursal ' . $idSucursal);

                insert_global_cron(
                    'empeño perdido por el cron Nº ' . $idLote . ' de la sucursal Nº ' . $idSucursal,
                    $idSucursal,
                    'Empenoperdido',
                    $usuarioCron
                );

                registrar_trazabilidad_lote_cron($idLote, $usuarioAccion, 'perdido', 'Empeño ' . $idLote . ' perdido automaticamente', $idSucursal);
                registrar_trazabilidad_lote_cron(
                    $idLote,
                    $usuarioAccion,
                    'pendiente_enviar',
                    'Empeño ' . $idLote . ' listo para enviar en la semana ' . $numeroSemana,
                    $idSucursal
                );

                mysqli_stmt_bind_param($stmtRenovacionEnfecha, 'i', $idLote);
                if (!mysqli_stmt_execute($stmtRenovacionEnfecha)) {
                    $resumen['errores']++;
                    cron_linea('      ERROR consultando renovacion enfecha lote ' . $idLote . ': ' . mysqli_stmt_error($stmtRenovacionEnfecha));
                    continue;
                }

                $resultadoEnfecha = mysqli_stmt_get_result($stmtRenovacionEnfecha);
                $filaEnfecha = $resultadoEnfecha ? mysqli_fetch_assoc($resultadoEnfecha) : null;
                $idRenovaciones = $filaEnfecha ? (int) $filaEnfecha['id_renovaciones'] : 0;

                if ($idRenovaciones > 0) {
                    mysqli_stmt_bind_param($stmtUpdHistorico, 'i', $idRenovaciones);
                    if (!mysqli_stmt_execute($stmtUpdHistorico)) {
                        $resumen['errores']++;
                        cron_linea('      ERROR actualizando historico ' . $idRenovaciones . ' a Perdido: ' . mysqli_stmt_error($stmtUpdHistorico));
                        continue;
                    }

                    $accionHistoricoRenovacion = 'el historico id ' . $idRenovaciones . ' ha sido actualizado a Perdido';
                    insert_accion_historico_renovaciones($idSucursal, $accionHistoricoRenovacion, $idLote, $idRenovaciones);
                }
            } else {
                mysqli_stmt_bind_param($stmtRenovacionEnfecha, 'i', $idLote);
                if (mysqli_stmt_execute($stmtRenovacionEnfecha)) {
                    $resultadoEnfecha = mysqli_stmt_get_result($stmtRenovacionEnfecha);
                    $filaEnfecha = $resultadoEnfecha ? mysqli_fetch_assoc($resultadoEnfecha) : null;
                    $idRenovaciones = $filaEnfecha ? (int) $filaEnfecha['id_renovaciones'] : 0;
                }
            }

            $lote = array(
                'id_lote' => $idLote,
                'renovaciones_vencidas' => $renovacionesVencidas,
                'id_renovaciones_enfecha' => $idRenovaciones,
                'numero_semana_envio' => $numeroSemana,
                'anyo_listado' => $anyoListado,
            );

            $lotesSucursal[] = $lote;
            $resumen['lotes_perdidos']++;

            $prefijoVista = cron_solo_vista() ? '[SIMULACION] ' : '';
            cron_linea(
                $prefijoVista .
                '      * Lote ' . $idLote .
                ' | renovaciones_vencidas=' . $renovacionesVencidas .
                ' (>= ' . $valorMesesPerdidosEmpenos . ')' .
                ' | semana_envio=' . $numeroSemana .
                ' | historico_enfecha=' . ($idRenovaciones > 0 ? $idRenovaciones : 'sin registro')
            );
        }

        if ($stmtCountVencidas) {
            mysqli_stmt_close($stmtCountVencidas);
        }
        if ($stmtRenovacionEnfecha) {
            mysqli_stmt_close($stmtRenovacionEnfecha);
        }
        if ($stmtUpdLote) {
            mysqli_stmt_close($stmtUpdLote);
        }
        if ($stmtUpdRel) {
            mysqli_stmt_close($stmtUpdRel);
        }
        if ($stmtUpdHistorico) {
            mysqli_stmt_close($stmtUpdHistorico);
        }
        mysqli_stmt_close($stmtLotes);

        if (!$lotesSucursal) {
            cron_linea('    Sin lotes vencidos perdibles pendientes de marcar como perdidos en ' . $tablaLotes . '.');
        }

        $resumen['detalle'][] = array(
            'id_sucursal' => $idSucursal,
            'nombre_sucursal' => $nombreSucursal,
            'valor_meses_perdidos_empenos' => $valorMesesPerdidosEmpenos,
            'tabla_lotes' => $tablaLotes,
            'tabla_historico' => $tablaHistorico,
            'lotes' => $lotesSucursal,
        );
    }

    cron_linea(
        '  Resumen: sucursales=' . $resumen['sucursales'] .
        ', con valor_meses_perdidos_empenos valido=' . $resumen['sucursales_con_meses'] .
        ', lotes_evaluados=' . $resumen['lotes_evaluados'] .
        ', lotes_perdidos=' . $resumen['lotes_perdidos'] .
        ', errores=' . $resumen['errores']
    );

    return $resumen;
}
