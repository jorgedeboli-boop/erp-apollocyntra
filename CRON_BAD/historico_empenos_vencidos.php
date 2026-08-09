<?php
declare(strict_types=1);

/**
 * Marca como vencidas las renovaciones "enfecha" que han vencido en el mes actual,
 * genera la nueva cuota (siguiente vencimiento +1 mes), registra acciones, trazabilidad y tareas_cron,
 * y actualiza el lote a estado "vencido" si aplica.
 *
 * Tablas por sucursal:
 * - historico_renovaciones_{id_sucursal}
 * - lotes_{id_sucursal}
 *
 * Tablas globales:
 * - acciones_historico_renovaciones
 * - trazabilidad_lotes
 * - tareas_cron
 */

function cron_historico_empenos_vencidos(mysqli $conexion): array
{
    $resumen = [
        'sucursales' => 0,
        'sucursales_validas' => 0,
        'tablas_historico_inexistentes' => 0,
        'tablas_lotes_inexistentes' => 0,
        'renovaciones_vencidas_procesadas' => 0,
        'errores' => 0,
    ];

    @mysqli_set_charset($conexion, 'utf8');

    $hoy = new DateTimeImmutable('today');
    $mesActual = $hoy->format('Y-m');

    $rSuc = mysqli_query($conexion, "SELECT id_sucursal FROM sucursal");
    if (!$rSuc) {
        throw new RuntimeException("Error consultando sucursales: " . mysqli_error($conexion));
    }

    $qExists = "SELECT 1 FROM information_schema.tables WHERE table_schema = DATABASE() AND table_name = ?";
    $stExists = mysqli_prepare($conexion, $qExists);

    while ($s = mysqli_fetch_assoc($rSuc)) {
        $resumen['sucursales']++;
        $sucursalId = (int)($s['id_sucursal'] ?? 0);
        if ($sucursalId <= 0) {
            $resumen['errores']++;
            continue;
        }

        $tablaHistorico = 'historico_renovaciones_' . $sucursalId;
        $tablaLotes = 'lotes_' . $sucursalId;

        // Validación estricta de identificadores dinámicos
        if (!preg_match('/^historico_renovaciones_\\d+$/', $tablaHistorico) || !preg_match('/^lotes_\\d+$/', $tablaLotes)) {
            $resumen['errores']++;
            fwrite(STDERR, "[historico_empenos_vencidos] Sucursal {$sucursalId}: nombre de tabla no permitido\n");
            continue;
        }

        $resumen['sucursales_validas']++;

        // Verificar existencia de tablas
        $existeHistorico = true;
        $existeLotes = true;
        if ($stExists) {
            mysqli_stmt_bind_param($stExists, 's', $tablaHistorico);
            mysqli_stmt_execute($stExists);
            $rh = mysqli_stmt_get_result($stExists);
            $existeHistorico = $rh && mysqli_num_rows($rh) > 0;

            mysqli_stmt_bind_param($stExists, 's', $tablaLotes);
            mysqli_stmt_execute($stExists);
            $rl = mysqli_stmt_get_result($stExists);
            $existeLotes = $rl && mysqli_num_rows($rl) > 0;
        }

        if (!$existeHistorico) {
            $resumen['tablas_historico_inexistentes']++;
            fwrite(STDERR, "[historico_empenos_vencidos] Sucursal {$sucursalId}: no existe {$tablaHistorico}\n");
            continue;
        }
        if (!$existeLotes) {
            $resumen['tablas_lotes_inexistentes']++;
            fwrite(STDERR, "[historico_empenos_vencidos] Sucursal {$sucursalId}: no existe {$tablaLotes}\n");
            continue;
        }

        // Renovaciones vencidas SOLO del mes actual:
        // - estado_historico = 'enfecha'
        // - proximo_vencimiento < CURDATE()
        // - mes(proximo_vencimiento) == mesActual
        $qMaxPorLote = "
            SELECT MAX(id_renovaciones) AS max_id
            FROM {$tablaHistorico}
            WHERE lote = ?
        ";
        $stMaxPorLote = mysqli_prepare($conexion, $qMaxPorLote);
        if (!$stMaxPorLote) {
            $resumen['errores']++;
            fwrite(STDERR, "[historico_empenos_vencidos] Sucursal {$sucursalId}: error preparando MAX renovación por lote: " . mysqli_error($conexion) . "\n");
            continue;
        }

        $qRen = "
            SELECT id_renovaciones, lote, proximo_vencimiento
            FROM {$tablaHistorico}
            WHERE estado_historico = 'enfecha'
              AND proximo_vencimiento < CURRENT_DATE
        ";
        $rRen = mysqli_query($conexion, $qRen);
        if (!$rRen) {
            mysqli_stmt_close($stMaxPorLote);
            $resumen['errores']++;
            fwrite(STDERR, "[historico_empenos_vencidos] Sucursal {$sucursalId}: error consultando renovaciones: " . mysqli_error($conexion) . "\n");
            continue;
        }

        $renovaciones = [];
        while ($row = mysqli_fetch_assoc($rRen)) {
            $idRenovaciones = (int) ($row['id_renovaciones'] ?? 0);
            $loteid = (int) ($row['lote'] ?? 0);
            if ($idRenovaciones <= 0 || $loteid <= 0) {
                continue;
            }

            mysqli_stmt_bind_param($stMaxPorLote, 'i', $loteid);
            mysqli_stmt_execute($stMaxPorLote);
            $rMax = mysqli_stmt_get_result($stMaxPorLote);
            $rsmax = $rMax ? mysqli_fetch_assoc($rMax) : null;
            if ($rMax) {
                mysqli_free_result($rMax);
            }

            $maxId = (int) ($rsmax['max_id'] ?? 0);
            if ($idRenovaciones !== $maxId) {
                continue;
            }

            $renovaciones[] = $row;
        }
        mysqli_free_result($rRen);
        mysqli_stmt_close($stMaxPorLote);

        if (!$renovaciones) {
            continue;
        }

        mysqli_begin_transaction($conexion);
        try {
            // Statements reutilizables dentro de la sucursal
            $stLote = mysqli_prepare(
                $conexion,
                "SELECT id_lote, estado_lote, precio_compra, precio_recompra, compra_opcion FROM {$tablaLotes} WHERE id_lote = ?"
            );
            if (!$stLote) {
                throw new RuntimeException("No se pudo preparar SELECT de lote: " . mysqli_error($conexion));
            }

            $stUpdHist = mysqli_prepare(
                $conexion,
                "UPDATE {$tablaHistorico} SET estado_historico = 'Vencido', fecha_vencido = NOW() WHERE id_renovaciones = ?"
            );
            if (!$stUpdHist) {
                throw new RuntimeException("No se pudo preparar UPDATE historico: " . mysqli_error($conexion));
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

            $stInsHist = mysqli_prepare(
                $conexion,
                "INSERT INTO {$tablaHistorico} (
                    importe_renovacion, lote, proximo_vencimiento, estado_historico, fecha_insert
                ) VALUES (?, ?, ?, 'enfecha', NOW())"
            );
            if (!$stInsHist) {
                throw new RuntimeException("No se pudo preparar INSERT historico: " . mysqli_error($conexion));
            }

            $stUpdLote = mysqli_prepare(
                $conexion,
                "UPDATE {$tablaLotes} SET estado_lote = 'vencido' WHERE id_lote = ?"
            );
            if (!$stUpdLote) {
                throw new RuntimeException("No se pudo preparar UPDATE lote: " . mysqli_error($conexion));
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

            foreach ($renovaciones as $ren) {
                $idRen = (int)($ren['id_renovaciones'] ?? 0);
                $loteId = (int)($ren['lote'] ?? 0);
                $proxVenc = (string)($ren['proximo_vencimiento'] ?? '');

                if ($idRen <= 0 || $loteId <= 0 || $proxVenc === '') {
                    continue;
                }

                // Doble seguridad: mes actual (equivalente al if original)
                $mesVenc = date('Y-m', strtotime($proxVenc));
                if ($mesVenc !== $mesActual) {
                    continue;
                }

                // Obtener info del lote
                mysqli_stmt_bind_param($stLote, 'i', $loteId);
                mysqli_stmt_execute($stLote);
                $rLote = mysqli_stmt_get_result($stLote);
                $lote = $rLote ? mysqli_fetch_assoc($rLote) : null;
                if (!$lote) {
                    continue;
                }

                $estadoLote = (string)($lote['estado_lote'] ?? '');
                $compraOpcion = (string)($lote['compra_opcion'] ?? '');

                // Lógica original: solo continuar si está enfecha/vencido y compra_opcion='si'
                $continuar = in_array($estadoLote, ['enfecha', 'vencido'], true) && $compraOpcion === 'si';
                if (!$continuar) {
                    continue;
                }

                $precioCompra = (float)($lote['precio_compra'] ?? 0);
                $precioRecompra = (float)($lote['precio_recompra'] ?? 0);
                $importeRenovacion = $precioRecompra - $precioCompra;

                // Control global (si existe)
                if (function_exists('insert_global_cron')) {
                    $descripcionCron = "empeño vencido por el cron Nº {$loteId} de la sucursal Nº {$sucursalId}";
                    $tipoOperacion = "Empenovencido";
                    insert_global_cron($conexion, $descripcionCron, $sucursalId, $tipoOperacion);
                }

                // UPDATE historico -> Vencido
                mysqli_stmt_bind_param($stUpdHist, 'i', $idRen);
                if (!mysqli_stmt_execute($stUpdHist)) {
                    throw new RuntimeException("Error actualizando historico {$idRen}: " . mysqli_stmt_error($stUpdHist));
                }

                // Acción del update
                $accionTxt1 = "el historico id {$idRen} ha sido actualizado a vencido";
                $loteStr = (string)$loteId;
                mysqli_stmt_bind_param($stAccion, 'issi', $sucursalId, $accionTxt1, $loteStr, $idRen);
                if (!mysqli_stmt_execute($stAccion)) {
                    throw new RuntimeException("Error insertando acción update historico {$idRen}: " . mysqli_stmt_error($stAccion));
                }

                // Próximo vencimiento = +1 mes manteniendo día
                $dtVenc = new DateTimeImmutable($proxVenc);
                $proximoVencimiento = $dtVenc->modify('+1 month')->format('Y-m-d');

                // INSERT nueva cuota en fecha
                mysqli_stmt_bind_param($stInsHist, 'diss', $importeRenovacion, $loteId, $proximoVencimiento);
                if (!mysqli_stmt_execute($stInsHist)) {
                    throw new RuntimeException("Error insertando nueva cuota para lote {$loteId}: " . mysqli_stmt_error($stInsHist));
                }
                $nuevoId = (int)mysqli_insert_id($conexion);

                // Acción del insert
                $accionTxt2 = "el historico id {$nuevoId} ha sido insertado en fecha desde el vencido";
                mysqli_stmt_bind_param($stAccion, 'issi', $sucursalId, $accionTxt2, $loteStr, $nuevoId);
                if (!mysqli_stmt_execute($stAccion)) {
                    throw new RuntimeException("Error insertando acción insert historico {$nuevoId}: " . mysqli_stmt_error($stAccion));
                }

                // UPDATE lote -> vencido
                mysqli_stmt_bind_param($stUpdLote, 'i', $loteId);
                if (!mysqli_stmt_execute($stUpdLote)) {
                    throw new RuntimeException("Error actualizando lote {$loteId} a vencido: " . mysqli_stmt_error($stUpdLote));
                }

                // Trazabilidad
                $accionTraz = 'vencido';
                $comentario = 'Lote vencido automaticamente';
                mysqli_stmt_bind_param($stTraz, 'issi', $loteId, $accionTraz, $comentario, $sucursalId);
                if (!mysqli_stmt_execute($stTraz)) {
                    throw new RuntimeException("Error insertando trazabilidad lote {$loteId}: " . mysqli_stmt_error($stTraz));
                }

                // tareas_cron
                $descEvento = "UPDATE lote vencido {$loteId} Sucursal {$sucursalId}";
                mysqli_stmt_bind_param($stTarea, 's', $descEvento);
                if (!mysqli_stmt_execute($stTarea)) {
                    throw new RuntimeException("Error insertando tareas_cron lote {$loteId}: " . mysqli_stmt_error($stTarea));
                }

                $resumen['renovaciones_vencidas_procesadas']++;
            }

            mysqli_stmt_close($stLote);
            mysqli_stmt_close($stUpdHist);
            mysqli_stmt_close($stAccion);
            mysqli_stmt_close($stInsHist);
            mysqli_stmt_close($stUpdLote);
            mysqli_stmt_close($stTraz);
            mysqli_stmt_close($stTarea);

            mysqli_commit($conexion);
        } catch (Throwable $e) {
            mysqli_rollback($conexion);
            $resumen['errores']++;
            fwrite(STDERR, "[historico_empenos_vencidos] Sucursal {$sucursalId}: {$e->getMessage()}\n");
        }
    }

    if ($stExists) {
        mysqli_stmt_close($stExists);
    }

    return $resumen;
}

