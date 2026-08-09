<?php
$hoy = date("Y-m-d");

//OBTENGO LA SUCURSAL
$query1 = "SELECT id_sucursal FROM sucursal ";
$Item1 = mysql_query($query1, $conexion);
mysql_query ("SET NAMES 'utf8'");
while ($rsItem1 = mysql_fetch_assoc($Item1)){ //ARRAY DE SUCURSALES

	$sucursalcron = $rsItem1['id_sucursal'];
	
	
	////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
	
	//OBTENGO EL ARRAY DE EMPEÑOS DE SEGUNDAMANO EN FECHA
	$query = "SELECT * FROM empenos_$sucursalcron WHERE compra_opcion='si' AND estado_empeno = 'enfecha'  ";
	$Item = mysql_query($query, $conexion);
	mysql_query ("SET NAMES 'utf8'");
	while ($rsItem = mysql_fetch_assoc($Item))
	
	{ //ARRAY DE LOTES SEGUNDAMANO EN FECHA
	
		$date1= $rsItem['fecha_vencimiento'];
		$sqldate1=date('Y-m-d',strtotime($date1));
		$empeno=$rsItem['id_empeno'];
		
		//SI LA FECHA DE HOY ES MAYOR A LA DE VENCIMIENTO ACTUALIZO EL ESTADO DEL LOTE DE SEGUDAMANO A VENCIDO
		if($hoy>$sqldate1){mysql_query("UPDATE empenos_$sucursalcron SET estado_empeno='vencido' WHERE id_empeno=$empeno ",$conexion); }else{}
	
	} //END ARRAY DE LOTES SEGUNDAMANO EN FECHA
	
	


} //END ARRAY DE SUCURSALES
?>