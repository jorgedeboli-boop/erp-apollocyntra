<?php
declare(strict_types=1);

/**
 * Pasa lotes a "pendiente_enviar" (solo los lunes).
 *
 * Casos:
 * 1) Empeños perdidos: lotes_{sucursal} con compra_opcion='si', estado_lote='perdido', estado_envio='false', envio_numero=0
 *    y fecha_perdido < fecha_semana_hasta (de numeroSemanaEnvio()).
 *    - Actualiza lote + rel_articulos_estados
 *    - Control cron global (si existe)
 *    - Trazabilidad + tareas_cron
 *
 * 2) Lotes liberados (compra): liberado='si', compra_opcion='no', estado_lote='compra', estado_envio='false', envio_numero=0
 *    y fecha_compra < fecha_semana_hasta
 *    - Si estado_lote == 'intervenido': inserta en lotes_intervenidos_envios
 *    - Si no: actualiza lote + rel_articulos_estados, trazabilidad + tareas_cron
 *
 * 3) Lotes liberados con intervenido='true' (pero estado_lote != 'intervenido'):
 *    liberado='si', intervenido='true', estado_envio='false', fecha_compra < fecha_semana_hasta
 *    - Actualiza lote + rel_articulos_estados, trazabilidad + tareas_cron
 */

