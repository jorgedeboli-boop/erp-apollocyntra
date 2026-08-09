<?php

$fecha_informe_actual_today = date('Y-m-d');
$numeroSemana = numeroSemanaConFecha($fecha_informe_actual_today);
cron_linea('>> Paso: calculo_semana_numero_actual | semana=' . $numeroSemana);