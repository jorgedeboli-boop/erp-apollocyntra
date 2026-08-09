<?php

//SOLO SE PASARAN A ENVIADOS LOS DIAS LUNES A LAS 00:00
$dia_semana = getdate();
$dia_semana = $dia_semana['weekday'];
echo $dia_semana;

if($dia_semana == "Monday"){
    
    $hoy = date("Y-m-d");
    $desde_fecha = date("Y-m-d", strtotime("last Monday - 1 week"));
    $hasta_fecha = date("Y-m-d", strtotime("next Sunday - 1 week"));
    
    $datos_semana = numeroSemanaEnvio($conexion);

    // Acceder a las variables
    $id_numero_semana = $datos_semana['id_numero_semana'];
    $fecha_semana_desde_principal = $datos_semana['fecha_semana_desde'];
    $fecha_semana_hasta_principal = $datos_semana['fecha_semana_hasta'];
    $numeroSemana = $datos_semana['numero_semana'];
    $anyo_listado = $datos_semana['anyo_listado'];
    

    echo "<h1>update_lotes para enviar: ".$hora."</h1><br>";
    
    // SOLO EMPEÑOS PERDIDOS Y EL ESTADO DE ENVÍO ES FALSE
    // SOLO LOTES PERDIDOS
    /*
    $query11 = "SELECT id_sucursal FROM sucursal ";
    $Item11 = mysql_query($query11, $conexion);
    mysql_query ("SET NAMES 'utf8'");
    while ($rsItem11 = mysql_fetch_assoc($Item11)){ 

        $sucursalcron = $rsItem11['id_sucursal'];

        // LISTO LOS LOTES PERDIDOS
        $query = " SELECT id_lote FROM lotes_$sucursalcron WHERE compra_opcion = 'si' AND estado_envio = 'false' AND envio_numero = 0 AND estado_lote = 'perdido' AND fecha_perdido < '$fecha_semana_hasta_principal' ";
        
        $Item = mysql_query($query, $conexion);
        mysql_query ("SET NAMES 'utf8'");
        while ($rsItem = mysql_fetch_assoc($Item)){

            $lote = $rsItem['id_lote'];

            mysql_query(" UPDATE lotes_$sucursalcron SET estado_envio = 'pendiente_enviar' WHERE id_lote = $lote AND estado_envio = 'false' ", $conexion);
            
            mysql_query(" UPDATE rel_articulos_estados SET estado_articulo = 'pendiente_enviar' WHERE rel_id_lote = $lote AND rel_id_sucursal = $sucursalcron ",$conexion);
            
            // INSERTO EL CONTROL CRON
            $descripcion_cron = "lote listo para enviar Nº ".$lote." de la sucursal Nº ".$sucursalcron;
            $tipo_de_operacion = "Loteparaenviar";
            insert_global_cron($conexion, $descripcion_cron, $sucursalcron, $tipo_de_operacion);
            
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
                'Lote listo para enviar',
                '$sucursalcron'
            ) ",$conexion);

            mysql_query(" INSERT INTO tareas_cron ( descripcion_evento, fecha ) VALUES ( 'UPDATE empeño perdido listo para enviar $lote Sucursal $sucursalcron', now() )",$conexion);

            }
        
    }
    */
    
    // SOLO LOTES LIBERADOS Y EL ESTADO DE ENVÍO ES FALSE
    $query12 = "SELECT id_sucursal FROM sucursal ";
    $Item12 = mysql_query($query12, $conexion);
    mysql_query ("SET NAMES 'utf8'");
    while ($rsItem12 = mysql_fetch_assoc($Item12)){ //ARRAY DE SUCURSALES

        $sucursalcron = $rsItem12['id_sucursal'];

        // LISTO LOS LOTES LIBERADOS
     // $query = " SELECT id_lote, estado_lote FROM lotes_$sucursalcron WHERE liberado = 'si' AND compra_opcion = 'no' AND estado_envio = 'false' AND fecha_liberado BETWEEN '$desde_fecha' AND '$hasta_fecha' ";
        $query = " SELECT id_lote, estado_lote FROM lotes_$sucursalcron WHERE liberado = 'si' AND compra_opcion = 'no' AND estado_envio = 'false' AND envio_numero = 0 AND estado_lote = 'compra' AND fecha_compra < '$fecha_semana_hasta_principal' ";
        $Item = mysql_query($query, $conexion);
        mysql_query ("SET NAMES 'utf8'");
        while ($rsItem = mysql_fetch_assoc($Item)){

            $lote = $rsItem['id_lote'];
            $estado_lote = $rsItem['estado_lote'];
            if($estado_lote == 'intervenido'){
                                
                mysql_query(" INSERT INTO lotes_intervenidos_envios ( 
                    id_lote_intervenido,
                    id_sucursal_intervenido,
                    fecha_creacion,
                    estado_intervenido
                ) VALUES (
                    '$lote',
                    '$sucursalcron',
                    now(),
                    'pendiente_auditar'
                ) ",$conexion);
                
            }else{

                mysql_query(" UPDATE lotes_$sucursalcron SET estado_envio = 'pendiente_enviar', envio_numero = 0 WHERE id_lote = $lote AND estado_envio = 'false' ", $conexion);
                
                mysql_query(" UPDATE rel_articulos_estados SET estado_articulo = 'pendiente_enviar' WHERE rel_id_lote = $lote AND rel_id_sucursal = $sucursalcron ",$conexion);

                mysql_query(" INSERT INTO tareas_cron ( descripcion_evento, fecha ) VALUES ( 'UPDATE lote liberado listo para enviar $lote Sucursal $sucursalcron', now() )",$conexion);

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
                    'Lote listo para enviar (CRON)',
                    '$sucursalcron'
                ) ",$conexion);
        
            }
        }
    }
    
    
    // SOLO LOTES LIBERADOS Y EL ESTADO INTERVENIDO ES TRUE PERO
    $query12 = "SELECT id_sucursal FROM sucursal ";
    $Item12 = mysql_query($query12, $conexion);
    mysql_query ("SET NAMES 'utf8'");
    while ($rsItem12 = mysql_fetch_assoc($Item12)){ //ARRAY DE SUCURSALES

        $sucursalcron = $rsItem12['id_sucursal'];

        // LISTO LOS LOTES LIBERADOS
     // $query = " SELECT id_lote FROM lotes_$sucursalcron WHERE liberado = 'si' AND estado_lote != 'intervenido' AND estado_envio = 'false' AND intervenido = 'true' ";
        $query = " SELECT id_lote FROM lotes_$sucursalcron WHERE liberado = 'si' AND estado_lote != 'intervenido' AND estado_envio = 'false' AND intervenido = 'true' AND fecha_compra < '$fecha_semana_hasta_principal' ";
        $Item = mysql_query($query, $conexion);
        mysql_query ("SET NAMES 'utf8'");
        while ($rsItem = mysql_fetch_assoc($Item)){

            $lote = $rsItem['id_lote'];

            mysql_query(" UPDATE lotes_$sucursalcron SET estado_envio = 'pendiente_enviar', envio_numero = 0 WHERE id_lote = $lote AND estado_envio = 'false' ", $conexion);
            
            mysql_query(" UPDATE rel_articulos_estados SET estado_articulo = 'pendiente_enviar' WHERE rel_id_lote = $lote AND rel_id_sucursal = $sucursalcron ",$conexion);

            mysql_query(" INSERT INTO tareas_cron ( descripcion_evento, fecha ) VALUES ( 'UPDATE lote liberado listo para enviar $lote Sucursal $sucursalcron', now() )",$conexion);

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
                'Lote listo para enviar (CRON)',
                '$sucursalcron'
            ) ",$conexion);
        
        }
    }
    
    
} // SI ES LUNES CONDICION

?>