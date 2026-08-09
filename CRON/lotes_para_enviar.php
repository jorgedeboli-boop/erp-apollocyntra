<?php

/**
 * Marca lotes liberados (compra) como pendientes de envío según la semana de envío.
 */

/**
 * @param mysqli $conexion
 * @param array $sucursalesActivas
 * @param array|null $numeroSemanaEnvio
 * @return array
 */
function cron_lotes_para_enviar($conexion, $sucursalesActivas, $numeroSemanaEnvio)
{
    cron_linea('>> Tarea: lotes_para_enviar');
    if (cron_solo_vista()) {
        cron_linea('  (modo solo vista: no se ejecutaran UPDATE ni INSERT)');
    }

    $resumen = array(
        'sucursales' => 0,
        'sucursales_procesadas' => 0,
        'lotes_para_enviar' => 0,
        'lotes_intervenidos' => 0,
        'errores' => 0,
        'detalle' => array(),
    );

    if (!is_array($numeroSemanaEnvio) || !isset($numeroSemanaEnvio['fecha_semana_hasta'])) {
        $resumen['errores']++;
        cron_linea('  ERROR: no se pudo obtener numeroSemanaEnvio (fecha_semana_hasta).');
        return $resumen;
    }

    $fechaSemanaHasta = (string) $numeroSemanaEnvio['fecha_semana_hasta'];
    $numeroSemana = isset($numeroSemanaEnvio['numero_semana']) ? (int) $numeroSemanaEnvio['numero_semana'] : 0;
    $anyoListado = isset($numeroSemanaEnvio['anyo_listado']) ? (int) $numeroSemanaEnvio['anyo_listado'] : 0;
    $usuarioAccion = 1;
    $usuarioCron = '';

    cron_linea(
        '  Semana envio: ' . $numeroSemana . ' (' . $anyoListado . ') | fecha_semana_hasta = ' . $fechaSemanaHasta
    );

    foreach ($sucursalesActivas as $sucursal) {
        $resumen['sucursales']++;

        $idSucursal = isset($sucursal['id_sucursal']) ? (int) $sucursal['id_sucursal'] : 0;
        $nombreSucursal = isset($sucursal['nombre_sucursal']) ? $sucursal['nombre_sucursal'] : '';

        cron_linea('  - Sucursal ' . $idSucursal . ' (' . $nombreSucursal . ')');

        if ($idSucursal <= 0) {
            continue;
        }

        $resumen['sucursales_procesadas']++;

        $tablaLotes = cron_tabla_lotes_sucursal($idSucursal);
        if ($tablaLotes === false) {
            $resumen['errores']++;
            cron_linea('    ERROR: id de sucursal no valido para tabla de lotes.');
            continue;
        }

        $sqlLotes = "SELECT id_lote, estado_lote
                     FROM {$tablaLotes}
                     WHERE liberado = 'si'
                       AND compra_opcion = 'no'
                       AND estado_envio = 'false'
                       AND envio_numero = 0
                       AND estado_lote = 'compra'
                       AND fecha_compra < ?";

        $stmtLotes = mysqli_prepare($conexion, $sqlLotes);
        if (!$stmtLotes) {
            $resumen['errores']++;
            cron_linea('    Sin tabla o error en ' . $tablaLotes . ': ' . mysqli_error($conexion));
            continue;
        }

        mysqli_stmt_bind_param($stmtLotes, 's', $fechaSemanaHasta);

        if (!mysqli_stmt_execute($stmtLotes)) {
            $resumen['errores']++;
            cron_linea('    Error consultando ' . $tablaLotes . ': ' . mysqli_stmt_error($stmtLotes));
            mysqli_stmt_close($stmtLotes);
            continue;
        }

        $resultadoLotes = mysqli_stmt_get_result($stmtLotes);
        $lotesSucursal = array();

        $stmtInsIntervenido = null;
        $stmtUpdLote = null;
        $stmtUpdRel = null;

        if (!cron_solo_vista()) {
            $sqlInsIntervenido = "INSERT INTO lotes_intervenidos_envios (
                id_lote_intervenido,
                id_sucursal_intervenido,
                fecha_creacion,
                estado_intervenido
            ) VALUES (?, ?, NOW(), 'pendiente_auditar')";
            $stmtInsIntervenido = mysqli_prepare($conexion, $sqlInsIntervenido);

            $sqlUpdLote = "UPDATE {$tablaLotes}
                           SET estado_envio = 'pendiente_enviar', envio_numero = 0
                           WHERE id_lote = ? AND estado_envio = 'false'";
            $stmtUpdLote = mysqli_prepare($conexion, $sqlUpdLote);

            $sqlUpdRel = "UPDATE rel_articulos_estados
                          SET estado_articulo = 'pendiente_enviar'
                          WHERE rel_id_lote = ? AND rel_id_sucursal = ?";
            $stmtUpdRel = mysqli_prepare($conexion, $sqlUpdRel);

            if (!$stmtInsIntervenido || !$stmtUpdLote || !$stmtUpdRel) {
                $resumen['errores']++;
                cron_linea('    ERROR preparando consultas para ' . $tablaLotes . ': ' . mysqli_error($conexion));
                mysqli_stmt_close($stmtLotes);
                if ($stmtInsIntervenido) {
                    mysqli_stmt_close($stmtInsIntervenido);
                }
                if ($stmtUpdLote) {
                    mysqli_stmt_close($stmtUpdLote);
                }
                if ($stmtUpdRel) {
                    mysqli_stmt_close($stmtUpdRel);
                }
                continue;
            }
        }

        while ($filaLote = $resultadoLotes ? mysqli_fetch_assoc($resultadoLotes) : null) {
            if (!$filaLote) {
                break;
            }

            $idLote = (int) $filaLote['id_lote'];
            $estadoLote = isset($filaLote['estado_lote']) ? (string) $filaLote['estado_lote'] : '';

            if ($idLote <= 0) {
                continue;
            }

            if ($estadoLote === 'intervenido') {
                if (!cron_solo_vista()) {
                    mysqli_stmt_bind_param($stmtInsIntervenido, 'ii', $idLote, $idSucursal);
                    if (!mysqli_stmt_execute($stmtInsIntervenido)) {
                        $resumen['errores']++;
                        cron_linea('      ERROR insertando lote intervenido ' . $idLote . ': ' . mysqli_stmt_error($stmtInsIntervenido));
                        continue;
                    }
                }

                $lotesSucursal[] = array(
                    'id_lote' => $idLote,
                    'estado_lote' => $estadoLote,
                    'accion' => 'intervenido_envio',
                );
                $resumen['lotes_intervenidos']++;

                $prefijoVista = cron_solo_vista() ? '[SIMULACION] ' : '';
                cron_linea($prefijoVista . '      * Lote ' . $idLote . ' | intervenido -> lotes_intervenidos_envios');
                continue;
            }

            if (!cron_solo_vista()) {
                mysqli_stmt_bind_param($stmtUpdLote, 'i', $idLote);
                if (!mysqli_stmt_execute($stmtUpdLote)) {
                    $resumen['errores']++;
                    cron_linea('      ERROR actualizando lote ' . $idLote . ' a pendiente_enviar: ' . mysqli_stmt_error($stmtUpdLote));
                    continue;
                }

                mysqli_stmt_bind_param($stmtUpdRel, 'ii', $idLote, $idSucursal);
                if (!mysqli_stmt_execute($stmtUpdRel)) {
                    $resumen['errores']++;
                    cron_linea('      ERROR actualizando rel_articulos_estados lote ' . $idLote . ': ' . mysqli_stmt_error($stmtUpdRel));
                    continue;
                }

                registrar_tareas_cron('ACTUALIZAR lote ' . $idLote . ' liberado listo para enviar Sucursal ' . $idSucursal);

                insert_global_cron(
                    'Lote listo para enviar por el cron Nº ' . $idLote . ' de la sucursal Nº ' . $idSucursal,
                    $idSucursal,
                    'Loteparaenviar',
                    $usuarioCron
                );

                registrar_trazabilidad_lote_cron(
                    $idLote,
                    $usuarioAccion,
                    'pendiente_enviar',
                    'Lote listo para enviar por el cron Nº ' . $idLote,
                    $idSucursal
                );
            }

            $lotesSucursal[] = array(
                'id_lote' => $idLote,
                'estado_lote' => $estadoLote,
                'accion' => 'pendiente_enviar',
            );
            $resumen['lotes_para_enviar']++;

            $prefijoVista = cron_solo_vista() ? '[SIMULACION] ' : '';
            cron_linea(
                $prefijoVista .
                '      * Lote ' . $idLote .
                ' | estado=' . $estadoLote .
                ' -> pendiente_enviar'
            );
        }

        if ($stmtInsIntervenido) {
            mysqli_stmt_close($stmtInsIntervenido);
        }
        if ($stmtUpdLote) {
            mysqli_stmt_close($stmtUpdLote);
        }
        if ($stmtUpdRel) {
            mysqli_stmt_close($stmtUpdRel);
        }
        mysqli_stmt_close($stmtLotes);

        if (!$lotesSucursal) {
            cron_linea('    Sin lotes liberados pendientes de envio en ' . $tablaLotes . '.');
        }

        $resumen['detalle'][] = array(
            'id_sucursal' => $idSucursal,
            'nombre_sucursal' => $nombreSucursal,
            'tabla_lotes' => $tablaLotes,
            'fecha_semana_hasta' => $fechaSemanaHasta,
            'lotes' => $lotesSucursal,
        );
    }

    cron_linea(
        '  Resumen: sucursales=' . $resumen['sucursales'] .
        ', procesadas=' . $resumen['sucursales_procesadas'] .
        ', lotes_para_enviar=' . $resumen['lotes_para_enviar'] .
        ', lotes_intervenidos=' . $resumen['lotes_intervenidos'] .
        ', errores=' . $resumen['errores']
    );

    return $resumen;
}
