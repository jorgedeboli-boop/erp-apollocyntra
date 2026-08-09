<?php
declare(strict_types=1);

/**
 * Ajusta vencimientos "29 de febrero" en años NO bisiestos:
 * - historico_renovaciones_{sucursal}.proximo_vencimiento: YYYY-02-29 -> YYYY-03-01
 * - lotes_{sucursal}.fecha_vencimiento: YYYY-02-29 -> YYYY-03-01
 *
 * Se ejecuta rápido con UPDATE directos y valida tablas dinámicas.
 */

function cron_vencimiento_empeno_29_febrero(mysqli $conexion): array
{
    $resumen = [
        'skipped_bisiesto' => 0,
        'sucursales' => 0,
        'sucursales_validas' => 0,
        'tablas_historico_inexistentes' => 0,
        'tablas_lotes_inexistentes' => 0,
        'actualizados_historico' => 0,
        'actualizados_lotes' => 0,
        'errores' => 0,
    ];

    @mysqli_set_charset($conexion, 'utf8');

    $anyoActual = (int)date('Y');
    // Si el año es bisiesto, NO corregimos (29/02 es válido).
    if (checkdate(2, 29, $anyoActual)) {
        $resumen['skipped_bisiesto'] = 1;
        return $resumen;
    }

    $diaBisiesto = sprintf('%04d-02-29', $anyoActual);
    $diaUpdate = sprintf('%04d-03-01', $anyoActual);

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

        $tablaHistorico = 'historico_renovaciones_' . $sucursalId;
        $tablaLotes = 'lotes_' . $sucursalId;

        if (!preg_match('/^historico_renovaciones_\\d+$/', $tablaHistorico) || !preg_match('/^lotes_\\d+$/', $tablaLotes)) {
            $resumen['errores']++;
            fwrite(STDERR, "[vencimiento_29_febrero] Sucursal {$sucursalId}: nombre de tabla no permitido\n");
            continue;
        }

        $resumen['sucursales_validas']++;

        // Verificar existencia de tablas
        $existeHist = true;
        $existeLotes = true;
        if ($stExists) {
            mysqli_stmt_bind_param($stExists, 's', $tablaHistorico);
            mysqli_stmt_execute($stExists);
            $rh = mysqli_stmt_get_result($stExists);
            $existeHist = $rh && mysqli_num_rows($rh) > 0;

            mysqli_stmt_bind_param($stExists, 's', $tablaLotes);
            mysqli_stmt_execute($stExists);
            $rl = mysqli_stmt_get_result($stExists);
            $existeLotes = $rl && mysqli_num_rows($rl) > 0;
        }

        if (!$existeHist) {
            $resumen['tablas_historico_inexistentes']++;
            fwrite(STDERR, "[vencimiento_29_febrero] Sucursal {$sucursalId}: no existe {$tablaHistorico}\n");
        }
        if (!$existeLotes) {
            $resumen['tablas_lotes_inexistentes']++;
            fwrite(STDERR, "[vencimiento_29_febrero] Sucursal {$sucursalId}: no existe {$tablaLotes}\n");
        }
        if (!$existeHist && !$existeLotes) {
            continue;
        }

        mysqli_begin_transaction($conexion);
        try {
            if ($existeHist) {
                $sqlH = "UPDATE {$tablaHistorico} SET proximo_vencimiento = ? WHERE proximo_vencimiento = ?";
                $stH = mysqli_prepare($conexion, $sqlH);
                if (!$stH) {
                    throw new RuntimeException("No se pudo preparar UPDATE historico: " . mysqli_error($conexion));
                }
                mysqli_stmt_bind_param($stH, 'ss', $diaUpdate, $diaBisiesto);
                mysqli_stmt_execute($stH);
                $resumen['actualizados_historico'] += (int)mysqli_stmt_affected_rows($stH);
                mysqli_stmt_close($stH);
            }

            if ($existeLotes) {
                $sqlL = "UPDATE {$tablaLotes} SET fecha_vencimiento = ? WHERE fecha_vencimiento = ?";
                $stL = mysqli_prepare($conexion, $sqlL);
                if (!$stL) {
                    throw new RuntimeException("No se pudo preparar UPDATE lotes: " . mysqli_error($conexion));
                }
                mysqli_stmt_bind_param($stL, 'ss', $diaUpdate, $diaBisiesto);
                mysqli_stmt_execute($stL);
                $resumen['actualizados_lotes'] += (int)mysqli_stmt_affected_rows($stL);
                mysqli_stmt_close($stL);
            }

            mysqli_commit($conexion);
        } catch (Throwable $e) {
            mysqli_rollback($conexion);
            $resumen['errores']++;
            fwrite(STDERR, "[vencimiento_29_febrero] Sucursal {$sucursalId}: {$e->getMessage()}\n");
        }
    }

    if ($stExists) {
        mysqli_stmt_close($stExists);
    }

    return $resumen;
}

