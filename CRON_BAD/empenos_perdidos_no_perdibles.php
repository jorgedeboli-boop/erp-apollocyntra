<?php
declare(strict_types=1);

/**
 * Control de empeños vencidos NO perdibles:
 * - Lotes en estado 'vencido' con lote_perdible = 'false'
 * - Si tienen (valor_meses_perdidos_empenos + 2) cuotas vencidas (estado_historico='Vencido'),
 *   se registra trazabilidad/tareas y se guarda en `empenos_vencidos_no_perdibles`.
 * - Si no cumplen, se elimina su registro de `empenos_vencidos_no_perdibles` (si existía).
 */

function cron_empenos_perdidos_no_perdibles(mysqli $conexion): array
{
    $resumen = [
        'sucursales' => 0,
        'sucursales_validas' => 0,
        'tablas_lotes_inexistentes' => 0,
        'tablas_historico_inexistentes' => 0,
        'tabla_control_inexistente' => 0,
        'lotes_evaluados' => 0,
        'lotes_marcados' => 0,
        'errores' => 0,
    ];

    @mysqli_set_charset($conexion, 'utf8');

    // Semana actual + año (esta función abre/cierra su propia conexión por diseño).
    $datosSemana = numeroSemanaActualConAnyo();
    if (!$datosSemana || !isset($datosSemana['numero_semana'], $datosSemana['anyo_listado'])) {
        throw new RuntimeException("No se pudo obtener numeroSemanaActualConAnyo()");
    }
    $numeroSemana = (int) $datosSemana['numero_semana'];
    $anyoListado = (int) $datosSemana['anyo_listado'];

    // Verificar tabla global de control
    $existeControl = true;
    $stExists = mysqli_prepare(
        $conexion,
        "SELECT 1 FROM information_schema.tables WHERE table_schema = DATABASE() AND table_name = ?"
    );
    if ($stExists) {
        $tablaControl = 'empenos_vencidos_no_perdibles';
        mysqli_stmt_bind_param($stExists, 's', $tablaControl);
        mysqli_stmt_execute($stExists);
        $r = mysqli_stmt_get_result($stExists);
        $existeControl = $r && mysqli_num_rows($r) > 0;
    }
    if (!$existeControl) {
        $resumen['tabla_control_inexistente']++;
        fwrite(STDERR, "[empenos_perdidos_no_perdibles] No existe la tabla empenos_vencidos_no_perdibles\n");
        if ($stExists) {
            mysqli_stmt_close($stExists);
        }
        return $resumen;
    }

    $rSuc = mysqli_query($conexion, "SELECT id_sucursal, valor_meses_perdidos_empenos FROM sucursal");
    if (!$rSuc) {
        if ($stExists) {
            mysqli_stmt_close($stExists);
        }
        throw new RuntimeException("Error consultando sucursales: " . mysqli_error($conexion));
    }

    while ($s = mysqli_fetch_assoc($rSuc)) {
        $resumen['sucursales']++;

        $sucursalId = (int)($s['id_sucursal'] ?? 0);
        $meses = (int)($s['valor_meses_perdidos_empenos'] ?? 0);
        if ($sucursalId <= 0 || $meses <= 0) {
            continue;
        }

        $tablaLotes = 'lotes_' . $sucursalId;
        $tablaHistorico = 'historico_renovaciones_' . $sucursalId;
        if (!preg_match('/^lotes_\\d+$/', $tablaLotes) || !preg_match('/^historico_renovaciones_\\d+$/', $tablaHistorico)) {
            $resumen['errores']++;
            fwrite(STDERR, "[empenos_perdidos_no_perdibles] Sucursal {$sucursalId}: nombre de tabla no permitido\n");
            continue;
        }

        $resumen['sucursales_validas']++;

        // Verificar existencia de tablas por sucursal
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
            fwrite(STDERR, "[empenos_perdidos_no_perdibles] Sucursal {$sucursalId}: no existe {$tablaLotes}\n");
            continue;
        }
        if (!$existeHist) {
            $resumen['tablas_historico_inexistentes']++;
            fwrite(STDERR, "[empenos_perdidos_no_perdibles] Sucursal {$sucursalId}: no existe {$tablaHistorico}\n");
            continue;
        }

        $umbral = $meses + 2;

        // Listar lotes vencidos NO perdibles
        $rLotes = mysqli_query(
            $conexion,
            "SELECT id_lote FROM {$tablaLotes} WHERE estado_lote = 'vencido' AND lote_perdible = 'false'"
        );
        if (!$rLotes) {
            $resumen['errores']++;
            fwrite(STDERR, "[empenos_perdidos_no_perdibles] Sucursal {$sucursalId}: error consultando lotes: " . mysqli_error($conexion) . "\n");
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
            $stCountVenc = mysqli_prepare(
                $conexion,
                "SELECT COUNT(*) AS c FROM {$tablaHistorico} WHERE lote = ? AND estado_historico = 'Vencido'"
            );
            if (!$stCountVenc) {
                throw new RuntimeException("No se pudo preparar COUNT vencidas: " . mysqli_error($conexion));
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

            $stDel = mysqli_prepare(
                $conexion,
                "DELETE FROM empenos_vencidos_no_perdibles WHERE id_lote_rel = ? AND id_sucursal_rel = ?"
            );
            if (!$stDel) {
                throw new RuntimeException("No se pudo preparar DELETE control: " . mysqli_error($conexion));
            }

            $stIns = mysqli_prepare(
                $conexion,
                "INSERT INTO empenos_vencidos_no_perdibles (
                    id_lote_rel, id_sucursal_rel, cuotas_vencidas, usuario_update, fecha_update
                ) VALUES (?, ?, ?, '1', NOW())"
            );
            if (!$stIns) {
                throw new RuntimeException("No se pudo preparar INSERT control: " . mysqli_error($conexion));
            }

            foreach ($lotes as $loteId) {
                if ($loteId <= 0) {
                    continue;
                }
                $resumen['lotes_evaluados']++;

                mysqli_stmt_bind_param($stCountVenc, 'i', $loteId);
                mysqli_stmt_execute($stCountVenc);
                $rc = mysqli_stmt_get_result($stCountVenc);
                $rowc = $rc ? mysqli_fetch_assoc($rc) : null;
                $vencidas = $rowc ? (int) $rowc['c'] : 0;

                // Siempre limpiamos el registro previo (igual que el original)
                mysqli_stmt_bind_param($stDel, 'ii', $loteId, $sucursalId);
                mysqli_stmt_execute($stDel);

                if ($vencidas >= $umbral) {
                    $accion = 'noperdible';
                    $coment = 'Lote pasado 2 renovaciones de 3 vencidas';
                    mysqli_stmt_bind_param($stTraz, 'issi', $loteId, $accion, $coment, $sucursalId);
                    mysqli_stmt_execute($stTraz);

                    $desc = "Lote Nº {$loteId} pasado 2 renovaciones de 3 vencidas Sucursal {$sucursalId}";
                    mysqli_stmt_bind_param($stTarea, 's', $desc);
                    mysqli_stmt_execute($stTarea);

                    mysqli_stmt_bind_param($stIns, 'iii', $loteId, $sucursalId, $vencidas);
                    mysqli_stmt_execute($stIns);

                    $resumen['lotes_marcados']++;
                }
            }

            mysqli_stmt_close($stCountVenc);
            mysqli_stmt_close($stTraz);
            mysqli_stmt_close($stTarea);
            mysqli_stmt_close($stDel);
            mysqli_stmt_close($stIns);

            mysqli_commit($conexion);
        } catch (Throwable $e) {
            mysqli_rollback($conexion);
            $resumen['errores']++;
            fwrite(STDERR, "[empenos_perdidos_no_perdibles] Sucursal {$sucursalId}: {$e->getMessage()}\n");
        }
    }

    if ($stExists) {
        mysqli_stmt_close($stExists);
    }

    return $resumen;
}

