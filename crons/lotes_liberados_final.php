<?
echo "hora3:".$hora;
echo "<h1>lotes_liberados_final: ".$hora."</h1><br>";

//OBTENGO LA SUCURSAL
$query19 = "SELECT id_sucursal, dias_liberacion FROM sucursal ";
$Item19 = mysql_query($query19, $conexion);
mysql_query ("SET NAMES 'utf8'");
while ($rsItem19 = mysql_fetch_assoc($Item19)){
    
	$sucursalcron = $rsItem19['id_sucursal'];
    $dias_liberacion = $rsItem19['dias_liberacion'];
	
    //ACTUALIZO LA SUCURSAL
	$query22 = "SELECT id_lote, fecha_compra FROM lotes_$sucursalcron WHERE liberado = 'no' ";
	$Item22 = mysql_query($query22, $conexion);
	mysql_query ("SET NAMES 'utf8'");
	while ($rsItem22 = mysql_fetch_assoc($Item22)){
		
		$lote = $rsItem22['id_lote'];
        $fecha_compra = $rsItem22['fecha_compra'];
        $hoy = date("Y-m-d");
        
        $fecha1 = new DateTime("$fecha_compra");
        $fecha2 = new DateTime("$hoy");
        $diff = $fecha1->diff($fecha2);
        $dias_pasados = $diff->days;
        
        if( $dias_pasados >= $dias_liberacion ){
            
            // INSERTO EL CONTROL CRON
            $descripcion_cron = "loteliberado por el cron Nº ".$lote." de la sucursal Nº ".$sucursalcron;
            $tipo_de_operacion = "Loteliberado";

            insert_global_cron($conexion, $descripcion_cron, $sucursal_cron, $tipo_de_operacion);

            mysql_query(" UPDATE lotes_$sucursalcron SET liberado = 'si', fecha_liberado = CURDATE() WHERE id_lote = $lote ",$conexion);
            
            mysql_query(" UPDATE rel_articulos_estados SET estado_articulo = 'Liberado' WHERE rel_id_lote = $lote AND rel_id_sucursal = $sucursalcron ",$conexion);
            
            mysql_query(" INSERT INTO tareas_cron ( descripcion_evento, fecha ) VALUES ('UPDATE lote liberado $lote Sucursal $sucursalcron', now() )",$conexion);
            
            
            $accion_trazabilidad = "liberado";
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
                'Lote liberado automaticamente',
                '$sucursalcron'
            ) ",$conexion);
            
            
        }

    }

//CIERRO LAS SUCURSALES
}

echo "<br><br><br><br><br><br><br><br><br><br><br><br><br><br><br><br><br><br><br><br><br><br><br><br><br><br><br><br><br><br><br><br><br><br><br><br><br><br><br><br><br><br><br><br><br><br><br><br><br><br><br><br><br>--------------------------------------------------------------------";
echo "<br>--------------------------------------------------------------------";
echo "<br>--------------------------------------------------------------------";
echo "lotes_liberados_final: ".$hora;

?>