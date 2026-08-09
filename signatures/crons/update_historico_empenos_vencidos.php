<?php
$hoy = date("Y-m-d");
$anio_actual = date("Y");
echo "<h1>update_historico_empenos_vencidos: ".$hora."</h1><br>";
//OBTENGO LA SUCURSAL
$query1 = "SELECT id_sucursal FROM sucursal ";
$Item1 = mysql_query($query1, $conexion);
mysql_query ("SET NAMES 'utf8'");
while ($rsItem1 = mysql_fetch_assoc($Item1)){ //ARRAY DE SUCURSALES

	$sucursalcron = $rsItem1['id_sucursal'];
	////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
	
	//CONSULTO EL HISTORICO DE RENOVACIONES EN FECHA DE LA SUCURSAL
	$query2 = "SELECT id_renovaciones, proximo_vencimiento, lote FROM historico_renovaciones_$sucursalcron WHERE estado_historico='enfecha' AND proximo_vencimiento < CURRENT_DATE ";
	$Item2 = mysql_query($query2, $conexion);
	mysql_query ("SET NAMES 'utf8'");
	while ($rsItem2 = mysql_fetch_assoc($Item2))
	
	{ //ARRAY DE HISTORICO DE RENOVACIONES
		
		$idrenovaciones=$rsItem2['id_renovaciones'];
		
		$loteid=$rsItem2['lote'];
		$vencimiento= $rsItem2['proximo_vencimiento'];
        $proximo_vencimiento = date('Y-m-d', strtotime($vencimiento . ' +1 month'));
        
        // VERIFICO SI ES LA ÚLTIMA RENOVACIÓN DE ESTE LOTE
        $querymax = "SELECT MAX(id_renovaciones) as max_id FROM historico_renovaciones_$sucursalcron WHERE lote = $loteid";
        $Itemmax = mysql_query($querymax, $conexion);
        $rsmax = mysql_fetch_assoc($Itemmax);
        
        if ($idrenovaciones != $rsmax['max_id']) {
            continue; // No es la última renovación de este lote, saltamos
        }

        //OBTENGO EL ESTADO DEL LOTE DE ESTE HISTORICO
        $queryda = "SELECT id_lote, estado_lote, precio_compra, precio_recompra, compra_opcion FROM lotes_$sucursalcron WHERE id_lote = ".$loteid."  ";
        $Itemda = mysql_query($queryda, $conexion);
        mysql_query ("SET NAMES 'utf8'");
        $rsItemda = mysql_fetch_assoc($Itemda);
              
        $preciocompra = $rsItemda['precio_compra'];
        $preciorecompra = $rsItemda['precio_recompra'];
        $importerenovacion = $preciorecompra - $preciocompra;
			
        echo $rsItemda['id_lote'];
        echo "<br>";
              $continuar_vencido = "false";
              if($rsItemda['estado_lote']=='enfecha'||$rsItemda['estado_lote']=='vencido'){
                  if($rsItemda['compra_opcion']=='si'){
                      $continuar_vencido = "true";
                  }
              }
            
			//COMPRUEBO EL ESTADO DEL LOTE PARA PODER HACER ACCIONES
            if($continuar_vencido == "true"){ 
						
						echo "El lote esta ".$rsItemda['estado_lote'];
						echo "<br>";
                
                        // INSERTO EL CONTROL CRON
                        $descripcion_cron = "empeño vencido por el cron Nº ".$loteid." de la sucursal Nº ".$sucursalcron;
                        $tipo_de_operacion = "Empenovencido";
                        insert_global_cron($conexion, $descripcion_cron, $sucursal_cron, $tipo_de_operacion);
			
						//ACTUALIZO EL ESTADO DE LA RENOVACION VENCIDA
						mysql_query("UPDATE historico_renovaciones_$sucursalcron SET estado_historico = 'Vencido', fecha_vencido = now() WHERE id_renovaciones=$idrenovaciones ",$conexion);
						
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
						'el historico id $idrenovaciones ha sido actualizado a vencido',
						'cron',
						'$loteid',
						'$idrenovaciones',
						now(),
						'1'
						)",$conexion);
						
						//INSERTO LA NUEVA CUOTA
						mysql_query("INSERT INTO historico_renovaciones_$sucursalcron (
						importe_renovacion,
						lote,
						proximo_vencimiento,
						estado_historico,
						fecha_insert
						)
						VALUES (
						'$importerenovacion',
						'$loteid',
						'$proximo_vencimiento',
						'enfecha',
						now()
						)",$conexion);
						$ID = mysql_insert_id($conexion);
						
						//INSERTO LA ACCION DEL HISTORICO DEL INSERT
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
						'el historico id $ID ha sido insertado en fecha desde el vencido',
						'cron',
						'$loteid',
						'$ID',
						now(),
						'1'
						)",$conexion);
						
						//ACTUALIZO EL ESTADO DE LOTE A VENCIDO
						mysql_query(" UPDATE lotes_$sucursalcron SET estado_lote = 'vencido' WHERE id_lote = $loteid ",$conexion); 
                        
                        $accion_trazabilidad = "vencido";
                        mysql_query(" INSERT INTO trazabilidad_lotes ( 
                            id_lote,
                            fecha_accion,
                            usuario_accion,
                            accion_trazabilidad,
                            comentarios_accion,
                            sucursal_accion
                        ) VALUES (
                            '$loteid',
                            now(),
                            '1',
                            '$accion_trazabilidad',
                            'Lote vencido automaticamente',
                            '$sucursalcron'
                        ) ",$conexion);
                
                    
                        mysql_query(" INSERT INTO tareas_cron ( descripcion_evento, fecha ) VALUES ( 'UPDATE lote vencido $lote Sucursal $sucursalcron', now() ) ",$conexion);
			
			}
			
			
			
		

        
	} //END ARRAY DE HISTORICO DE RENOVACIONES


} //END ARRAY DE SUCURSALES

echo "<br><br><br><br><br><br><br><br><br><br><br><br><br><br><br><br><br><br><br><br><br><br><br><br><br><br><br><br><br><br><br><br><br><br><br><br><br><br><br><br><br><br><br><br><br><br><br><br><br><br><br><br><br>--------------------------------------------------------------------";
echo "<br>--------------------------------------------------------------------";
echo "<br>--------------------------------------------------------------------";
echo "update_historico_empenos_vencidos: ".$hora;
?>