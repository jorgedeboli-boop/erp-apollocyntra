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
        
        echo "idrenovaciones ".$idrenovaciones." sucursalcron ".$sucursalcron." loteid ".$loteid;
        echo "<br>";
        
        // VERIFICO SI ES LA ÚLTIMA RENOVACIÓN DE ESTE LOTE
        $querymax = "SELECT MAX(id_renovaciones) as max_id FROM historico_renovaciones_$sucursalcron WHERE lote = $loteid";
        $Itemmax = mysql_query($querymax, $conexion);
        $rsmax = mysql_fetch_assoc($Itemmax);
        
        if ($idrenovaciones != $rsmax['max_id']) {
            continue; // No es la última renovación de este lote, saltamos
        }
        
        echo "es la maxima renovacion";
        echo "<br>";

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
			
			}
			
			
			
		
        
        echo "<br>";
        echo "<br>";
        echo "<br>";
	} //END ARRAY DE HISTORICO DE RENOVACIONES


} //END ARRAY DE SUCURSALES

echo "<br><br><br><br><br><br><br><br><br><br><br><br><br><br><br><br><br><br><br><br><br><br><br><br><br><br><br><br><br><br><br><br><br><br><br><br><br><br><br><br><br><br><br><br><br><br><br><br><br><br><br><br><br>--------------------------------------------------------------------";
echo "<br>--------------------------------------------------------------------";
echo "<br>--------------------------------------------------------------------";
echo "update_historico_empenos_vencidos: ".$hora;
?>