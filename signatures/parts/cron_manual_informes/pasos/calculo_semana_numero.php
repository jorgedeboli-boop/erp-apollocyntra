<?php

/**
 * Paso manual: calcula nº de semana para la fecha indicada ($fecha_informe_today).
 * Copia adaptada de CRON/calculo_semana_numero.php (no modifica CRON/).
 */

if (!isset($fecha_informe_today) || $fecha_informe_today === '') {
    $fecha_informe_today = date('Y-m-d');
}

$numeroSemana = numeroSemanaConFecha($fecha_informe_today);
cron_linea('>> Paso: calculo_semana_numero | fecha=' . $fecha_informe_today . ' | semana=' . $numeroSemana);
