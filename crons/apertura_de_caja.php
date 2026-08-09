<?
//OBTENGO LA SUCURSAL
$query1w = "SELECT id_sucursal, new_sitema_caja FROM sucursal WHERE estado_tienda like 'habilitada' ";
$Item1w = mysql_query($query1w, $conexion);
mysql_query ("SET NAMES 'utf8'");
while ($rsItem1s = mysql_fetch_assoc($Item1w)){ //ARRAY DE SUCURSALES

	$sucursalcron = $rsItem1s['id_sucursal'];
    $new_sitema_caja = $rsItem1s['new_sitema_caja'];
    echo "<br><br><br>".$sucursalcron."<br>";
    
    if( $new_sitema_caja == "true" ){ // REALIZO LA APERTURA DE LA CAJA
        
        // Recuperar el ultimo inicio.
        $query2s = "SELECT id_movimientos, fecha_apunte, entrada FROM movimientos_de_caja_$sucursalcron WHERE cierre_caja = 'false' AND grupos = 'CAJA INICIO' ORDER BY id_movimientos DESC LIMIT 1";
        $Item2s = mysql_query ( $query2s, $conexion );
        $rsItem2s = mysql_fetch_assoc ( $Item2s );
        $fecha_apunte_inicio = $rsItem2s['fecha_apunte'];
        $id_apunte_inicio = $rsItem2s['id_movimientos'];
        $total_caja_inicio = $rsItem2s['entrada'];
        
        // Recuperar el ultimo cierre.
        $query2 = "SELECT salida, fecha_apunte FROM movimientos_de_caja_$sucursalcron WHERE cierre_caja = 'true' AND grupos = 'CAJA FINAL' ORDER BY id_movimientos DESC LIMIT 1";
        $Item2 = mysql_query ( $query2, $conexion );
        $rsItem2 = mysql_fetch_assoc ( $Item2 );
        $apuntecierre = $rsItem2['salida'];
        $fecha_apunte_cierre = $rsItem2['fecha_apunte'];
        
        if( $fecha_apunte_inicio == $fecha_apunte_cierre ){
            
            // SI LAS FECHAS DE APERTURA E INICIO COINCIDEN PROCEDEMOS A REALIZAR LA APERTURA AUTOMATICA DE LA CAJA
            $hoy = date("Y-m-d"); 
            $query3="
            INSERT INTO movimientos_de_caja_$sucursalcron (
            cierre_caja, 
            usuario,
            grupos,
            concepto,
            entrada, 
            fecha_apunte,
            hora_de_apunte
            ) VALUES ( 
            'false', 
            'cron', 
            'CAJA INICIO', 
            'Apertura de caja del $hoy ', 
            '$apuntecierre', 
            now(), 
            now()
            )";
            mysql_query ( $query3 , $conexion );
            
            // INSERTO EL CONTROL CRON
            $descripcion_cron = "Apertura cron de caja Sucursal Nº ".$sucursalcron;
            $tipo_de_operacion = "Aperturacaja";
            insert_global_cron($conexion, $descripcion_cron, $sucursalcron, $tipo_de_operacion);
            
        }else{
            
            // CONTROLO SI HUBO ALGUN MOVIMIENTO EN LA FECHA DEL DIA LA ULTIMA APERTURA
            $query2COUNT = "SELECT COUNT(id_movimientos) AS TOTALMOVIMIENTOS FROM movimientos_de_caja_$sucursalcron WHERE fecha_apunte = '$fecha_apunte_inicio' 
            AND cierre_caja = 'false' 
            AND id_movimientos NOT IN ($id_apunte_inicio)
            ";
            $Item2COUNT = mysql_query ( $query2COUNT, $conexion );
            $rsItem2OUNT = mysql_fetch_assoc ( $Item2COUNT );
            $TOTALMOVIMIENTOS = $rsItem2OUNT['TOTALMOVIMIENTOS'];
            
            
            if($TOTALMOVIMIENTOS > 0){ // SI HAY MOVIMIENTOS Y NO HAY CIERRE PROCEDO A PONER LA SUCURSAL EN ESTADO CAJA NO CERRADA
                // INSERTO EL CONTROL CRON
                $descripcion_cron = "Caja no cerrada Sucursal Nº ".$sucursalcron;
                $tipo_de_operacion = "Cajanocerrada";
                insert_global_cron($conexion, $descripcion_cron, $sucursalcron, $tipo_de_operacion);

                // SI LAS FECHAS DE LOS ULTIMOS CAJA INICIO Y CAJA CIERRE NO COINVIDEN PONGO LA SUCURSAL EN ESTADO DE CAJA NO CERRADA TRUE
                mysql_query("UPDATE sucursal SET
                caja_cerrada = 'false'
                WHERE id_sucursal = $sucursalcron ",$conexion);
                     
            }else if( $TOTALMOVIMIENTOS < 1 || empty($TOTALMOVIMIENTOS) ){ 
                /*
                //CONTROLO SI HAY ARQUEO
                $query = "SELECT * FROM cierre_caja_$sucursal WHERE id_fecha_cierre=(SELECT MAX(id_fecha_cierre) FROM cierre_caja_$sucursal); ";
                $Item = mysql_query($query, $conexion);
                mysql_query ("SET NAMES 'utf8'");
                $rsItem = mysql_fetch_assoc($Item);

                $b500 = $rsItem['b500'];
                $b200 = $rsItem['b200'];
                $b100 = $rsItem['b100'];
                $b50 = $rsItem['b50'];
                $b20 = $rsItem['b20'];
                $b10 = $rsItem['b10'];
                $b5 = $rsItem['b5'];
                $m2 = $rsItem['m2'];
                $m1 = $rsItem['m1'];
                $t500 = $rsItem['t500'];
                $t200 = $rsItem['t200'];
                $t100 = $rsItem['t100'];
                $t50 = $rsItem['t50'];
                $t20 = $rsItem['t20'];
                $t10 = $rsItem['t10'];
                $t5 = $rsItem['t5'];
                $t2 = $rsItem['t2'];
                $t1 = $rsItem['t1'];
                $t50cent = $rsItem['t50cent'];
                $t20cent = $rsItem['t20cent'];
                $t10cent = $rsItem['t10cent'];
                $t5cent = $rsItem['t5cent'];
                $t2cent = $rsItem['t2cent'];
                $t1cent = $rsItem['t1cent'];
                $cent50 = $rsItem['50cent'];
                $cent20 = $rsItem['20cent'];
                $cent10 = $rsItem['10cent'];
                $cent5 = $rsItem['5cent'];
                $cent2 = $rsItem['2cent'];
                $cent1 = $rsItem['1cent'];
                $efectivo  = $rsItem['efectivo'];
                $caja = $rsItem['caja'];
                $diferencia = $rsItem['diferencia'];
                
                // COMO NO HUBO MOVIMIENTOS PROCEDO A GUARDAR EL ARQUEO POR QUE SE SUPONE QUE TIENE EL MISMO EFECTIVO
                mysql_query("INSERT INTO cierre_caja_$sucursalcron (b500,b200,b100,b50,b20,b10,b5,m2,m1,t500,t200,t100,t50,t20,t10,t5,t2,t1,t50cent,t20cent,t10cent,t5cent,t2cent,t1cent,50cent,20cent,10cent,5cent,2cent,1cent,efectivo,caja,diferencia,fecha_cierre )
                VALUES ('$b500', '$b200', '$b100', '$b50', '$b20', '$b10', '$b5', '$m2', '$m1', '$t500', '$t200', '$t100', '$t50', '$t20', '$t10', '$t5', '$t2', '$t1', '$t50cent', '$t20cent', '$t10cent', '$t5cent', '$t2cent', '$t1cent', '$cent50', '$cent20', '$cent10', '$cent5', '$cent2', '$cent1', '$efectivo', '$caja', '$diferencia', NOW())",$conexion);
                */
  
                //COMO NO HUBO MOVIMIENTOS PROCEDO A CERRAR LA CAJA
                mysql_query("INSERT INTO movimientos_de_caja_$sucursalcron (
                cierre_caja,
                fecha_apunte,
                grupos,
                salida,
                usuario
                )
                VALUES (
                'true',
                '$fecha_apunte_inicio' ,
                'CAJA FINAL',
                '$total_caja_inicio',
                'cron'
                )",$conexion);
                $IDA = mysql_insert_id();
                
                // INSERTO EL CONTROL CRON
                $descripcion_cron = "Cierre cron de caja Sucursal Nº ".$sucursalcron." no se registraron movimientos.";
                $tipo_de_operacion = "Cajacerrada";
                insert_global_cron($conexion, $descripcion_cron, $sucursalcron, $tipo_de_operacion);
                
                // SI LAS FECHAS DE LOS ULTIMOS CAJA INICIO Y CAJA CIERRE NO COINVIDEN PONGO LA SUCURSAL EN ESTADO DE CAJA NO CERRADA TRUE
                mysql_query("UPDATE sucursal SET
                caja_cerrada = 'true'
                WHERE id_sucursal = $sucursalcron ",$conexion);
                
                // UNA VEZ REALIZADO EL APUNTE DE CIERRE AUTOMATICO PROCEDO A ABRIR LA CAJA CON EL MISMO VALOR Q TIENE EL CIERRE PERO CON FECHA DE HOY
                $hoy = date("Y-m-d"); 
                $query3="
                INSERT INTO movimientos_de_caja_$sucursalcron (
                cierre_caja, 
                usuario,
                grupos,
                concepto,
                entrada, 
                fecha_apunte,
                hora_de_apunte
                ) VALUES ( 
                'false', 
                'cron', 
                'CAJA INICIO', 
                'Apertura de caja del $hoy ', 
                '$apuntecierre', 
                now(), 
                now()
                )";
                mysql_query ( $query3 , $conexion );

                // INSERTO EL CONTROL CRON
                $descripcion_cron = "Apertura cron de caja Sucursal Nº ".$sucursalcron." no se registraron movimientos.";
                $tipo_de_operacion = "Aperturacaja";
                insert_global_cron($conexion, $descripcion_cron, $sucursalcron, $tipo_de_operacion);
            }
            
            
            
        }
        
    }
    
}

?>