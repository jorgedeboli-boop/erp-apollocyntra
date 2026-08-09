<?php
declare(strict_types=1);

/**
 * Apertura/cierre automático de caja (solo para sucursales habilitadas y new_sitema_caja='true').
 *
 * Lógica (adaptada del legacy):
 * - Obtiene último "CAJA INICIO" (cierre_caja='false')
 * - Obtiene último "CAJA FINAL" (cierre_caja='true')
 * - Si la fecha del último inicio == fecha del último cierre:
 *     - Inserta una nueva apertura con el importe del último cierre
 * - Si no coincide:
 *     - Cuenta movimientos en la fecha del último inicio excluyendo el propio apunte de inicio
 *     - Si hay movimientos:
 *         - Marca sucursal.caja_cerrada='false' (caja no cerrada)
 *     - Si NO hay movimientos:
 *         - Inserta cierre automático con el importe del inicio
 *         - Marca sucursal.caja_cerrada='true'
 *         - Inserta apertura nueva con el importe del último cierre (fallback: importe inicio)
 */

function cron_apertura_de_caja(mysqli $conexion): array
{
    $resumen = [
        'sucursales' => 0,
        'sucursales_validas' => 0,
        'tablas_movimientos_inexistentes' => 0,
        'aperturas' => 0,
        'cierres' => 0,
        'cajas_no_cerradas' => 0,
        'errores' => 0,
    ];

    @mysqli_set_charset($conexion, 'utf8');

    $rSuc = mysqli_query(
        $conexion,
        "SELECT id_sucursal, new_sitema_caja
         FROM sucursal
         WHERE estado_tienda LIKE 'habilitada'"
    );
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
        $newSistema = (string)($s['new_sitema_caja'] ?? '');

        if ($sucursalId <= 0 || $newSistema !== 'true') {
            continue;
        }

        $tablaMov = 'movimientos_de_caja_' . $sucursalId;
        if (!preg_match('/^movimientos_de_caja_\\d+$/', $tablaMov)) {
            $resumen['errores']++;
            fwrite(STDERR, "[apertura_de_caja] Sucursal {$sucursalId}: nombre de tabla no permitido\n");
            continue;
        }

        $resumen['sucursales_validas']++;

        // Verificar existencia de tabla movimientos_de_caja_{sucursal}
        $existeTabla = true;
        if ($stExists) {
            mysqli_stmt_bind_param($stExists, 's', $tablaMov);
            mysqli_stmt_execute($stExists);
            $rt = mysqli_stmt_get_result($stExists);
            $existeTabla = $rt && mysqli_num_rows($rt) > 0;
        }
        if (!$existeTabla) {
            $resumen['tablas_movimientos_inexistentes']++;
            fwrite(STDERR, "[apertura_de_caja] Sucursal {$sucursalId}: no existe {$tablaMov}\n");
            continue;
        }

        mysqli_begin_transaction($conexion);
        try {
            // Último inicio
            $sqlInicio = "SELECT id_movimientos, fecha_apunte, entrada
                          FROM {$tablaMov}
                          WHERE cierre_caja = 'false' AND grupos = 'CAJA INICIO'
                          ORDER BY id_movimientos DESC
                          LIMIT 1";
            $rInicio = mysqli_query($conexion, $sqlInicio);
            $inicio = $rInicio ? mysqli_fetch_assoc($rInicio) : null;

            if (!$inicio) {
                // Sin inicio no podemos inferir nada (no creamos registros a ciegas)
                mysqli_commit($conexion);
                continue;
            }

            $fechaInicio = (string)($inicio['fecha_apunte'] ?? '');
            $idInicio = (int)($inicio['id_movimientos'] ?? 0);
            $totalInicio = (float)($inicio['entrada'] ?? 0);

            // Último cierre
            $sqlCierre = "SELECT salida, fecha_apunte
                          FROM {$tablaMov}
                          WHERE cierre_caja = 'true' AND grupos = 'CAJA FINAL'
                          ORDER BY id_movimientos DESC
                          LIMIT 1";
            $rCierre = mysqli_query($conexion, $sqlCierre);
            $cierre = $rCierre ? mysqli_fetch_assoc($rCierre) : null;

            $apunteCierre = $cierre ? (float)($cierre['salida'] ?? 0) : 0.0;
            $fechaCierre = $cierre ? (string)($cierre['fecha_apunte'] ?? '') : '';

            // Fallback: si no hay cierre todavía, usamos el total de inicio
            if ($apunteCierre <= 0) {
                $apunteCierre = $totalInicio;
            }

            $hoy = date('Y-m-d');

            // Helpers para INSERT apertura/cierre
            $stInsertApertura = mysqli_prepare(
                $conexion,
                "INSERT INTO {$tablaMov} (
                    cierre_caja, usuario, grupos, concepto, entrada, fecha_apunte, hora_de_apunte
                 ) VALUES ('false', 'cron', 'CAJA INICIO', ?, ?, NOW(), NOW())"
            );
            if (!$stInsertApertura) {
                throw new RuntimeException("No se pudo preparar INSERT apertura: " . mysqli_error($conexion));
            }

            $stInsertCierre = mysqli_prepare(
                $conexion,
                "INSERT INTO {$tablaMov} (
                    cierre_caja, fecha_apunte, grupos, salida, usuario
                 ) VALUES ('true', ?, 'CAJA FINAL', ?, 'cron')"
            );
            if (!$stInsertCierre) {
                throw new RuntimeException("No se pudo preparar INSERT cierre: " . mysqli_error($conexion));
            }

            $stUpdSucursalCaja = mysqli_prepare(
                $conexion,
                "UPDATE sucursal SET caja_cerrada = ? WHERE id_sucursal = ?"
            );
            if (!$stUpdSucursalCaja) {
                throw new RuntimeException("No se pudo preparar UPDATE sucursal: " . mysqli_error($conexion));
            }

            if ($fechaInicio !== '' && $fechaCierre !== '' && $fechaInicio === $fechaCierre) {
                // Apertura automática
                $concepto = "Apertura de caja del {$hoy}";
                mysqli_stmt_bind_param($stInsertApertura, 'sd', $concepto, $apunteCierre);
                mysqli_stmt_execute($stInsertApertura);
                $resumen['aperturas']++;

                if (function_exists('insert_global_cron')) {
                    insert_global_cron($conexion, "Apertura cron de caja Sucursal Nº {$sucursalId}", $sucursalId, "Aperturacaja");
                }

                mysqli_stmt_close($stInsertApertura);
                mysqli_stmt_close($stInsertCierre);
                mysqli_stmt_close($stUpdSucursalCaja);
                mysqli_commit($conexion);
                continue;
            }

            // Contar movimientos en fecha del inicio excluyendo el propio apunte inicio
            $stCount = mysqli_prepare(
                $conexion,
                "SELECT COUNT(id_movimientos) AS total
                 FROM {$tablaMov}
                 WHERE fecha_apunte = ?
                   AND cierre_caja = 'false'
                   AND id_movimientos != ?"
            );
            if (!$stCount) {
                throw new RuntimeException("No se pudo preparar COUNT movimientos: " . mysqli_error($conexion));
            }
            mysqli_stmt_bind_param($stCount, 'si', $fechaInicio, $idInicio);
            mysqli_stmt_execute($stCount);
            $rCount = mysqli_stmt_get_result($stCount);
            $rowCount = $rCount ? mysqli_fetch_assoc($rCount) : null;
            $totalMov = $rowCount ? (int)$rowCount['total'] : 0;
            mysqli_stmt_close($stCount);

            if ($totalMov > 0) {
                // Caja no cerrada: marcar sucursal caja_cerrada=false
                $flag = 'false';
                mysqli_stmt_bind_param($stUpdSucursalCaja, 'si', $flag, $sucursalId);
                mysqli_stmt_execute($stUpdSucursalCaja);
                $resumen['cajas_no_cerradas']++;

                if (function_exists('insert_global_cron')) {
                    insert_global_cron($conexion, "Caja no cerrada Sucursal Nº {$sucursalId}", $sucursalId, "Cajanocerrada");
                }
            } else {
                // Sin movimientos: cerrar caja con el total de inicio y reabrir
                mysqli_stmt_bind_param($stInsertCierre, 'sd', $fechaInicio, $totalInicio);
                mysqli_stmt_execute($stInsertCierre);
                $resumen['cierres']++;

                if (function_exists('insert_global_cron')) {
                    insert_global_cron(
                        $conexion,
                        "Cierre cron de caja Sucursal Nº {$sucursalId} no se registraron movimientos.",
                        $sucursalId,
                        "Cajacerrada"
                    );
                }

                $flag = 'true';
                mysqli_stmt_bind_param($stUpdSucursalCaja, 'si', $flag, $sucursalId);
                mysqli_stmt_execute($stUpdSucursalCaja);

                $concepto = "Apertura de caja del {$hoy}";
                mysqli_stmt_bind_param($stInsertApertura, 'sd', $concepto, $apunteCierre);
                mysqli_stmt_execute($stInsertApertura);
                $resumen['aperturas']++;

                if (function_exists('insert_global_cron')) {
                    insert_global_cron(
                        $conexion,
                        "Apertura cron de caja Sucursal Nº {$sucursalId} no se registraron movimientos.",
                        $sucursalId,
                        "Aperturacaja"
                    );
                }
            }

            mysqli_stmt_close($stInsertApertura);
            mysqli_stmt_close($stInsertCierre);
            mysqli_stmt_close($stUpdSucursalCaja);
            mysqli_commit($conexion);
        } catch (Throwable $e) {
            mysqli_rollback($conexion);
            $resumen['errores']++;
            fwrite(STDERR, "[apertura_de_caja] Sucursal {$sucursalId}: {$e->getMessage()}\n");
        }
    }

    if ($stExists) {
        mysqli_stmt_close($stExists);
    }

    return $resumen;
}

