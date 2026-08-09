<?php
echo "<br>";
echo "<h1>generar gastos variables: ".$hora."</h1><br>";

$queryGF = "SELECT * FROM gastos_fijos WHERE estado_gasto_fijo = 'true' ";
$ItemGF = mysql_query($queryGF, $conexion);
mysql_query ("SET NAMES 'utf8'");
while ($rsItem_GF = mysql_fetch_assoc($ItemGF)){ //ARRAY DE SUCURSALES
    
    $id_gasto_fijo = $rsItem_GF['id_gasto_fijo'];
    $periodo_gasto_fijo = $rsItem_GF['periodo_gasto_fijo'];
    $fecha_inicio_gasto_fijo = $rsItem_GF['fecha_inicio_gasto_fijo'];
    $total_gasto_fijo = $rsItem_GF['total_gasto_fijo'];
    $tipo_de_gasto_fijo = $rsItem_GF['tipo_de_gasto_fijo'];
    $descripcion_gasto_fijo = $rsItem_GF['descripcion_gasto_fijo'];
    $proveedor_gasto_fijo = $rsItem_GF['proveedor_gasto_fijo'];
    $sucursal_gasto_fijo = $rsItem_GF['sucursal_gasto_fijo'];
    $forma_pago_gasto_fijo = $rsItem_GF['forma_pago_gasto_fijo'];
    $empresa_gasto_fijo = $rsItem_GF['empresa_gasto_fijo'];
    $fecha_alta_gasto_fijo = $rsItem_GF['fecha_alta_gasto_fijo'];
    $gasto_tipo = $rsItem_GF['gasto_tipo'];
    
    $querysa = " SELECT numero_forma_pago FROM rel_gastos_forma_pago WHERE gasto_fijo_id = '$id_gasto_fijo' AND forma_de_pago_id = '$forma_pago_gasto_fijo' ";
    $Itemda = mysql_query($querysa, $conexion);
    mysql_query("SET NAMES 'utf8'");
    $rsItemda = mysql_fetch_assoc($Itemda);
    $numero_forma_pago = $rsItemda['numero_forma_pago'];
    
    $fechaActual = date('Y-m-d');
    
    // Crear objetos DateTime
    $fechaInicio = new DateTime($fecha_inicio_gasto_fijo);
    $fechaFin = new DateTime($fechaActual);

    // Calcular diferencia
    $diferencia = $fechaInicio->diff($fechaFin);
    $mesesTranscurridos = ($diferencia->y * 12) + $diferencia->m;

    // Array para almacenar todas las fechas
    $fechasPorMes = array();

    // Crear una copia de la fecha de inicio para no modificar la original
    $fechaTemporal = new DateTime($fecha_inicio_gasto_fijo);

    // Generar array con cada fecha mensual
    for ($i = 0; $i <= $mesesTranscurridos; $i++) {
        // Agregar la fecha actual al array
        $fechasPorMes[] = $fechaTemporal->format('Y-m-d');

        // Sumar un mes para la siguiente iteración
        $fechaTemporal->modify('+1 month');
    }

    // Mostrar resultados
    /*
    echo "Fecha de inicio: " . $fecha_inicio_gasto_fijo . "<br>";
    echo "Fecha actual: " . $fechaActual . "<br>";
    echo "Meses transcurridos: " . $mesesTranscurridos . "<br>";
    echo "Total de fechas en array: " . count($fechasPorMes) . "<br><br>";

    echo "<br>Array completo:<br>";
    */
    foreach ($fechasPorMes as $index => $fecha) {
        ///echo "[$index] => $fecha<br>";
        
        // CONSULTO LA CANTIDAD DE GASTOS VARIABLES GENERADOS
        $query2 = " SELECT id_gasto FROM gastos WHERE rel_id_gasto_fijo = '$id_gasto_fijo' AND DATE(fecha_gasto) = '$fecha' ";
        $Item2 = mysql_query ( $query2, $conexion );
        $rsItem2 = mysql_fetch_assoc ( $Item2 );
        $id_gasto = $rsItem2['id_gasto'];
       
        if(empty($id_gasto)){
            //echo "esta vacio SI<br>";
            // INSERTO EL GASTO
            mysql_query("INSERT INTO gastos (
            rel_id_gasto_fijo,
            empresa_gasto,
            fecha_gasto,
            fecha_pago_gasto,
            usuario_creacion_gasto,
            usuario_pago_gasto,
            sucursal_gasto,
            proveedor_gasto,
            total_gasto,
            forma_pago_gasto,
            tipo_de_gasto,
            descripcion_gasto,
            creado_desde,
            origen_gasto_variable,
            gasto_tipo,
            estado_gasto
            )
            VALUES (
            '$id_gasto_fijo',
            '$empresa_gasto_fijo',
            '$fecha',
            '$fecha',
            '1',
            '1',
            '$sucursal_gasto_fijo',
            '$proveedor_gasto_fijo',
            '$total_gasto_fijo',
            '$forma_pago_gasto_fijo',
            '$tipo_de_gasto_fijo',
            '$descripcion_gasto_fijo',
            'Cron',
            'gasto_fijo',
            '$gasto_tipo',
            'pagado'
            )",$conexion);
            $id_gasto = mysql_insert_id();
            
            // INSERTO EL REGISTRO DE LA FORMA DE PAGO
            mysql_query("INSERT INTO rel_gastos_forma_pago (
            gasto_id,
            forma_de_pago_id,
            numero_forma_pago,
            empresa_id_rel,
            fecha_rel
            )
            VALUES (
            '$id_gasto',
            '$forma_pago_gasto_fijo',
            '$numero_forma_pago',
            '$empresa_gasto_fijo',
            now()
            )",$conexion);
            
        }
    }

    
}

echo "<br><br><br><br><br><br><br><br><br><br><br><br><br><br><br><br><br><br><br><br><br><br><br><br><br><br><br><br><br><br><br><br><br><br><br><br><br><br><br><br><br><br><br><br><br><br><br><br><br><br><br><br><br>--------------------------------------------------------------------";
echo "<br>--------------------------------------------------------------------";
echo "<br>--------------------------------------------------------------------";
echo "gastos fijos: ".$hora;
?>