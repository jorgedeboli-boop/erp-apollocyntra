<?php

/**
 * Ajusta vencimientos del 29 de febrero al 1 de marzo en años bisiestos.
 */

/**
 * @param mysqli $conexion
 * @param array $sucursalesActivas
 * @return array
 */
function cron_historico_empenos_vencidos_biciesto($conexion, $sucursalesActivas)
{
    cron_linea('>> Tarea: historico_empenos_vencidos_biciesto');
    if (cron_solo_vista()) {
        cron_linea('  (modo solo vista: no se ejecutaran UPDATE ni INSERT)');
    }

    $resumen = array(
        'sucursales' => 0,
        'sucursales_procesadas' => 0,
        'renovaciones_actualizadas' => 0,
        'lotes_actualizados' => 0,
        'errores' => 0,
        'detalle' => array(),
    );

    $anioActual = (int) date('Y');
    $diaBiciesto = $anioActual . '-02-29';
    $diaUpdate = $anioActual . '-03-01';

    cron_linea('  Fecha bisiesto: ' . $diaBiciesto . ' -> ' . $diaUpdate);

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

        $renovacionesSucursal = array();
        $lotesSucursal = array();

        $sqlRenovaciones = "SELECT id_renovaciones
                            FROM {$tablaHistorico}
                            WHERE proximo_vencimiento = ?";
        $stmtRenovaciones = mysqli_prepare($conexion, $sqlRenovaciones);

        if (!$stmtRenovaciones) {
            $resumen['errores']++;
            cron_linea('    Sin tabla o error en ' . $tablaHistorico . ': ' . mysqli_error($conexion));
            continue;
        }

        mysqli_stmt_bind_param($stmtRenovaciones, 's', $diaBiciesto);

        if (!mysqli_stmt_execute($stmtRenovaciones)) {
            $resumen['errores']++;
            cron_linea('    Error consultando ' . $tablaHistorico . ': ' . mysqli_stmt_error($stmtRenovaciones));
            mysqli_stmt_close($stmtRenovaciones);
            continue;
        }

        $resultadoRenovaciones = mysqli_stmt_get_result($stmtRenovaciones);

        $stmtUpdHistorico = null;
        if (!cron_solo_vista()) {
            $sqlUpdHistorico = "UPDATE {$tablaHistorico} SET proximo_vencimiento = ? WHERE id_renovaciones = ?";
            $stmtUpdHistorico = mysqli_prepare($conexion, $sqlUpdHistorico);
            if (!$stmtUpdHistorico) {
                $resumen['errores']++;
                cron_linea('    ERROR preparando UPDATE historico: ' . mysqli_error($conexion));
                mysqli_stmt_close($stmtRenovaciones);
                continue;
            }
        }

        while ($filaRenovacion = $resultadoRenovaciones ? mysqli_fetch_assoc($resultadoRenovaciones) : null) {
            if (!$filaRenovacion) {
                break;
            }

            $idRenovaciones = (int) $filaRenovacion['id_renovaciones'];
            if ($idRenovaciones <= 0) {
                continue;
            }

            if (!cron_solo_vista()) {
                mysqli_stmt_bind_param($stmtUpdHistorico, 'si', $diaUpdate, $idRenovaciones);
                if (!mysqli_stmt_execute($stmtUpdHistorico)) {
                    $resumen['errores']++;
                    cron_linea('      ERROR actualizando renovacion ' . $idRenovaciones . ': ' . mysqli_stmt_error($stmtUpdHistorico));
                    continue;
                }
            }

            $renovacionesSucursal[] = array('id_renovaciones' => $idRenovaciones);
            $resumen['renovaciones_actualizadas']++;

            $prefijoVista = cron_solo_vista() ? '[SIMULACION] ' : '';
            cron_linea($prefijoVista . '      * Renovacion ' . $idRenovaciones . ' | proximo_vencimiento -> ' . $diaUpdate);
        }

        if ($stmtUpdHistorico) {
            mysqli_stmt_close($stmtUpdHistorico);
        }
        mysqli_stmt_close($stmtRenovaciones);

        $sqlLotes = "SELECT id_lote FROM {$tablaLotes} WHERE fecha_vencimiento = ?";
        $stmtLotes = mysqli_prepare($conexion, $sqlLotes);

        if (!$stmtLotes) {
            $resumen['errores']++;
            cron_linea('    Sin tabla o error en ' . $tablaLotes . ': ' . mysqli_error($conexion));
            continue;
        }

        mysqli_stmt_bind_param($stmtLotes, 's', $diaBiciesto);

        if (!mysqli_stmt_execute($stmtLotes)) {
            $resumen['errores']++;
            cron_linea('    Error consultando ' . $tablaLotes . ': ' . mysqli_stmt_error($stmtLotes));
            mysqli_stmt_close($stmtLotes);
            continue;
        }

        $resultadoLotes = mysqli_stmt_get_result($stmtLotes);

        $stmtUpdLote = null;
        if (!cron_solo_vista()) {
            $sqlUpdLote = "UPDATE {$tablaLotes} SET fecha_vencimiento = ? WHERE id_lote = ?";
            $stmtUpdLote = mysqli_prepare($conexion, $sqlUpdLote);
            if (!$stmtUpdLote) {
                $resumen['errores']++;
                cron_linea('    ERROR preparando UPDATE lotes: ' . mysqli_error($conexion));
                mysqli_stmt_close($stmtLotes);
                continue;
            }
        }

        while ($filaLote = $resultadoLotes ? mysqli_fetch_assoc($resultadoLotes) : null) {
            if (!$filaLote) {
                break;
            }

            $idLote = (int) $filaLote['id_lote'];
            if ($idLote <= 0) {
                continue;
            }

            if (!cron_solo_vista()) {
                mysqli_stmt_bind_param($stmtUpdLote, 'si', $diaUpdate, $idLote);
                if (!mysqli_stmt_execute($stmtUpdLote)) {
                    $resumen['errores']++;
                    cron_linea('      ERROR actualizando lote ' . $idLote . ': ' . mysqli_stmt_error($stmtUpdLote));
                    continue;
                }
            }

            $lotesSucursal[] = array('id_lote' => $idLote);
            $resumen['lotes_actualizados']++;

            $prefijoVista = cron_solo_vista() ? '[SIMULACION] ' : '';
            cron_linea($prefijoVista . '      * Lote ' . $idLote . ' | fecha_vencimiento -> ' . $diaUpdate);
        }

        if ($stmtUpdLote) {
            mysqli_stmt_close($stmtUpdLote);
        }
        mysqli_stmt_close($stmtLotes);

        if (!$renovacionesSucursal && !$lotesSucursal) {
            cron_linea('    Sin registros con fecha ' . $diaBiciesto . ' en ' . $tablaHistorico . ' ni ' . $tablaLotes . '.');
        }

        $resumen['detalle'][] = array(
            'id_sucursal' => $idSucursal,
            'nombre_sucursal' => $nombreSucursal,
            'dia_biciesto' => $diaBiciesto,
            'dia_update' => $diaUpdate,
            'renovaciones' => $renovacionesSucursal,
            'lotes' => $lotesSucursal,
        );
    }

    cron_linea(
        '  Resumen: sucursales=' . $resumen['sucursales'] .
        ', procesadas=' . $resumen['sucursales_procesadas'] .
        ', renovaciones_actualizadas=' . $resumen['renovaciones_actualizadas'] .
        ', lotes_actualizados=' . $resumen['lotes_actualizados'] .
        ', errores=' . $resumen['errores']
    );

    return $resumen;
}
