<?php

/**
 * Envia SMS de recordatorio de vencimiento de empeño (1 dia antes).
 */

/**
 * @param mysqli $conexion
 * @param array $sucursalesSmsEmpeno
 * @return array
 */
function cron_enviar_sms_empeno_por_vencer($conexion, $sucursalesSmsEmpeno)
{
    cron_linea('>> Tarea: enviar_sms_empeno_por_vencer');
    if (cron_solo_vista()) {
        cron_linea('  (modo solo vista: no se ejecutaran INSERT ni envio SMS)');
    }

    $resumen = array(
        'sucursales' => 0,
        'renovaciones_consultadas' => 0,
        'sms_enviados' => 0,
        'sms_omitidos' => 0,
        'errores' => 0,
        'detalle' => array(),
    );

    $usuarioCron = '';
    $conexionMatermedia = null;

    if (!cron_solo_vista()) {
        $conexionMatermedia = cron_conectar_matermedia_sms();
        if (!$conexionMatermedia) {
            $resumen['errores']++;
            cron_linea('  ERROR: no se pudo conectar a matermedia SMS.');
            return $resumen;
        }
    }

    $estadosLoteOmitidos = array('compra', 'retirado', 'intervenido', 'perdido');

    foreach ($sucursalesSmsEmpeno as $sucursal) {
        $resumen['sucursales']++;

        $idSucursal = isset($sucursal['id_sucursal']) ? (int) $sucursal['id_sucursal'] : 0;
        $nombreSucursal = isset($sucursal['nombre_sucursal']) ? $sucursal['nombre_sucursal'] : '';

        cron_linea('  - Sucursal ' . $idSucursal . ' (' . $nombreSucursal . ')');

        if ($idSucursal <= 0) {
            continue;
        }

        $tablaHistorico = cron_tabla_historico_renovaciones_sucursal($idSucursal);
        $tablaLotes = cron_tabla_lotes_sucursal($idSucursal);

        if ($tablaHistorico === false || $tablaLotes === false) {
            $resumen['errores']++;
            cron_linea('    ERROR: id de sucursal no valido para tablas de historico o lotes.');
            continue;
        }

        $sqlRenovaciones = "SELECT id_renovaciones, importe_renovacion, lote, proximo_vencimiento
                            FROM {$tablaHistorico}
                            WHERE estado_historico = 'enfecha'
                              AND proximo_vencimiento = DATE_ADD(CURDATE(), INTERVAL 1 DAY)";
        $resultadoRenovaciones = mysqli_query($conexion, $sqlRenovaciones);

        if (!$resultadoRenovaciones) {
            $resumen['errores']++;
            cron_linea('    Sin tabla o error en ' . $tablaHistorico . ': ' . mysqli_error($conexion));
            continue;
        }

        $renovacionesSucursal = array();

        $sqlLote = "SELECT l.id_lote, l.estado_lote, l.cliente, c.nombre, c.apellido, c.telefono
                    FROM {$tablaLotes} l
                    LEFT JOIN clientes c ON l.cliente = c.id_cliente
                    WHERE l.id_lote = ?";
        $stmtLote = mysqli_prepare($conexion, $sqlLote);

        $stmtSmsMatermedia = null;
        $stmtSmsSend = null;

        if (!cron_solo_vista() && $conexionMatermedia) {
            $sqlSmsMatermedia = "INSERT INTO send_sms_clientes (
                mensaje_envio,
                telefono_destino,
                estado_envio,
                fecha_envio
            ) VALUES (?, ?, 'pendiente', NOW())";
            $stmtSmsMatermedia = mysqli_prepare($conexionMatermedia, $sqlSmsMatermedia);

            $sqlSmsSend = "INSERT INTO sms_send (
                cliente_sms,
                movil_sms,
                type_item_sms,
                estado_sms,
                mensaje_sms,
                rel_item_sms,
                usuario_sms,
                surusal_sms,
                fecha_sms
            ) VALUES (?, ?, 'vencimiento', 'true', ?, ?, 1, ?, NOW())";
            $stmtSmsSend = mysqli_prepare($conexion, $sqlSmsSend);
        }

        if (!$stmtLote || (!cron_solo_vista() && (!$stmtSmsMatermedia || !$stmtSmsSend))) {
            $resumen['errores']++;
            cron_linea('    ERROR preparando consultas SMS para ' . $tablaHistorico . '.');
            if ($resultadoRenovaciones) {
                mysqli_free_result($resultadoRenovaciones);
            }
            if ($stmtLote) {
                mysqli_stmt_close($stmtLote);
            }
            if ($stmtSmsMatermedia) {
                mysqli_stmt_close($stmtSmsMatermedia);
            }
            if ($stmtSmsSend) {
                mysqli_stmt_close($stmtSmsSend);
            }
            continue;
        }

        while ($filaRenovacion = mysqli_fetch_assoc($resultadoRenovaciones)) {
            $resumen['renovaciones_consultadas']++;

            $idRenovaciones = isset($filaRenovacion['id_renovaciones']) ? (int) $filaRenovacion['id_renovaciones'] : 0;
            $idLote = isset($filaRenovacion['lote']) ? (int) $filaRenovacion['lote'] : 0;
            $vencimiento = isset($filaRenovacion['proximo_vencimiento']) ? (string) $filaRenovacion['proximo_vencimiento'] : '';
            $vencimientoParse = $vencimiento !== '' ? date('d-m-Y', strtotime($vencimiento)) : '';

            if ($idLote <= 0) {
                continue;
            }

            mysqli_stmt_bind_param($stmtLote, 'i', $idLote);
            if (!mysqli_stmt_execute($stmtLote)) {
                $resumen['errores']++;
                cron_linea('      ERROR consultando lote ' . $idLote . ': ' . mysqli_stmt_error($stmtLote));
                continue;
            }

            $resultadoLote = mysqli_stmt_get_result($stmtLote);
            $filaLote = $resultadoLote ? mysqli_fetch_assoc($resultadoLote) : null;

            if (!$filaLote) {
                $resumen['sms_omitidos']++;
                continue;
            }

            $telefonoDestino = isset($filaLote['telefono']) ? trim((string) $filaLote['telefono']) : '';
            $estadoLote = isset($filaLote['estado_lote']) ? (string) $filaLote['estado_lote'] : '';
            $cliente = isset($filaLote['cliente']) ? (int) $filaLote['cliente'] : 0;
            $nombre = trim(
                (isset($filaLote['nombre']) ? (string) $filaLote['nombre'] : '') . ' ' .
                (isset($filaLote['apellido']) ? (string) $filaLote['apellido'] : '')
            );

            cron_linea(
                '      * Lote ' . $idLote .
                ' | cliente: ' . $nombre .
                ' | sucursal: ' . $idSucursal .
                ' | vencimiento: ' . $vencimientoParse
            );

            if ($telefonoDestino === '') {
                $resumen['sms_omitidos']++;
                continue;
            }

            if (in_array($estadoLote, $estadosLoteOmitidos, true)) {
                $resumen['sms_omitidos']++;
                cron_linea('        Omitido: estado_lote=' . $estadoLote);
                continue;
            }

            $mensajeEnvio = 'Estimado cliente, Le recordamos que mañana ' . $vencimientoParse .
                ' vence el plazo de pago del contrato de opcion de recompra numero ' .
                $idLote . ' - ' . $idSucursal;

            if (!cron_solo_vista()) {
                insert_global_cron(
                    'envia SMS de vencimiento de empeno Nº ' . $idLote . ' de la sucursal Nº ' . $idSucursal,
                    $idSucursal,
                    'SMSvencimientoemp',
                    $usuarioCron
                );

                mysqli_stmt_bind_param($stmtSmsMatermedia, 'ss', $mensajeEnvio, $telefonoDestino);
                if (!mysqli_stmt_execute($stmtSmsMatermedia)) {
                    $resumen['errores']++;
                    cron_linea('        ERROR insertando send_sms_clientes: ' . mysqli_stmt_error($stmtSmsMatermedia));
                    continue;
                }

                mysqli_stmt_bind_param(
                    $stmtSmsSend,
                    'issii',
                    $cliente,
                    $telefonoDestino,
                    $mensajeEnvio,
                    $idLote,
                    $idSucursal
                );

                if (!mysqli_stmt_execute($stmtSmsSend)) {
                    $resumen['errores']++;
                    cron_linea('        ERROR insertando sms_send: ' . mysqli_stmt_error($stmtSmsSend));
                    continue;
                }

                registrar_tareas_cron('INSERT INTO sms_send del lote: ' . $idLote . ' Sucursal ' . $idSucursal);
            }

            $resumen['sms_enviados']++;
            $prefijoVista = cron_solo_vista() ? '[SIMULACION] ' : '';
            cron_linea($prefijoVista . '        SMS programado -> ' . $telefonoDestino);

            $renovacionesSucursal[] = array(
                'id_renovaciones' => $idRenovaciones,
                'id_lote' => $idLote,
                'telefono' => $telefonoDestino,
                'vencimiento' => $vencimiento,
            );
        }

        mysqli_free_result($resultadoRenovaciones);
        mysqli_stmt_close($stmtLote);
        if ($stmtSmsMatermedia) {
            mysqli_stmt_close($stmtSmsMatermedia);
        }
        if ($stmtSmsSend) {
            mysqli_stmt_close($stmtSmsSend);
        }

        if (!$renovacionesSucursal) {
            cron_linea('    Sin renovaciones que venzan manana en ' . $tablaHistorico . '.');
        }

        $resumen['detalle'][] = array(
            'id_sucursal' => $idSucursal,
            'nombre_sucursal' => $nombreSucursal,
            'renovaciones' => $renovacionesSucursal,
        );
    }

    if ($conexionMatermedia) {
        mysqli_close($conexionMatermedia);
    }

    cron_linea(
        '  Resumen: sucursales=' . $resumen['sucursales'] .
        ', renovaciones_consultadas=' . $resumen['renovaciones_consultadas'] .
        ', sms_enviados=' . $resumen['sms_enviados'] .
        ', sms_omitidos=' . $resumen['sms_omitidos'] .
        ', errores=' . $resumen['errores']
    );

    return $resumen;
}
