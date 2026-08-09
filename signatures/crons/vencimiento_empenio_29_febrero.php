<?

$anio_actual = date("Y");
$dia_dia_biciesto = "02-29";
$dia_biciesto = $anio_actual."-".$dia_dia_biciesto;
$dia_update = $anio_actual."-03-01";

//OBTENGO LA SUCURSAL
$query1w = "SELECT id_sucursal FROM sucursal ";
$Item1w = mysql_query($query1w, $conexion);
mysql_query ("SET NAMES 'utf8'");
while ($rsItem1s = mysql_fetch_assoc($Item1w)){ //ARRAY DE SUCURSALES

	$sucursalcron = $rsItem1s['id_sucursal'];
    echo "<br><br><br>".$sucursalcron."<br>";
    
    
	//CONSULTO EL HISTORICO DE RENOVACIONES EN FECHA DE LA SUCURSAL
	$query2 = "SELECT id_renovaciones FROM historico_renovaciones_$sucursalcron WHERE proximo_vencimiento = '$dia_biciesto' ";
	$Item2 = mysql_query($query2, $conexion);
	mysql_query ("SET NAMES 'utf8'");
	while ($rsItem2 = mysql_fetch_assoc($Item2))
	
	{ //ARRAY DE HISTORICO DE RENOVACIONES
		
		$id_renovaciones = $rsItem2['id_renovaciones']."<br>";
        echo "id_renovaciones: ".$id_renovaciones."<br>";
        mysql_query("UPDATE historico_renovaciones_$sucursalcron SET proximo_vencimiento = '$dia_update' WHERE id_renovaciones = '$id_renovaciones' ",$conexion);
        
    }
    
    echo "<br>-----------------<br>";
    
    //CONSULTO EL HISTORICO DE RENOVACIONES EN FECHA DE LA SUCURSAL
	$query2d = "SELECT id_lote FROM lotes_$sucursalcron WHERE fecha_vencimiento = '$dia_biciesto' ";
	$Item2d = mysql_query($query2d, $conexion);
	mysql_query ("SET NAMES 'utf8'");
	while ($rsItem2d = mysql_fetch_assoc($Item2d))
	
	{ //ARRAY DE HISTORICO DE RENOVACIONES
		
		$id_lote = $rsItem2d['id_lote']."<br>";
        echo "id_lote: ".$id_lote."<br>";
        mysql_query("UPDATE lotes_$sucursalcron SET fecha_vencimiento = '$dia_update' WHERE id_lote = '$id_lote' ",$conexion);
        
    }


	
}

?>