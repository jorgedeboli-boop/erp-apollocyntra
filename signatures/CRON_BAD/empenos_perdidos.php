<?php
declare(strict_types=1);

/**
 * Pasa a "perdido" los empeños (lotes) que:
 * - están en estado 'vencido'
 * - son perdibles (lote_perdible = 'true')
 * - tienen al menos N renovaciones vencidas (N = sucursal.valor_meses_perdidos_empenos)
 *
 * Además:
 * - marca estado_envio = 'pendiente_enviar'
 * - actualiza rel_articulos_estados
 * - registra trazabilidad y tareas_cron
 * - marca el histórico "enfecha" como 'Perdido' (si existe)
 */

function cron_empenos_perdidos(mysqli $conexion): array
{
    $resumen = [
        'sucursales' => 0,
        'sucursales_validas' => 0,
        'tablas_lotes_inexistentes' => 0,
        'tablas_historico_inexistentes' => 0,
        'lotes_evaluados' => 0,
        'lotes_perdidos' => 0,
        'errores' => 0,
    ];

    @mysqli_set_charset($conexion, 'utf8');

    // Semana de envío (3 semanas atrás). OJO: esta función abre/cierra su propia conexión por diseño.
    $datos_semana = numeroSemanaEnvio();
    if (!$datos_semana || !isset($datos_semana['numero_semana'], $datos_semana['anyo_listado'])) {
        throw new RuntimeException("No se pudo obtener numeroSemanaEnvio()");
    }
    $numeroSemanaPerdido = (int) $datos_semana['numero_semana'];
    $anyoListadoPerdido = (int) $datos_semana['anyo_listado'];

    $rSuc = mysqli_query($conexion, "SELECT id_sucursal, valor_meses_perdidos_empenos FROM sucursal");
    if (!$rSuc) {
        throw new RuntimeException("Error consultando sucursales: " . mysqli_error($conexion));
    }

    $qExists = "SELECT 1 FROM information_schema.tables WHERE table_schema = DATABASE() AND table_name = ?";
    $stExists = mysqli_prepare($conexion, $qExists);

    while ($s = mysqli_fetch_assoc($rSuc)) {
        $resumen['sucursales']++;

        $sucursalId = (int)($s['id_sucursal'] ?? 0);
        $mesesPerdidos = (int)($s['valor_meses_perdidos_empenos'] ?? 0);
        if ($sucursalId <= 0 || $mesesPerdidos <= 0) {
            continue;
        }

        $tablaLotes = 'lotes_' . $sucursalId;
        $tablaHistorico = 'historico_renovaciones_' . $sucursalId;
        if (!preg_match('/^lotes_\\d+$/', $tablaLotes) || !preg_match('/^historico_renovaciones_\\d+$/', $tablaHistorico)) {
            $resumen['errores']++;
            fwrite(STDERR, "[empenos_perdidos] Sucursal {$sucursalId}: nombre de tabla no permitido\n");
            continue;
        }

        $resumen['sucursales_validas']++;

        // Verificar existencia de tablas
        $existeLotes = true;
        $existeHist = true;
        if ($stExists) {
            mysqli_stmt_bind_param($stExists, 's', $tablaLotes);
            mysqli_stmt_execute($stExists);
            $rl = mysqli_stmt_get_result($stExists);
            $existeLotes = $rl && mysqli_num_rows($rl) > 0;

            mysqli_stmt_bind_param($stExists, 's', $tablaHistorico);
            mysqli_stmt_execute($stExists);
            $rh = mysqli_stmt_get_result($stExists);
            $existeHist = $rh && mysqli_num_rows($rh) > 0;
        }
        if (!$existeLotes) {
            $resumen['tablas_lotes_inexistentes']++;
            fwrite(STDERR, "[empenos_perdidos] Sucursal {$sucursalId}: no existe {$tablaLotes}\n");
            continue;
        }
        if (!$existeHist) {
            $resumen['tablas_historico_inexistentes']++;
            fwrite(STDERR, "[empenos_perdidos] Sucursal {$sucursalId}: no existe {$tablaHistorico}\n");
            continue;
        }

        // Listar lotes vencidos y perdibles
        $qLotes = "SELECT id_lote FROM {$tablaLotes} WHERE estado_lote = 'vencido' AND lote_perdible = 'true'";
        $rLotes = mysqli_query($conexion, $qLotes);
        if (!$rLotes) {
            $resumen['errores']++;
            fwrite(STDERR, "[empenos_perdidos] Sucursal {$sucursalId}: error consultando lotes: " . mysqli_error($conexion) . "\n");
            continue;
        }

        $lotes = [];
        while ($row = mysqli_fetch_assoc($rLotes)) {
            $lotes[] = (int) $row['id_lote'];
        }
        if (!$lotes) {
            continue;
        }

        mysqli_begin_transaction($conexion);
        try {
            // Statements reutilizables
            $stCountVenc = mysqli_prepare(
                $conexion,
                "SELECT COUNT(*) AS c FROM {$tablaHistorico} WHERE lote = ? AND estado_historico = 'Vencido'"
            );
            if (!$stCountVenc) {
                throw new RuntimeException("No se pudo preparar COUNT vencidas: " . mysqli_error($conexion));
            }

            $stUpdLote = mysqli_prepare(
                $conexion,
                "UPDATE {$tablaLotes}
                 SET estado_lote = 'perdido',
                     fecha_perdido = CURDATE(),
                     numero_semana_empenio_perdido = ?,
                     year_empenio_perdido = ?,
                     estado_envio = 'pendiente_enviar',
                     envio_numero = 0
                 WHERE id_lote = ?"
            );
            if (!$stUpdLote) {
                throw new RuntimeException("No se pudo preparar UPDATE lote: " . mysqli_error($conexion));
            }

            $stUpdArt = mysqli_prepare(
                $conexion,
                "UPDATE rel_articulos_estados
                 SET fecha_perdido_empenio = CURDATE(),
                     rel_numero_semana_empenio_perdido = ?,
                     year_rel_empenio_perdido = ?,
                     estado_articulo = 'pendiente_enviar'
                 WHERE rel_id_lote = ? AND rel_id_sucursal = ?"
            );
            if (!$stUpdArt) {
                throw new RuntimeException("No se pudo preparar UPDATE rel_articulos_estados: " . mysqli_error($conexion));
            }

            $stTraz = mysqli_prepare(
                $conexion,
                "INSERT INTO trazabilidad_lotes (
                    id_lote, fecha_accion, usuario_accion, accion_trazabilidad, comentarios_accion, sucursal_accion
                ) VALUES (?, NOW(), '1', ?, ?, ?)"
            );
            if (!$stTraz) {
                throw new RuntimeException("No se pudo preparar INSERT trazabilidad_lotes: " . mysqli_error($conexion));
            }

            $stTarea = mysqli_prepare(
                $conexion,
                "INSERT INTO tareas_cron (descripcion_evento, fecha) VALUES (?, NOW())"
            );
            if (!$stTarea) {
                throw new RuntimeException("No se pudo preparar INSERT tareas_cron: " . mysqli_error($conexion));
            }

            $stGetEnFecha = mysqli_prepare(
                $conexion,
                "SELECT id_renovaciones
                 FROM {$tablaHistorico}
                 WHERE lote = ? AND estado_historico = 'enfecha'
                 ORDER BY id_renovaciones DESC
                 LIMIT 1"
            );
            if (!$stGetEnFecha) {
                throw new RuntimeException("No se pudo preparar SELECT enfecha: " . mysqli_error($conexion));
            }

            $stUpdHistPerd = mysqli_prepare(
                $conexion,
                "UPDATE {$tablaHistorico}
                 SET estado_historico = 'Perdido',
                     fecha_vencido = CURDATE(),
                     fecha_perdido = CURDATE()
                 WHERE id_renovaciones = ?"
            );
            if (!$stUpdHistPerd) {
                throw new RuntimeException("No se pudo preparar UPDATE historico perdido: " . mysqli_error($conexion));
            }

            $stAccion = mysqli_prepare(
                $conexion,
                "INSERT INTO acciones_historico_renovaciones (
                    sucursal, accion, origen, lote_accion, historico_id, fecha_accion, empleado
                ) VALUES (?, ?, 'cron', ?, ?, NOW(), '1')"
            );
            if (!$stAccion) {
                throw new RuntimeException("No se pudo preparar INSERT acciones_historico_renovaciones: " . mysqli_error($conexion));
            }

            foreach ($lotes as $loteId) {
                if ($loteId <= 0) {
                    continue;
                }
                $resumen['lotes_evaluados']++;

                // Cuántas renovaciones vencidas tiene este lote
                mysqli_stmt_bind_param($stCountVenc, 'i', $loteId);
                mysqli_stmt_execute($stCountVenc);
                $rCount = mysqli_stmt_get_result($stCountVenc);
                $rowCount = $rCount ? mysqli_fetch_assoc($rCount) : null;
                $vencidas = $rowCount ? (int) $rowCount['c'] : 0;

                if ($vencidas < $mesesPerdidos) {
                    continue;
                }

                // Actualizar lote a perdido
                mysqli_stmt_bind_param($stUpdLote, 'iii', $numeroSemanaPerdido, $anyoListadoPerdido, $loteId);
                if (!mysqli_stmt_execute($stUpdLote)) {
                    throw new RuntimeException("Error UPDATE lote {$loteId}: " . mysqli_stmt_error($stUpdLote));
                }

                // Actualizar artículos del lote
                mysqli_stmt_bind_param($stUpdArt, 'iiii', $numeroSemanaPerdido, $anyoListadoPerdido, $loteId, $sucursalId);
                if (!mysqli_stmt_execute($stUpdArt)) {
                    throw new RuntimeException("Error UPDATE rel_articulos_estados lote {$loteId}: " . mysqli_stmt_error($stUpdArt));
                }

                // Control global (si existe)
                if (function_exists('insert_global_cron')) {
                    $descripcionCron = "empeño perdido por el cron Nº {$loteId} de la sucursal Nº {$sucursalId}";
                    $tipoOperacion = "Empenoperdido";
                    insert_global_cron($conexion, $descripcionCron, $sucursalId, $tipoOperacion);
                }

                // Trazabilidad: perdido
                $accion1 = 'perdido';
                $coment1 = 'Lote perdido automaticamente';
                mysqli_stmt_bind_param($stTraz, 'issi', $loteId, $accion1, $coment1, $sucursalId);
                if (!mysqli_stmt_execute($stTraz)) {
                    throw new RuntimeException("Error INSERT trazabilidad perdido lote {$loteId}: " . mysqli_stmt_error($stTraz));
                }

                // Trazabilidad: pendiente_enviar
                $accion2 = 'pendiente_enviar';
                $coment2 = "Lote listo para enviar en la semana {$numeroSemanaPerdido}";
                mysqli_stmt_bind_param($stTraz, 'issi', $loteId, $accion2, $coment2, $sucursalId);
                if (!mysqli_stmt_execute($stTraz)) {
                    throw new RuntimeException("Error INSERT trazabilidad pendiente_enviar lote {$loteId}: " . mysqli_stmt_error($stTraz));
                }

                // tareas_cron
                $desc1 = "UPDATE lote perdido {$loteId} Sucursal {$sucursalId}";
                mysqli_stmt_bind_param($stTarea, 's', $desc1);
                mysqli_stmt_execute($stTarea);

                $desc2 = "UPDATE empeño perdido listo para enviar {$loteId} Sucursal {$sucursalId}";
                mysqli_stmt_bind_param($stTarea, 's', $desc2);
                mysqli_stmt_execute($stTarea);

                // Marcar el histórico "enfecha" como Perdido (si existe)
                mysqli_stmt_bind_param($stGetEnFecha, 'i', $loteId);
                mysqli_stmt_execute($stGetEnFecha);
                $rEn = mysqli_stmt_get_result($stGetEnFecha);
                $rowEn = $rEn ? mysqli_fetch_assoc($rEn) : null;
                $idRen = $rowEn ? (int) $rowEn['id_renovaciones'] : 0;

                if ($idRen > 0) {
                    mysqli_stmt_bind_param($stUpdHistPerd, 'i', $idRen);
                    if (!mysqli_stmt_execute($stUpdHistPerd)) {
                        throw new RuntimeException("Error UPDATE historico perdido {$idRen}: " . mysqli_stmt_error($stUpdHistPerd));
                    }

                    $accionTxt = "el historico id {$idRen} ha sido actualizado a Perdido";
                    $loteStr = (string) $loteId;
                    mysqli_stmt_bind_param($stAccion, 'issi', $sucursalId, $accionTxt, $loteStr, $idRen);
                    mysqli_stmt_execute($stAccion);
                }

                $resumen['lotes_perdidos']++;
            }

            mysqli_stmt_close($stCountVenc);
            mysqli_stmt_close($stUpdLote);
            mysqli_stmt_close($stUpdArt);
            mysqli_stmt_close($stTraz);
            mysqli_stmt_close($stTarea);
            mysqli_stmt_close($stGetEnFecha);
            mysqli_stmt_close($stUpdHistPerd);
            mysqli_stmt_close($stAccion);

            mysqli_commit($conexion);
        } catch (Throwable $e) {
            mysqli_rollback($conexion);
            $resumen['errores']++;
            fwrite(STDERR, "[empenos_perdidos] Sucursal {$sucursalId}: {$e->getMessage()}\n");
        }
    }

    if ($stExists) {
        mysqli_stmt_close($stExists);
    }

    return $resumen;
}

