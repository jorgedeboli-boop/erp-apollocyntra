<?
//OBTENGO LA SUCURSAL
$query_sucura = "SELECT id_sucursal, empresa_id FROM sucursal WHERE estado_tienda like 'habilitada' ";
$Item1w_sucura = mysql_query($query_sucura, $conexion);
mysql_query ("SET NAMES 'utf8'");
while ($rsItem1s_sucura = mysql_fetch_assoc($Item1w_sucura)){ //ARRAY DE SUCURSALES

	$sucursal_informe = $rsItem1s_sucura['id_sucursal'];
    $empresa_informe_id = $rsItem1s_sucura['empresa_id'];
    
    // INSERTO EL INFORME Y LO PONGO ABIERTO AL FINAL DE LA EJECUCION DE TODOS LOS COMPONENTES DE INFORMES SE DEBE ACTUALIZAR A CERRADO estado_cron_informe finalizado_cron
    $hoy = date("Y-m-d"); 
    $query3="
    INSERT INTO informe_semanal (
    sucursal_informe, 
    fecha_informe,
    fecha_generado
    hora_generado,
    empresa_informe_id,
    usuario_genera_informe,
    estado_informe,
    estado_cron_informe
    ) VALUES ( 
    '$sucursal_informe',
    '$fecha_informe',
    NOW(),
    NOW(),
    '$empresa_informe_id',
    '1',
    'abierto',
    'inicializado_cron'
    )";
    mysql_query ( $query3 , $conexion );
    $id_informe_generate = mysql_insert_id($conexion);
    
    mysql_query(" INSERT INTO tareas_cron ( descripcion_evento, fecha ) VALUES ('Genero informe Nº $id_informe_generate de la Sucursal $sucursal_informe', now() )",$conexion);
    
}

    echo "<br><br><br><br><br><br><br><br><br><br><br><br><br><br><br><br><br><br><br><br><br><br><br><br><br><br><br><br><br><br><br><br><br><br><br><br><br><br><br><br><br><br><br><br><br><br><br><br><br><br><br><br><br>--------------------------------------------------------------------";
    echo "<br>--------------------------------------------------------------------";
    echo "<br>--------------------------------------------------------------------";
    echo "Generar informe inicia: ".$hora;

?>