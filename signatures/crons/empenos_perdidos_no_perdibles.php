<?php
$hoy = date("Y-m-d");
echo "<h1>update_historico_empenos_vencidos: ".$hora."</h1><br>";

$datos_semana_actual_con_anyo = numeroSemanaActualConAnyo($conexion);
$numeroSemana = $datos_semana_actual_con_anyo['numero_semana'];
$anyo_listado = $datos_semana_actual_con_anyo['anyo_listado'];

//OBTENGO LA SUCURSAL
$query1 = "SELECT id_sucursal, valor_meses_perdidos_empenos FROM sucursal ";
$Item1 = mysql_query($query1, $conexion);
mysql_query ("SET NAMES 'utf8'");
while ($rsItem1 = mysql_fetch_assoc($Item1)){ //ARRAY DE SUCURSALES

	$sucursalcron = $rsItem1['id_sucursal'];
    $valor_meses_perdidos_empenos = $rsItem1['valor_meses_perdidos_empenos'];
    
    // LISTO LOS LOTES VENCIDOS
    $query = " SELECT id_lote FROM lotes_$sucursalcron WHERE estado_lote = 'vencido' AND lote_perdible = 'false' ";
	$Item = mysql_query($query, $conexion);
	mysql_query ("SET NAMES 'utf8'");
	while ($rsItem = mysql_fetch_assoc($Item)){
        
		$lote = $rsItem['id_lote'];
                    
        //CONSULTO LAS RENOVACIONES VENCIDAS
        $queryrenovacionesvencidas = "SELECT * FROM historico_renovaciones_$sucursalcron WHERE lote = '$lote' AND estado_historico = 'Vencido' ";
        $Itemrenovacionesvencidas = mysql_query($queryrenovacionesvencidas, $conexion);
        mysql_query("SET NAMES 'utf8'");
        $renovacionesvencidas = mysql_num_rows($Itemrenovacionesvencidas);
        
        $valor_meses_perdidos_empenos_parset = $valor_meses_perdidos_empenos + 2;

        if($renovacionesvencidas >= $valor_meses_perdidos_empenos_parset){

            $accion_trazabilidad = "noperdible";
            mysql_query(" INSERT INTO trazabilidad_lotes ( 
                id_lote,
                fecha_accion,
                usuario_accion,
                accion_trazabilidad,
                comentarios_accion,
                sucursal_accion
            ) VALUES (
                '$lote',
                now(),
                '1',
                '$accion_trazabilidad',
                'Lote pasado 2 renovaciones de 3 vencidas',
                '$sucursalcron'
            ) ",$conexion);

            mysql_query("INSERT INTO tareas_cron ( descripcion_evento, fecha ) VALUES ( 'Lote Nº $lote pasado 2 renovaciones de 3 vencidas Sucursal $sucursalcron', now() )",$conexion);
            
            $query = "DELETE FROM empenos_vencidos_no_perdibles WHERE id_lote_rel = '$lote' AND id_sucursal_rel = '$sucursalcron' ";
            $Item = mysql_query($query, $conexion);
            
            mysql_query("INSERT INTO empenos_vencidos_no_perdibles ( id_lote_rel, id_sucursal_rel, cuotas_vencidas, usuario_update, fecha_update ) VALUES ( '$lote', '$sucursalcron', '$renovacionesvencidas', '1', now() )",$conexion);

        }else{
            $query = "DELETE FROM empenos_vencidos_no_perdibles WHERE id_lote_rel = '$lote' AND id_sucursal_rel = '$sucursalcron' ";
            $Item = mysql_query($query, $conexion);
        }
            
        
    }
    
        
}

?>