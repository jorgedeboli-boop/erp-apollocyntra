<?php
require("../../session_file.php");
require("../../conexion.php");
$varios = $_GET['varios'];
$id_articulo = $_GET['id_articulo'];
$envio = $_POST['envio'];
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Label Joyeria</title>
    <style>
    .contenedor-principal {
      display: flex;
      flex-direction: column;
      width: 100%;
    }

    .fila-superior {
      display: flex;
      width: 100%;
        text-align: center;
    }

    .mitad {
      flex: 1;
      padding: 0px;
        text-align: center;
    }

    .completo {
      width: 100%;
      padding: 0px;
      text-align: left;
    font-size: 9pt;
    }
    @media print {
            body {
                margin: 0;
                padding: 0;
                text-align: center;
                background: none;
                font-family: Arial, sans-serif;
            }
            
            contenedor-principal {
                display: flex;
                width: 3.4cm;
                height: 1.15cm;
                page-break-after: always;
            }
            .mitad {
                padding: 6pt 0pt;
            }    
            .completo {
                  width: 100%;
                  padding: 0pt 2pt;
                  text-align: left;
                font-size: 4pt;
            }
            .price {
                font-size: 11pt;
                letter-spacing: -0.2pt;
            }
            .sku_title {
                font-size: 5pt;
                letter-spacing: -0.2pt;
                width: 100%;
                display: block;
            }
            .sku {
                font-size: 11pt;
                letter-spacing: -0.2pt;
                width: 100%;
                display: block;
            }
            @page {
                size: 3.5cm 1.2cm;
                margin: 0;
            }
        }
        
  </style>
</head>
<body>
<? if($varios == 'true'){ 
    
    $query = "SELECT id, precio, descripcion FROM articulos_venta WHERE estado = 'noetiquetado_c' OR estado = 'noetiquetado_u' ";
    $Item = mysql_query($query, $conexion);
    mysql_query ("SET NAMES 'utf8'");
    while ($rsItem = mysql_fetch_assoc($Item))
    { 
        $id_articulo = $rsItem['id'];
        $precio = $rsItem['precio'];
        $precio = (int) $precio;
        $descripcion = $rsItem['descripcion'];
        ?>
    
            <div class="contenedor-principal">
        <div class="fila-superior">
            <div class="mitad "><span class="price">€<? echo $precio; ?></span></div>
            <div class="mitad"><span class="sku_title">SKU</span>
                    <span class="sku"><? echo $id_articulo; ?></span></div>
        </div>
        <div class="completo"><? echo $descripcion; ?></div>
    </div>
            
    
        <?
        
        // AHORA ACTUALIZO EL ESTADO DE ARTICULO A STOCK
        mysql_query("UPDATE articulos_venta SET estado ='enventa', update_register = CURDATE() WHERE id =$id_articulo ",$conexion);
        
        // AHORA REGISTRO LA TRAZABILIDAD QUE FUE PUESTO EN STOCK            
        $comentarios_accion = "Artículo puesto a la venta al imprimir desde impresión por lotes por el usuario: ".$nombre_usuario." ";
        $accion_trazabilidad = "enventa";
        trazabilidad_articulos_venta ($conexion, 0, $id_usuario, $accion_trazabilidad, $comentarios_accion, 2, $id_articulo, 0);
        
        // AHORA REGISTRO LA TRAZABILIDAD QUE SE IMPRIMIO ETIQUETA EN RECEPCIÓN DE ENVÍO Y FUE PUESTO EN STOCK            
        $comentarios_accion = "Etiqueta impresa desde central por lotes por el usuario: ".$nombre_usuario." ";
        $accion_trazabilidad = "etiqueta_reimpresa";
        trazabilidad_articulos_venta ($conexion, 0, $id_usuario, $accion_trazabilidad, $comentarios_accion, 2, $id_articulo, 0);
        
        }
    
}elseif($envio == 'true'){ 
    
    $id_envio = $_POST['id_envio'];
    $query = "SELECT id_trazabilidad_articulo, id_articulo_venta FROM trazabilidad_articulos WHERE envio_id = $id_envio AND accion_trazabilidad = 'pasado_stock' ";
    $Item = mysql_query($query, $conexion);
    mysql_query ("SET NAMES 'utf8'");
    while ($rsItem = mysql_fetch_assoc($Item))
    { 
        $id_articulo = $rsItem['id_articulo_venta'];
        
        $queryqw = "SELECT precio FROM articulos_venta WHERE id = '$id_articulo' ";
        $Itemas = mysql_query($queryqw, $conexion);
        mysql_query ("SET NAMES 'utf8'");
        $rsItemas = mysql_fetch_assoc($Itemas);
        $precio = $rsItemas['precio'];
        $precio = (int) $precio;
        $descripcion = $rsItem['descripcion'];
        ?>
    
            <div class="contenedor-principal">
        <div class="fila-superior">
            <div class="mitad "><span class="price">€<? echo $precio; ?></span></div>
            <div class="mitad"><span class="sku_title">SKU</span>
                    <span class="sku"><? echo $id_articulo; ?></span></div>
        </div>
        <div class="completo"><? echo $descripcion; ?></div>
    </div>
            
        <?
    }

}else{ 
    
    $query = "SELECT * FROM articulos_venta WHERE id = $id_articulo ";
    $Item = mysql_query($query, $conexion);
    mysql_query ("SET NAMES 'utf8'");
    $rsItem = mysql_fetch_assoc($Item);
    $id_articulo = $rsItem['id'];
    $precio = $rsItem['precio'];
    $precio = (int) $precio;
    $descripcion = $rsItem['descripcion'];
    ?>
    <div class="contenedor-principal">
        <div class="fila-superior">
            <div class="mitad "><span class="price">€<? echo $precio; ?></span></div>
            <div class="mitad"><span class="sku_title">SKU</span>
                    <span class="sku"><? echo $id_articulo; ?></span></div>
        </div>
        <div class="completo"><? echo $descripcion; ?></div>
    </div>
    <?

} ?>

<script src="../../js/jquery.min.js"></script>
    
<script type="text/javascript">
    
    function PrintWindow() { 
        window.print();
        CheckWindowState();
    }
    function CheckWindowState() { 
        if(document.readyState=="complete") { 
            
            <? if($envio == 'true'){ ?>
                 window.history.back();
            <? }else{ ?>
                window.close();
            <? } ?>
           
        } else { 
            setTimeout("CheckWindowState()", 10) 
        } 
    } 
    PrintWindow();
    
</script>
    
</body>
</html>