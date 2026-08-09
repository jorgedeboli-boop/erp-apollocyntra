<?php

/**
 * Libera lotes automáticamente según dias_liberacion de cada sucursal.
 */

/**
 * @param mysqli $conexion
 * @param array $sucursalesActivas
 * @return array
 */
function cron_lotes_liberados($conexion, $sucursalesActivas)
{
    cron_linea('>> Tarea: lotes_liberados');
    if (cron_solo_vista()) {
        cron_linea('  (modo solo vista: no se ejecutaran UPDATE ni INSERT)');
    }

    $resumen = array(
        'sucursales' => 0,
        'sucursales_con_dias' => 0,
        'lotes_a_liberar' => 0,
        'errores' => 0,
        'detalle' => array(),
    );

    foreach ($sucursalesActivas as $sucursal) {
        $resumen['sucursales']++;

        $idSucursal = isset($sucursal['id_sucursal']) ? (int) $sucursal['id_sucursal'] : 0;
        $nombreSucursal = isset($sucursal['nombre_sucursal']) ? $sucursal['nombre_sucursal'] : '';
        $diasLiberacion = isset($sucursal['dias_liberacion']) ? (int) $sucursal['dias_liberacion'] : 0;

        cron_linea(
            '  - Sucursal ' . $idSucursal . ' (' . $nombreSucursal . '): dias_liberacion = ' . $diasLiberacion
        );

        if ($idSucursal <= 0 || $diasLiberacion <= 0) {
            continue;
        }

        $resumen['sucursales_con_dias']++;

        $tablaLotes = cron_tabla_lotes_sucursal($idSucursal);
        if ($tablaLotes === false) {
            $resumen['errores']++;
            cron_linea('    ERROR: id de sucursal no valido para tabla de lotes.');
            continue;
        }

        $sqlLotes = "SELECT id_lote, fecha_compra, DATEDIFF(CURDATE(), fecha_compra) AS dias_desde_compra
                     FROM {$tablaLotes}
                     WHERE liberado = 'no' AND DATEDIFF(CURDATE(), fecha_compra) >= ?";

        $stmtLotes = mysqli_prepare($conexion, $sqlLotes);
        if (!$stmtLotes) {
            $resumen['errores']++;
            cron_linea('    Sin tabla o error en ' . $tablaLotes . ': ' . mysqli_error($conexion));
            continue;
        }

        mysqli_stmt_bind_param($stmtLotes, 'i', $diasLiberacion);

        if (!mysqli_stmt_execute($stmtLotes)) {
            $resumen['errores']++;
            cron_linea('    Error consultando ' . $tablaLotes . ': ' . mysqli_stmt_error($stmtLotes));
            mysqli_stmt_close($stmtLotes);
            continue;
        }

        $resultadoLotes = mysqli_stmt_get_result($stmtLotes);
        $lotesSucursal = array();

        $stmtUpdLote = null;
        $stmtUpdRel = null;

        if (!cron_solo_vista()) {
            $sqlUpdLote = "UPDATE {$tablaLotes} SET liberado = 'si', fecha_liberado = CURDATE() WHERE id_lote = ?";
            $stmtUpdLote = mysqli_prepare($conexion, $sqlUpdLote);

            $sqlUpdRel = "UPDATE rel_articulos_estados SET estado_articulo = 'Liberado' WHERE rel_id_lote = ? AND rel_id_sucursal = ?";
            $stmtUpdRel = mysqli_prepare($conexion, $sqlUpdRel);

            if (!$stmtUpdLote || !$stmtUpdRel) {
                $resumen['errores']++;
                cron_linea('    ERROR preparando UPDATE para ' . $tablaLotes . ': ' . mysqli_error($conexion));
                mysqli_stmt_close($stmtLotes);
                if ($stmtUpdLote) {
                    mysqli_stmt_close($stmtUpdLote);
                }
                if ($stmtUpdRel) {
                    mysqli_stmt_close($stmtUpdRel);
                }
                continue;
            }
        }

        $usuarioAccion = 1;
        $usuarioCron = '';

        while ($filaLote = $resultadoLotes ? mysqli_fetch_assoc($resultadoLotes) : null) {
            if (!$filaLote) {
                break;
            }

            $idLote = (int) $filaLote['id_lote'];
            if ($idLote <= 0) {
                continue;
            }

            if (!cron_solo_vista()) {
                mysqli_stmt_bind_param($stmtUpdLote, 'i', $idLote);
                if (!mysqli_stmt_execute($stmtUpdLote)) {
                    $resumen['errores']++;
                    cron_linea('      ERROR liberando lote ' . $idLote . ' en ' . $tablaLotes . ': ' . mysqli_stmt_error($stmtUpdLote));
                    continue;
                }

                mysqli_stmt_bind_param($stmtUpdRel, 'ii', $idLote, $idSucursal);
                if (!mysqli_stmt_execute($stmtUpdRel)) {
                    $resumen['errores']++;
                    cron_linea('      ERROR actualizando rel_articulos_estados lote ' . $idLote . ': ' . mysqli_stmt_error($stmtUpdRel));
                    continue;
                }

                $descripcionEvento = 'ACTUALIZAR lote liberado ' . $idLote . ' Sucursal ' . $idSucursal;
                registrar_tareas_cron($descripcionEvento);

                $descripcionCron = 'loteliberado por el cron Nº ' . $idLote . ' de la sucursal Nº ' . $idSucursal;
                $tipoDeOperacion = 'Loteliberado';
                insert_global_cron($descripcionCron, $idSucursal, $tipoDeOperacion, $usuarioCron);

                $accionTrazabilidad = 'liberado';
                $comentariosAccion = 'Lote ' . $idLote . ' liberado automaticamente';
                registrar_trazabilidad_lote_cron($idLote, $usuarioAccion, $accionTrazabilidad, $comentariosAccion, $idSucursal);
            }

            $lote = array(
                'id_lote' => $idLote,
                'fecha_compra' => (string) $filaLote['fecha_compra'],
                'dias_desde_compra' => (int) $filaLote['dias_desde_compra'],
            );

            $lotesSucursal[] = $lote;
            $resumen['lotes_a_liberar']++;

            $prefijoVista = cron_solo_vista() ? '[SIMULACION] ' : '';
            cron_linea(
                $prefijoVista .
                '      * Lote ' . $lote['id_lote'] .
                ' | compra=' . $lote['fecha_compra'] .
                ' | dias_desde_compra=' . $lote['dias_desde_compra'] .
                ' (>= ' . $diasLiberacion . ')'
            );
        }

        if ($stmtUpdLote) {
            mysqli_stmt_close($stmtUpdLote);
        }
        if ($stmtUpdRel) {
            mysqli_stmt_close($stmtUpdRel);
        }

        mysqli_stmt_close($stmtLotes);

        if (!$lotesSucursal) {
            cron_linea('    Sin lotes pendientes de liberar en ' . $tablaLotes . '.');
        }

        $resumen['detalle'][] = array(
            'id_sucursal' => $idSucursal,
            'nombre_sucursal' => $nombreSucursal,
            'dias_liberacion' => $diasLiberacion,
            'tabla' => $tablaLotes,
            'lotes' => $lotesSucursal,
        );
    }

    cron_linea(
        '  Resumen: sucursales=' . $resumen['sucursales'] .
        ', con dias_liberacion valido=' . $resumen['sucursales_con_dias'] .
        ', lotes_a_liberar=' . $resumen['lotes_a_liberar'] .
        ', errores=' . $resumen['errores']
    );

    return $resumen;
}
