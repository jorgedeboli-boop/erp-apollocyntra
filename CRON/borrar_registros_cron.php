<?php

/**
 * Elimina registros de tablas de cron anteriores a 3 meses.
 */

/**
 * @param mysqli $conexion
 * @return array
 */
function cron_borrar_registros_cron($conexion)
{
    cron_linea('>> Tarea: borrar_registros_cron');
    if (cron_solo_vista()) {
        cron_linea('  (modo solo vista: no se ejecutaran DELETE)');
    }

    $resumen = array(
        'tablas' => 0,
        'registros_borrados' => 0,
        'errores' => 0,
        'detalle' => array(),
    );

    $tablas = array(
        array(
            'tabla' => 'tareas_cron',
            'columna' => 'fecha',
            'condicion' => 'fecha < DATE_SUB(NOW(), INTERVAL 3 MONTH)',
        ),
        array(
            'tabla' => 'test_cron',
            'columna' => 'hora_insert',
            'condicion' => 'hora_insert < DATE_SUB(NOW(), INTERVAL 3 MONTH)',
        ),
        array(
            'tabla' => 'acciones_cron',
            'columna' => 'fecha',
            'condicion' => 'fecha < DATE_SUB(NOW(), INTERVAL 3 MONTH)',
        ),
        array(
            'tabla' => 'acciones_historico_renovaciones',
            'columna' => 'fecha_accion',
            'condicion' => 'fecha_accion < DATE_SUB(NOW(), INTERVAL 3 MONTH)',
        ),
        array(
            'tabla' => 'control_cron',
            'columna' => 'fecha_cron',
            'condicion' => 'fecha_cron < DATE_SUB(CURDATE(), INTERVAL 3 MONTH)',
        ),
    );

    foreach ($tablas as $config) {
        $resumen['tablas']++;
        $tabla = $config['tabla'];
        $columna = $config['columna'];
        $condicion = $config['condicion'];

        $sqlCount = "SELECT COUNT(*) AS total FROM {$tabla} WHERE {$condicion}";
        $resultadoCount = mysqli_query($conexion, $sqlCount);

        if (!$resultadoCount) {
            $resumen['errores']++;
            cron_linea('    ERROR contando ' . $tabla . ': ' . mysqli_error($conexion));
            continue;
        }

        $filaCount = mysqli_fetch_assoc($resultadoCount);
        mysqli_free_result($resultadoCount);
        $totalPendiente = $filaCount ? (int) $filaCount['total'] : 0;

        $borrados = 0;

        if ($totalPendiente > 0 && !cron_solo_vista()) {
            $sqlDelete = "DELETE FROM {$tabla} WHERE {$condicion}";
            if (!mysqli_query($conexion, $sqlDelete)) {
                $resumen['errores']++;
                cron_linea('    ERROR borrando en ' . $tabla . ': ' . mysqli_error($conexion));
                continue;
            }

            $borrados = (int) mysqli_affected_rows($conexion);
        } elseif ($totalPendiente > 0) {
            $borrados = $totalPendiente;
        }

        $resumen['registros_borrados'] += $borrados;

        $prefijoVista = cron_solo_vista() ? '[SIMULACION] ' : '';
        cron_linea(
            $prefijoVista .
            '  - ' . $tabla . ' (' . $columna . '): ' .
            $borrados . ' registro(s) anteriores a 3 meses'
        );

        $resumen['detalle'][] = array(
            'tabla' => $tabla,
            'columna' => $columna,
            'registros' => $borrados,
        );
    }

    cron_linea(
        '  Resumen: tablas=' . $resumen['tablas'] .
        ', registros_borrados=' . $resumen['registros_borrados'] .
        ', errores=' . $resumen['errores']
    );

    return $resumen;
}
