<?php
$hora = date("Y-m-d H:i:s");
require('conexion.php');
echo "<br>";
echo "hora1:".$hora;

require('crons/enviar_sms_empeno_por_vencer.php');
require('crons/lotes_liberados_final.php');
require('crons/update_historico_empenos_vencidos.php');
/*
require('crons/empenos_perdidos.php');
require('crons/empenos_perdidos_no_perdibles.php');
*/
require('crons/lotes_para_enviar.php');
require('crons/vencimiento_empenio_29_febrero.php');
require('crons/apertura_de_caja.php');
require('crons/generar_gastos_variables.php');

require('crons/borrar_registros_cronweb.php');

echo "<br><br><br><br><br><br><br><br><br><br><br><br><br><br><br><br><br><br><br><br><br><br><br><br><br><br><br><br><br><br><br><br><br><br><br><br><br><br><br><br><br><br><br><br><br><br><br><br><br><br><br><br><br>--------------------------------------------------------------------";
echo "<br>--------------------------------------------------------------------";
echo "<br>--------------------------------------------------------------------";
echo "CRON FINALIZADO: ".$hora;


mysql_query("INSERT INTO tareas_cron (
descripcion_evento,
fecha
)
VALUES (
'Cron finalizado',
now()
)",$conexion);



/*
echo "test 12";+

$conexion_matermedia = mysql_connect("mysql-5707.dinaserver.com", "sd3ref4df", "Soul@7891") or trigger_error(mysql_error(),E_USER_ERROR); 
mysql_select_db("goldservicemater", $conexion_matermedia);
mysql_query ("SET NAMES 'utf8'", $conexion_matermedia);

$venimientoparset = date('d-m-Y',strtotime("2023-07-04"));
$id_lote = "999";


$texto_enviar = "Estimado cliente, Le recordamos que mañana ".$venimientoparset ." vence el plazo de pago del contrato de opcion de recompra numero ".$id_lote." - 52";



mysql_query("INSERT INTO send_sms_clientes ( mensaje_envio, telefono_destino, estado_envio, fecha_envio ) VALUES ( '$texto_enviar', '644174243', 'pendiente', now() )",$conexion_matermedia);
*/


// Estimado jorge javier deboli, este mensaje es para recordarle que mañana 4-Julio-2023 vence el periodo de pago de la opción de recompra Nº 666 / 49

/*
mysql_query("INSERT INTO sms_send ( cliente_sms, movil_sms, type_item_sms, estado_sms, mensaje_sms, rel_item_sms, usuario_sms, surusal_sms, fecha_sms ) VALUES ( '23995', '644174243', 'vencimiento', 'true', 'texto corto', '777', '1', '49', '{$hora}'  )",$conexion);
*/
?>