<?php

$fecha_informe_today = date('Y-m-d');
$numeroSemana = numeroSemanaConFecha($fecha_informe_today);
cron_linea('>> Paso: calculo_semana_numero | semana=' . $numeroSemana);