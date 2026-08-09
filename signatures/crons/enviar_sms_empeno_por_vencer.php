<?php
echo "<br>";
echo "<h1>enviar_sms_empeno_por_vencer: ".$hora."</h1><br>";

$hoy = date("Y-m-d");

//OBTENGO LA SUCURSAL
$query1 = "SELECT id_sucursal FROM sucursal ";
$Item1 = mysql_query($query1, $conexion);
mysql_query ("SET NAMES 'utf8'");
while ($rsItem1 = mysql_fetch_assoc($Item1)){ //ARRAY DE SUCURSALES
    $sucursalcron = $rsItem1['id_sucursal'];
    $active_code_autorization_pa =  checkSMSsendEmpenyo($sucursalcron, $conexion);
  
    if( $active_code_autorization_pa == "true" ){

        //CONSULTO EL HISTORICO DE RENOVACIONES EN FECHA DE LA SUCURSAL
        $query2 = "SELECT * FROM historico_renovaciones_$sucursalcron WHERE estado_historico='enfecha' ";
        $Item2 = mysql_query($query2, $conexion);
        mysql_query ("SET NAMES 'utf8'");
        while ($rsItem2 = mysql_fetch_assoc($Item2))

        {

            $idrenovaciones=$rsItem2['id_renovaciones'];
            $importerenovacion=$rsItem2['importe_renovacion'];
            $loteid=$rsItem2['lote'];
            $vencimiento= $rsItem2['proximo_vencimiento'];
            $vencido=date('Y-m-d',strtotime($vencimiento));
            $venimientoparset = date('d-m-Y',strtotime($vencimiento));

            $anyo=date('Y',strtotime($vencimiento));
            $mes=date('m',strtotime($vencimiento));
            $dia=date('d',strtotime($vencimiento));

            $fecha_parset = mktime(0,0,0,date("$mes"),date("$dia")-1,date("$anyo"));
            $fechadianteriorvencer = date("Y-m-d", $fecha_parset); 

            if($hoy==$fechadianteriorvencer)		
            { //hoy es un dia antes de vencer

                //OBTENGO EL ESTADO DEL LOTE DE ESTE HISTORICO
                $queryda = "SELECT id_lote, estado_lote, cliente, nombre, apellido, telefono FROM lotes_$sucursalcron LEFT JOIN clientes ON lotes_$sucursalcron.cliente = clientes.id_cliente WHERE id_lote = ".$loteid."  ";
                $Itemda = mysql_query($queryda, $conexion);
                mysql_query ("SET NAMES 'utf8'");
                $rsItemda = mysql_fetch_assoc($Itemda);
                $telefono_destino = $rsItemda['telefono'];
                $id_lote = $rsItemda['id_lote'];
                $cliente = $rsItemda['cliente'];
                $nombre = $rsItemda['nombre']." ".$rsItemda['apellido'];
                echo "id lote: ".$id_lote." > cliente: ".$nombre." > Sucursal: ".$sucursalcron." > Fecha de vencimiento: ".$venimientoparset;
                echo "<br>";


                if(!empty($telefono_destino)){


                    $mensaje_envio = "Estimado cliente, Le recordamos que mañana ".$venimientoparset ." vence el plazo de pago del contrato de opcion de recompra numero ".$id_lote." - ".$sucursalcron;

                    //COMPRUEBO EL ESTADO DEL LOTE PARA PODER HACER ACCIONES
                    if($rsItemda['estado_lote']=='compra'||$rsItemda['estado_lote']=='retirado'||$rsItemda['estado_lote']=='intervenido'||$rsItemda['estado_lote']=='perdido'){ 

                    }else{  //SI EL ESTADO DEL LOTE "NO" ES COMPRA LO HAGO TODO
                        
                                // INSERTO EL CONTROL CRON DE LA VENTA WEB
                                $descripcion_cron = "envia SMS de vencimiento de empeño Nº ".$id_lote." de la sucursal Nº ".$sucursalcron;
                                $tipo_de_operacion = "SMSvencimientoemp";

                                insert_global_cron($conexion, $descripcion_cron, $sucursal_cron, $tipo_de_operacion);

                                $conexion_matermedia = mysql_connect("mysql-5707.dinaserver.com", "sd3ref4df", "Soul@7891") or trigger_error(mysql_error(),E_USER_ERROR); 
                                mysql_select_db("goldservicemater", $conexion_matermedia);
                                mysql_query ("SET NAMES 'utf8'", $conexion_matermedia);

                                mysql_query("INSERT INTO send_sms_clientes ( mensaje_envio, telefono_destino, estado_envio, fecha_envio ) VALUES ( '$mensaje_envio', '$telefono_destino', 'pendiente', now() )",$conexion_matermedia);

                                $hora = date("Y-m-d H:i:s");

                                mysql_query("INSERT INTO sms_send (
                                cliente_sms,
                                movil_sms,
                                type_item_sms,
                                estado_sms,
                                mensaje_sms,
                                rel_item_sms,
                                usuario_sms,
                                surusal_sms,
                                fecha_sms
                                )
                                VALUES (
                                '{$cliente}',
                                '{$telefono_destino}',
                                'vencimiento',
                                'true',
                                '{$mensaje_envio}',
                                '{$id_lote}',
                                '1',
                                '{$sucursalcron}',
                                '{$hora}'
                                )",$conexion);

                                mysql_query("INSERT INTO tareas_cron (
                                descripcion_evento,
                                fecha
                                )
                                VALUES (
                                'INSERT INTO sms_send del lote: $id_lote,
                                now()
                                )",$conexion);


                    }

                }


            } 


        } //END ARRAY DE HISTORICO DE RENOVACIONES

    }

} //END ARRAY DE SUCURSALES

echo "<br><br><br><br><br><br><br><br><br><br><br><br><br><br><br><br><br><br><br><br><br><br><br><br><br><br><br><br><br><br><br><br><br><br><br><br><br><br><br><br><br><br><br><br><br><br><br><br><br><br><br><br><br>--------------------------------------------------------------------";
echo "<br>--------------------------------------------------------------------";
echo "<br>--------------------------------------------------------------------";
echo "enviar_sms_empeno_por_vencer: ".$hora;
?>