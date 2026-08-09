<?php

/**
 * CRON_MANUAL: calcula semana para la fecha del informe (no fuerza "hoy").
 */

if (!isset($fecha_informe_today) || $fecha_informe_today === '') {
    $fecha_informe_today = date('Y-m-d');
}

$numeroSemana = numeroSemanaConFecha($fecha_informe_today);
cron_linea('>> Paso: calculo_semana_numero | fecha=' . $fecha_informe_today . ' | semana=' . $numeroSemana);
