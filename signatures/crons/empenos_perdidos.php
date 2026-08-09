<?php
$hoy = date("Y-m-d");
echo "<h1>update_historico_empenos_vencidos: ".$hora."</h1><br>";

$datos_semana = numeroSemanaEnvio($conexion);

// Acceder a las variables
$id_numero_semana = $datos_semana['id_numero_semana'];
$fecha_semana_desde_principal = $datos_semana['fecha_semana_desde'];
$fecha_semana_hasta_principal = $datos_semana['fecha_semana_hasta'];
$numeroSemana_perdido = $datos_semana['numero_semana'];
$anyo_listado_perdido = $datos_semana['anyo_listado'];

//OBTENGO LA SUCURSAL
$query1 = "SELECT id_sucursal, valor_meses_perdidos_empenos FROM sucursal ";
$Item1 = mysql_query($query1, $conexion);
mysql_query ("SET NAMES 'utf8'");
while ($rsItem1 = mysql_fetch_assoc($Item1)){ //ARRAY DE SUCURSALES

	$sucursalcron = $rsItem1['id_sucursal'];
    $valor_meses_perdidos_empenos = $rsItem1['valor_meses_perdidos_empenos'];
    
    // LISTO LOS LOTES VENCIDOS
    $query = " SELECT id_lote FROM lotes_$sucursalcron WHERE estado_lote = 'vencido' AND lote_perdible = 'true' ";
	$Item = mysql_query($query, $conexion);
	mysql_query ("SET NAMES 'utf8'");
	while ($rsItem = mysql_fetch_assoc($Item)){
        
		$lote = $rsItem['id_lote'];
                    
        //CONSULTO LAS RENOVACIONES VENCIDAS
        $queryrenovacionesvencidas = "SELECT * FROM historico_renovaciones_$sucursalcron WHERE lote = '$lote' AND estado_historico = 'Vencido' ";
        $Itemrenovacionesvencidas = mysql_query($queryrenovacionesvencidas, $conexion);
        mysql_query("SET NAMES 'utf8'");
        $renovacionesvencidas = mysql_num_rows($Itemrenovacionesvencidas);

        if($renovacionesvencidas >= $valor_meses_perdidos_empenos){

            //ACTUALIZO EL ESTADO DE LOTE A PERDIDO
            mysql_query(" UPDATE lotes_$sucursalcron SET estado_lote = 'perdido', fecha_perdido = CURDATE(), numero_semana_empenio_perdido = '$numeroSemana_perdido', year_empenio_perdido = '$anyo_listado_perdido', estado_envio = 'pendiente_enviar', envio_numero = 0 WHERE id_lote = $lote ", $conexion);
            
            //ACTUALIZO LOS ARTICULOS PERDIDOS DEL LOTE
            mysql_query("UPDATE rel_articulos_estados SET fecha_perdido_empenio = CURDATE(), rel_numero_semana_empenio_perdido = '$numeroSemana_perdido', year_rel_empenio_perdido = '$anyo_listado_perdido', estado_articulo = 'pendiente_enviar' WHERE rel_id_lote = '$lote' AND rel_id_sucursal = '$sucursalcron' ",$conexion);
            
            // INSERTO EL CONTROL CRON
            $descripcion_cron = "empeño perdido por el cron Nº ".$lote." de la sucursal Nº ".$sucursalcron;
            $tipo_de_operacion = "Empenoperdido";
            insert_global_cron($conexion, $descripcion_cron, $sucursal_cron, $tipo_de_operacion);
            
            $accion_trazabilidad = "perdido";
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
                'Lote perdido automaticamente',
                '$sucursalcron'
            ) ",$conexion);
            
            $accion_trazabilidad = "pendiente_enviar";
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
                'Lote listo para enviar en la semana $numeroSemana_perdido',
                '$sucursalcron'
            ) ",$conexion);

            mysql_query("INSERT INTO tareas_cron ( descripcion_evento, fecha ) VALUES ( 'UPDATE lote perdido $lote Sucursal $sucursalcron', now() )",$conexion);
            
            mysql_query(" INSERT INTO tareas_cron ( descripcion_evento, fecha ) VALUES ( 'UPDATE empeño perdido listo para enviar $lote Sucursal $sucursalcron', now() )",$conexion);

            $queryxx = " SELECT id_renovaciones FROM historico_renovaciones_$sucursalcron WHERE lote = '$lote' AND estado_historico = 'enfecha' ";
            $Itemxx = mysql_query($queryxx, $conexion);
            mysql_query ("SET NAMES 'utf8'");
            $rsItemxx = mysql_fetch_assoc($Itemxx);
            $id_renovaciones = $rsItemxx['id_renovaciones'];

            //ACTUALIZO EL ESTADO DE LA RENOVACION VENCIDA
            mysql_query("UPDATE historico_renovaciones_$sucursalcron SET estado_historico = 'Perdido', fecha_vencido = CURDATE(), fecha_perdido = CURDATE() WHERE id_renovaciones = '$id_renovaciones' ",$conexion);

            //INSERTO LA ACCION DEL HISTORICO DEL UPDATE
            mysql_query("INSERT INTO acciones_historico_renovaciones (
            sucursal,
                        accion,
                        origen,
                        lote_accion,
                        historico_id,
                        fecha_accion,
                        empleado
                        )
                        VALUES (
                        '$sucursalcron',
                        'el historico id $id_renovaciones ha sido actualizado a Perdido',
                        'cron',
                        '$lote',
                        '$id_renovaciones',
                        now(),
                        '1'
                        )",$conexion);

        }
            
        
    }
    
        
}

?>