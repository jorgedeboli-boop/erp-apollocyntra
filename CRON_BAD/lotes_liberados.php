<?php
declare(strict_types=1);

/**
 * Libera lotes automáticamente por sucursal, según `sucursal.dias_liberacion`.
 *
 * Requisitos:
 * - Tablas por sucursal: lotes_{id_sucursal}
 * - Campos mínimos: lotes_{id}.id_lote, lotes_{id}.fecha_compra, lotes_{id}.liberado, lotes_{id}.fecha_liberado
 * - Tablas globales: rel_articulos_estados, tareas_cron, trazabilidad_lotes
 */

function cron_lotes_liberados(mysqli $conexion, bool $soloSimulacion = false): array
{
    $resumen = [
        'sucursales' => 0,
        'lotes_liberados' => 0,
        'errores' => 0,
        'solo_simulacion' => $soloSimulacion,
        'detalle' => [],
    ];

    $qSuc = "SELECT id_sucursal, dias_liberacion FROM sucursal";
    $rSuc = mysqli_query($conexion, $qSuc);
    if (!$rSuc) {
        throw new RuntimeException("Error consultando sucursales: " . mysqli_error($conexion));
    }

    while ($s = mysqli_fetch_assoc($rSuc)) {
        $resumen['sucursales']++;

        $sucursalId = (int)($s['id_sucursal'] ?? 0);
        $diasLiberacion = (int)($s['dias_liberacion'] ?? 0);

        if ($sucursalId <= 0 || $diasLiberacion <= 0) {
            continue;
        }

        // Tabla dinámica por sucursal: solo permitimos dígitos
        $tabla = 'lotes_' . $sucursalId;
        if (!preg_match('/^lotes_\\d+$/', $tabla)) {
            $resumen['errores']++;
            continue;
        }

        // Selecciona los lotes aún no liberados que ya cumplieron el plazo.
        $qLotes = "SELECT id_lote, fecha_compra, DATEDIFF(CURDATE(), fecha_compra) AS dias_desde_compra
                   FROM {$tabla}
                   WHERE liberado = 'no' AND DATEDIFF(CURDATE(), fecha_compra) >= ?";
        $stLotes = mysqli_prepare($conexion, $qLotes);
        if (!$stLotes) {
            $resumen['errores']++;
            continue;
        }

        mysqli_stmt_bind_param($stLotes, 'i', $diasLiberacion);
        if (!mysqli_stmt_execute($stLotes)) {
            $resumen['errores']++;
            mysqli_stmt_close($stLotes);
            continue;
        }

        $rLotes = mysqli_stmt_get_result($stLotes);
        $lotes = [];
        while ($row = $rLotes ? mysqli_fetch_assoc($rLotes) : null) {
            if (!$row) {
                break;
            }
            $lotes[] = [
                'id_lote' => (int) $row['id_lote'],
                'fecha_compra' => (string) ($row['fecha_compra'] ?? ''),
                'dias_desde_compra' => (int) ($row['dias_desde_compra'] ?? 0),
            ];
        }
        mysqli_stmt_close($stLotes);

        if (!$lotes) {
            continue;
        }

        if ($soloSimulacion) {
            $resumen['detalle'][] = [
                'sucursal_id' => $sucursalId,
                'dias_liberacion' => $diasLiberacion,
                'tabla' => $tabla,
                'lotes' => $lotes,
            ];
            $resumen['lotes_liberados'] += count($lotes);
            continue;
        }

        $lotesIds = array_map(static function (array $lote): int {
            return (int) $lote['id_lote'];
        }, $lotes);

        mysqli_begin_transaction($conexion);
        try {
            // Preparar statements (reutilizables) para esta sucursal.
            $qUpdLote = "UPDATE {$tabla} SET liberado = 'si', fecha_liberado = CURDATE() WHERE id_lote = ? AND liberado = 'no'";
            $stUpdLote = mysqli_prepare($conexion, $qUpdLote);
            if (!$stUpdLote) {
                throw new RuntimeException("Error preparando UPDATE lotes: " . mysqli_error($conexion));
            }

            $qUpdArt = "UPDATE rel_articulos_estados SET estado_articulo = 'Liberado' WHERE rel_id_lote = ? AND rel_id_sucursal = ?";
            $stUpdArt = mysqli_prepare($conexion, $qUpdArt);
            if (!$stUpdArt) {
                throw new RuntimeException("Error preparando UPDATE rel_articulos_estados: " . mysqli_error($conexion));
            }

            $qTarea = "INSERT INTO tareas_cron (descripcion_evento, fecha) VALUES (?, NOW())";
            $stTarea = mysqli_prepare($conexion, $qTarea);
            if (!$stTarea) {
                throw new RuntimeException("Error preparando INSERT tareas_cron: " . mysqli_error($conexion));
            }

            $qTraz = "INSERT INTO trazabilidad_lotes (
                id_lote,
                fecha_accion,
                usuario_accion,
                accion_trazabilidad,
                comentarios_accion,
                sucursal_accion
            ) VALUES (?, NOW(), '1', ?, ?, ?)";
            $stTraz = mysqli_prepare($conexion, $qTraz);
            if (!$stTraz) {
                throw new RuntimeException("Error preparando INSERT trazabilidad_lotes: " . mysqli_error($conexion));
            }

            $accion = 'liberado';
            $comentario = 'Lote liberado automaticamente';

            foreach ($lotesIds as $loteId) {
                if ($loteId <= 0) {
                    continue;
                }

                // Control global (si existe en el proyecto)
                if (function_exists('insert_global_cron')) {
                    $descripcionCron = "Lote liberado por el cron Nº {$loteId} de la sucursal Nº {$sucursalId}";
                    $tipoOperacion = "Loteliberado";
                    insert_global_cron($conexion, $descripcionCron, $sucursalId, $tipoOperacion);
                }

                // UPDATE lotes_{sucursal}
                mysqli_stmt_bind_param($stUpdLote, 'i', $loteId);
                if (!mysqli_stmt_execute($stUpdLote)) {
                    throw new RuntimeException("Error liberando lote {$loteId} en {$tabla}: " . mysqli_stmt_error($stUpdLote));
                }

                // UPDATE rel_articulos_estados
                mysqli_stmt_bind_param($stUpdArt, 'ii', $loteId, $sucursalId);
                if (!mysqli_stmt_execute($stUpdArt)) {
                    throw new RuntimeException("Error actualizando rel_articulos_estados (lote {$loteId}): " . mysqli_stmt_error($stUpdArt));
                }

                // INSERT tareas_cron (detalle)
                $descEvento = "UPDATE lote liberado {$loteId} Sucursal {$sucursalId}";
                mysqli_stmt_bind_param($stTarea, 's', $descEvento);
                if (!mysqli_stmt_execute($stTarea)) {
                    throw new RuntimeException("Error insertando tareas_cron (lote {$loteId}): " . mysqli_stmt_error($stTarea));
                }

                // INSERT trazabilidad_lotes
                mysqli_stmt_bind_param($stTraz, 'issi', $loteId, $accion, $comentario, $sucursalId);
                if (!mysqli_stmt_execute($stTraz)) {
                    throw new RuntimeException("Error insertando trazabilidad (lote {$loteId}): " . mysqli_stmt_error($stTraz));
                }

                $resumen['lotes_liberados']++;
            }

            mysqli_stmt_close($stUpdLote);
            mysqli_stmt_close($stUpdArt);
            mysqli_stmt_close($stTarea);
            mysqli_stmt_close($stTraz);

            mysqli_commit($conexion);
        } catch (Throwable $e) {
            mysqli_rollback($conexion);
            $resumen['errores']++;
            // Seguimos con la siguiente sucursal, pero dejamos rastro en STDERR para el log del cron.
            fwrite(STDERR, "[lotes_liberados] Sucursal {$sucursalId}: {$e->getMessage()}\n");
        }
    }

    return $resumen;
}

