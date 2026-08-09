<?php 

//include_once("../conexion.php");
include_once("../includes/functions.php");

function crear_cajas_inicio(){
	if (es_localhost()) {
        $conexioni = mysqli_connect("localhost", "root", "", "comprooro_20190406") or trigger_error(mysqli_error(),E_USER_ERROR);         
    }else{
        $conexioni = mysqli_connect("localhost", "root", "Killiri54123", "oroefectivo"); 
    }
    
	//OBTENGO LA SUCURSAL
	$query_sucursales = "SELECT id_sucursal, new_sitema_caja FROM sucursal WHERE estado_tienda like 'habilitada'";
	$rSucursales = mysqli_query($conexioni, $query_sucursales);
	//$sucursales = mysqli_fetch_all($rSucursales, MYSQLI_ASSOC);
	//foreach ($sucursales as $key => $sucursal) {
	while ($sucursal = mysqli_fetch_assoc($rSucursales)){
		$sucursalcron = $sucursal['id_sucursal'];
        $new_sitema_caja = $sucursal['new_sitema_caja'];
        
        if( $new_sitema_caja == "false" ){
            
		  crear_caja_inicio($sucursalcron);
            
        }
        
	}
}

function crear_cajas_fin($sucursal = null){
    if (es_localhost()) {
        $conexioni = mysqli_connect("localhost", "root", "", "comprooro_20190406") or trigger_error(mysqli_error(),E_USER_ERROR);         
    }else{
        $conexioni = mysqli_connect("localhost", "root", "Killiri54123", "oroefectivo"); 
    }

    //OBTENGO LA SUCURSAL
    $query_sucursales = "SELECT id_sucursal, new_sitema_caja FROM sucursal WHERE estado_tienda like 'habilitada'";
    $rSucursales = mysqli_query($conexioni, $query_sucursales);
    //$sucursales = mysqli_fetch_all($rSucursales, MYSQLI_ASSOC);
    //foreach ($sucursales as $key => $sucursal) {

    while ($sucursal = mysqli_fetch_assoc($rSucursales)){
        $sucursalcron = $sucursal['id_sucursal'];
        $new_sitema_caja = $sucursal['new_sitema_caja'];
        
        if( $new_sitema_caja == "false" ){
            
		  crear_caja_fin($sucursalcron);
            
        }

    }
}

function revisar_plazos_vencidos(){
	if (es_localhost()) {
        $conexioni = mysqli_connect("localhost", "root", "", "comprooro_20190406") or trigger_error(mysqli_error(),E_USER_ERROR);         
    }else{
        $conexioni = mysqli_connect("localhost", "root", "Killiri54123", "oroefectivo"); 
    }

	$hoy = date("Y-m-d");

	//Obtenemos los plazos vencidos

	$query_vencidos = "SELECT * FROM ventas_plazos 
	WHERE 
	estado like 'Pendiente'
	and fecha_vencimiento like '$hoy'";

	$rVencidos = mysqli_query($conexioni, $query_vencidos);
	//$aVencidos = mysqli_fetch_all(, MYSQLI_ASSOC);
	//foreach ($aVencidos as $key => $aVencido) {
	while ($aVencido = mysqli_fetch_assoc($rVencidos)){

		$sql_venta = "SELECT * FROM ventas 
		WHERE ventas.id = ". $aVencido['id_venta'];
		$rsVenta = mysqli_fetch_assoc(mysqli_query($conexioni, $sql_venta));

		//Actualizamos estado del plazo y de la venta a vendido
		$sql_update_plazo = "UPDATE ventas_plazos SET estado = 'Vencido', fecha_vencido = '$hoy' WHERE id = ". $aVencido['id'];
		mysqli_query($conexioni, $sql_update_plazo);
		
		$sql_acciones_cron = "INSERT INTO acciones_cron (id_sucursal, tipo_accion, id_lote, mensaje)
		VALUES (".$rsVenta['id_sucursal'].",'Plazo vencido',".$aVencido['id'].", 'Plazo vencido de la venta Nº ".$rsVenta['id_venta_sucursal'].". Id_venta general: ".$rsVenta['id']."')";
		mysqli_query($conexioni, $sql_acciones_cron);

		$sql_update_venta = "UPDATE ventas SET estado = 'vencido' WHERE id = ". $aVencido['id_venta'];
		mysqli_query($conexioni, $sql_update_venta);

		$sql_acciones_cron = "INSERT INTO acciones_cron (id_sucursal, tipo_accion, id_lote, mensaje)
		VALUES (".$rsVenta['id_sucursal'].",'Venta vencida',".$aVencido['id_venta'].", 'Venta pasada a vencido: ".$rsVenta['id_venta_sucursal'].". Id_venta general: ".$rsVenta['id']."')";
		mysqli_query($conexioni, $sql_acciones_cron);


		//Confirmamos si tiene el número máximo de plazos creados, para crear otro enfecha o no
		$sql_datos_venta = "SELECT * FROM ventas WHERE id = ". $aVencido['id_venta'];
		$aVenta = mysqli_fetch_assoc(mysqli_query($conexioni, $sql_datos_venta));

		$sql_plazos_creados = "SELECT COUNT(*) as cuenta FROM ventas_plazos WHERE id_venta = ". $aVencido['id_venta'];
		$plazos_creados = mysqli_fetch_assoc(mysqli_query($conexioni, $sql_plazos_creados));
		$plazos_creados = $plazos_creados['cuenta'];
		if (intval($plazos_creados) < intval($aVenta['numero_plazos']) ) {
			$sql_insert_plazo = "INSERT INTO ventas_plazos (
				id_venta,
				estado,
				fecha_vencimiento,
				importe
				)
				VALUES (
				".$aVencido['id_venta'].",
				'Pendiente',
				'".Date('Y-m-d H:i:s', strtotime('+1 month'))."',
				".$aVencido['importe']."
			)";
			mysqli_query($conexioni, $sql_insert_plazo);

			$id_plazo_nuevo = mysqli_insert_id($conexioni);

			$sql_acciones_cron = "INSERT INTO acciones_cron (id_sucursal, tipo_accion, id_lote, mensaje)
			VALUES (".$rsVenta['id_sucursal'].",'Plazo',".$id_plazo_nuevo.", 'Nuevo plazo creado con id: ".$id_plazo_nuevo."')";
			mysqli_query($conexioni, $sql_acciones_cron);

		}

	}
}

?>