function cron_lotes_para_enviar(mysqli $conexion): array
{
    $resumen = [
        'skipped_no_lunes' => 0,
        'sucursales' => 0,
        'sucursales_validas' => 0,
        'tablas_lotes_inexistentes' => 0,
        'errores' => 0,
        'perdidos_actualizados' => 0,
        'liberados_actualizados' => 0,
        'intervenidos_insertados' => 0,
    ];

    @mysqli_set_charset($conexion, 'utf8');

    // SOLO LUNES (lunes = 1)
    if ((int)date('N') !== 1) {
        $resumen['skipped_no_lunes'] = 1;
        return $resumen;
    }

    // Corte de semana desde listado_numero_semanas (3 semanas atrás)
    $datosSemana = numeroSemanaEnvio(); // abre/cierra su conexión por diseño
    if (!$datosSemana || !isset($datosSemana['fecha_semana_hasta'])) {
        throw new RuntimeException("No se pudo obtener numeroSemanaEnvio()");
    }
    $fechaSemanaHasta = (string)$datosSemana['fecha_semana_hasta'];

    $rSuc = mysqli_query($conexion, "SELECT id_sucursal FROM sucursal");
    if (!$rSuc) {
        throw new RuntimeException("Error consultando sucursales: " . mysqli_error($conexion));
    }

    $stExists = mysqli_prepare(
        $conexion,
        "SELECT 1 FROM information_schema.tables WHERE table_schema = DATABASE() AND table_name = ?"
    );

    while ($s = mysqli_fetch_assoc($rSuc)) {
        $resumen['sucursales']++;
        $sucursalId = (int)($s['id_sucursal'] ?? 0);
        if ($sucursalId <= 0) {
            $resumen['errores']++;
            continue;
        }

        $tablaLotes = 'lotes_' . $sucursalId;
        if (!preg_match('/^lotes_\\d+$/', $tablaLotes)) {
            $resumen['errores']++;
            fwrite(STDERR, "[lotes_para_enviar] Sucursal {$sucursalId}: nombre de tabla no permitido\n");
            continue;
        }

        $resumen['sucursales_validas']++;

        // Verificar existencia de la tabla lotes_{sucursal}
        $existeLotes = true;
        if ($stExists) {
            mysqli_stmt_bind_param($stExists, 's', $tablaLotes);
            mysqli_stmt_execute($stExists);
            $rt = mysqli_stmt_get_result($stExists);
            $existeLotes = $rt && mysqli_num_rows($rt) > 0;
        }
        if (!$existeLotes) {
            $resumen['tablas_lotes_inexistentes']++;
            fwrite(STDERR, "[lotes_para_enviar] Sucursal {$sucursalId}: no existe {$tablaLotes}\n");
            continue;
        }

        mysqli_begin_transaction($conexion);
        try {
            // ---------- Bloque 1: empeños perdidos ----------
            $qPerdidos = "SELECT id_lote
                          FROM {$tablaLotes}
                          WHERE compra_opcion = 'si'
                            AND estado_envio = 'false'
                            AND envio_numero = 0
                            AND estado_lote = 'perdido'
                            AND fecha_perdido < ?";
            $stPerdidos = mysqli_prepare($conexion, $qPerdidos);
            if (!$stPerdidos) {
                throw new RuntimeException("No se pudo preparar SELECT perdidos: " . mysqli_error($conexion));
            }
            mysqli_stmt_bind_param($stPerdidos, 's', $fechaSemanaHasta);
            mysqli_stmt_execute($stPerdidos);
            $rPerdidos = mysqli_stmt_get_result($stPerdidos);
            $perdidos = [];
            while ($rPerdidos && ($row = mysqli_fetch_assoc($rPerdidos))) {
                $perdidos[] = (int)$row['id_lote'];
            }
            mysqli_stmt_close($stPerdidos);

            $stUpdEnvioLote = mysqli_prepare(
                $conexion,
                "UPDATE {$tablaLotes}
                 SET estado_envio = 'pendiente_enviar'
                 WHERE id_lote = ? AND estado_envio = 'false'"
            );
            if (!$stUpdEnvioLote) {
                throw new RuntimeException("No se pudo preparar UPDATE estado_envio: " . mysqli_error($conexion));
            }

            $stUpdRel = mysqli_prepare(
                $conexion,
                "UPDATE rel_articulos_estados
                 SET estado_articulo = 'pendiente_enviar'
                 WHERE rel_id_lote = ? AND rel_id_sucursal = ?"
            );
            if (!$stUpdRel) {
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

            foreach ($perdidos as $loteId) {
                if ($loteId <= 0) {
                    continue;
                }

                mysqli_stmt_bind_param($stUpdEnvioLote, 'i', $loteId);
                if (!mysqli_stmt_execute($stUpdEnvioLote)) {
                    throw new RuntimeException("Error UPDATE lote {$loteId}: " . mysqli_stmt_error($stUpdEnvioLote));
                }

                mysqli_stmt_bind_param($stUpdRel, 'ii', $loteId, $sucursalId);
                mysqli_stmt_execute($stUpdRel);

                if (function_exists('insert_global_cron')) {
                    $descripcionCron = "lote listo para enviar Nº {$loteId} de la sucursal Nº {$sucursalId}";
                    $tipoOperacion = "Loteparaenviar";
                    insert_global_cron($conexion, $descripcionCron, $sucursalId, $tipoOperacion);
                }

                $accion = 'pendiente_enviar';
                $coment = 'Lote listo para enviar';
                mysqli_stmt_bind_param($stTraz, 'issi', $loteId, $accion, $coment, $sucursalId);
                mysqli_stmt_execute($stTraz);

                $desc = "UPDATE empeño perdido listo para enviar {$loteId} Sucursal {$sucursalId}";
                mysqli_stmt_bind_param($stTarea, 's', $desc);
                mysqli_stmt_execute($stTarea);

                $resumen['perdidos_actualizados']++;
            }

            // ---------- Bloque 2: lotes liberados (compra) ----------
            $qLiberados = "SELECT id_lote, estado_lote
                           FROM {$tablaLotes}
                           WHERE liberado = 'si'
                             AND compra_opcion = 'no'
                             AND estado_envio = 'false'
                             AND envio_numero = 0
                             AND estado_lote = 'compra'
                             AND fecha_compra < ?";
            $stLiberados = mysqli_prepare($conexion, $qLiberados);
            if (!$stLiberados) {
                throw new RuntimeException("No se pudo preparar SELECT liberados: " . mysqli_error($conexion));
            }
            mysqli_stmt_bind_param($stLiberados, 's', $fechaSemanaHasta);
            mysqli_stmt_execute($stLiberados);
            $rLiberados = mysqli_stmt_get_result($stLiberados);
            $liberados = [];
            while ($rLiberados && ($row = mysqli_fetch_assoc($rLiberados))) {
                $liberados[] = ['id_lote' => (int)$row['id_lote'], 'estado_lote' => (string)$row['estado_lote']];
            }
            mysqli_stmt_close($stLiberados);

            // Insert intervenidos
            $stInsIntervenido = mysqli_prepare(
                $conexion,
                "INSERT INTO lotes_intervenidos_envios (
                    id_lote_intervenido, id_sucursal_intervenido, fecha_creacion, estado_intervenido
                 ) VALUES (?, ?, NOW(), 'pendiente_auditar')"
            );
            // Si no existe la tabla, simplemente no insertamos (no rompemos el cron)

            foreach ($liberados as $l) {
                $loteId = (int)$l['id_lote'];
                $estadoLote = (string)$l['estado_lote'];
                if ($loteId <= 0) {
                    continue;
                }

                if ($estadoLote === 'intervenido') {
                    if ($stInsIntervenido) {
                        mysqli_stmt_bind_param($stInsIntervenido, 'ii', $loteId, $sucursalId);
                        mysqli_stmt_execute($stInsIntervenido);
                        $resumen['intervenidos_insertados']++;
                    }
                    continue;
                }

                // marcar pendiente_enviar
                $stUpdEnvioLote2 = mysqli_prepare(
                    $conexion,
                    "UPDATE {$tablaLotes}
                     SET estado_envio = 'pendiente_enviar', envio_numero = 0
                     WHERE id_lote = ? AND estado_envio = 'false'"
                );
                if (!$stUpdEnvioLote2) {
                    throw new RuntimeException("No se pudo preparar UPDATE lote liberado: " . mysqli_error($conexion));
                }
                mysqli_stmt_bind_param($stUpdEnvioLote2, 'i', $loteId);
                mysqli_stmt_execute($stUpdEnvioLote2);
                mysqli_stmt_close($stUpdEnvioLote2);

                mysqli_stmt_bind_param($stUpdRel, 'ii', $loteId, $sucursalId);
                mysqli_stmt_execute($stUpdRel);

                $desc = "UPDATE lote liberado listo para enviar {$loteId} Sucursal {$sucursalId}";
                mysqli_stmt_bind_param($stTarea, 's', $desc);
                mysqli_stmt_execute($stTarea);

                $accion = 'pendiente_enviar';
                $coment = 'Lote listo para enviar (CRON)';
                mysqli_stmt_bind_param($stTraz, 'issi', $loteId, $accion, $coment, $sucursalId);
                mysqli_stmt_execute($stTraz);

                $resumen['liberados_actualizados']++;
            }

            if ($stInsIntervenido) {
                mysqli_stmt_close($stInsIntervenido);
            }

            // ---------- Bloque 3: liberados con intervenido=true (no estado_lote intervenido) ----------
            $qInter = "SELECT id_lote
                       FROM {$tablaLotes}
                       WHERE liberado = 'si'
                         AND estado_lote != 'intervenido'
                         AND estado_envio = 'false'
                         AND intervenido = 'true'
                         AND fecha_compra < ?";
            $stInter = mysqli_prepare($conexion, $qInter);
            if (!$stInter) {
                throw new RuntimeException("No se pudo preparar SELECT intervenido=true: " . mysqli_error($conexion));
            }
            mysqli_stmt_bind_param($stInter, 's', $fechaSemanaHasta);
            mysqli_stmt_execute($stInter);
            $rInter = mysqli_stmt_get_result($stInter);
            $inter = [];
            while ($rInter && ($row = mysqli_fetch_assoc($rInter))) {
                $inter[] = (int)$row['id_lote'];
            }
            mysqli_stmt_close($stInter);

            $stUpdEnvioLote3 = mysqli_prepare(
                $conexion,
                "UPDATE {$tablaLotes}
                 SET estado_envio = 'pendiente_enviar', envio_numero = 0
                 WHERE id_lote = ? AND estado_envio = 'false'"
            );
            if (!$stUpdEnvioLote3) {
                throw new RuntimeException("No se pudo preparar UPDATE lote intervenido=true: " . mysqli_error($conexion));
            }

            foreach ($inter as $loteId) {
                if ($loteId <= 0) {
                    continue;
                }

                mysqli_stmt_bind_param($stUpdEnvioLote3, 'i', $loteId);
                mysqli_stmt_execute($stUpdEnvioLote3);

                mysqli_stmt_bind_param($stUpdRel, 'ii', $loteId, $sucursalId);
                mysqli_stmt_execute($stUpdRel);

                $desc = "UPDATE lote liberado listo para enviar {$loteId} Sucursal {$sucursalId}";
                mysqli_stmt_bind_param($stTarea, 's', $desc);
                mysqli_stmt_execute($stTarea);

                $accion = 'pendiente_enviar';
                $coment = 'Lote listo para enviar (CRON)';
                mysqli_stmt_bind_param($stTraz, 'issi', $loteId, $accion, $coment, $sucursalId);
                mysqli_stmt_execute($stTraz);

                $resumen['liberados_actualizados']++;
            }

            mysqli_stmt_close($stUpdEnvioLote);
            mysqli_stmt_close($stUpdEnvioLote3);
            mysqli_stmt_close($stUpdRel);
            mysqli_stmt_close($stTraz);
            mysqli_stmt_close($stTarea);

            mysqli_commit($conexion);
        } catch (Throwable $e) {
            mysqli_rollback($conexion);
            $resumen['errores']++;
            fwrite(STDERR, "[lotes_para_enviar] Sucursal {$sucursalId}: {$e->getMessage()}\n");
        }
    }

    if ($stExists) {
        mysqli_stmt_close($stExists);
    }

    return $resumen;
}

