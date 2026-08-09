<?php
echo "<br>";
echo "hora3:".$hora;
exit();
//OBTENGO LA SUCURSAL
$query1 = "SELECT id_sucursal FROM sucursal ";
$Item1 = mysql_query($query1, $conexion);
mysql_query ("SET NAMES 'utf8'");
while ($rsItem1 = mysql_fetch_assoc($Item1)){

	$sucursalcron = $rsItem1['id_sucursal'];
    echo $sucursalcron;
    echo "<br>";
	
    //ACTUALIZO LA SUCURSAL
	$query = "SELECT fecha_liberacion, fecha_compra FROM lotes_$sucursalcron ";
	$Item = mysql_query($query, $conexion);
	mysql_query ("SET NAMES 'utf8'");
	while ($rsItem = mysql_fetch_assoc($Item)){
		
		$fecha_liberacion_parset = $rsItem['fecha_liberacion'];
		$fecha_liberacion = date('Y-m-d',strtotime($fecha_liberacion_parset));
		$lote = $rsItem['id_lote'];
        $fecha_compra = $rsItem['fecha_compra'];
        $hoy = date("Y-m-d");
        

        
        $fecha1 = new DateTime("$fecha_compra");
        $fecha2 = new DateTime("$hoy");
        $diff = $fecha1->diff($fecha2);
        $dias_pasados = $diff->days;
        
        echo $dias_pasados;
        echo "<br>";
        
        if( $dias_pasados > 14 ){
            echo "liberado";
            /*
            mysql_query("
			UPDATE lotes_$sucursalcron 
			SET liberado = 'si' 
			WHERE id_lote = $lote 
			",$conexion);
            */
        }else{
            echo "no liberado";
            /*
            mysql_query("
			UPDATE lotes_$sucursalcron 
			SET liberado = 'no' 
			WHERE id_lote = $lote 
			",$conexion);}
            */
        }

        
        /*
		if($hoy>=$fecha_liberacion){
            
            mysql_query("
			UPDATE lotes_$sucursalcron 
			SET liberado = 'si' 
			WHERE id_lote = $lote 
			",$conexion); 
	   }else{
            
            mysql_query("
			UPDATE lotes_$sucursalcron 
			SET liberado = 'no' 
			WHERE id_lote = $lote 
			",$conexion);}
	   }
       */

//CIERRO LAS SUCURSALES
}

?>