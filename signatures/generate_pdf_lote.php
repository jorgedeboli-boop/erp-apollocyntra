<?php
    
$identificador_compra = $iditem;

$query = "SELECT * FROM lotes LEFT JOIN clientes ON lotes.cliente = clientes.id_cliente WHERE lotes.sucursal_compra LIKE '".$sucursal."' AND identificador = '".$identificador_compra."'";

mysql_query ("SET NAMES 'utf8'");
$Item = mysql_query($query, $conexion);
$rsItem = mysql_fetch_assoc($Item);
$idl=$rsItem['id_compra'];
$id_document=$rsItem['id_compra'];
$numerocontrato=$rsItem['id_compra'];
$id_cliente=$rsItem['id_cliente'];
$nombreapellidocliente=$rsItem['nombre']." ".$rsItem['apellido'];
$direccioncliente=$rsItem['direccion'];
$codigospotalcliente=$rsItem['codigo_postal'];
$ciudadcliente=$rsItem['poblacion'];
$provinciacliente=$rsItem['provincia'];
$telefonocliente=$rsItem['telefono'];
$dnicliente=$rsItem['identificacion'];
$contratopol = $rsItem['idpol'];
$date= $rsItem['fecha_compra'];
$fechacontrato=date('d-m-Y',strtotime($date));
$datevencimiento=date('d-m-Y',strtotime($date));
$preciocompra = $rsItem['precio_compra'];
$user_generate = $rsItem['comprado_por'];

$difrecompra = $rsItem['precio_recompra']-$rsItem['precio_compra'];

// CONSULTO LOS DATOS DEL USUARIO QUE GENERO EL CONTRATO
$query = mysql_query("SELECT id_usuario,usuario,password,estado_usuario,privilegio_usuario,sucursal_usuario, nombre_usuario, apellido_usuario FROM usuarios WHERE id_usuario = '".$user_generate."' ") or die(mysql_error());
$row = mysql_fetch_array($query);
mysql_query ("SET NAMES 'utf8'");
$id_usuario_firma = $row['id_usuario'];
$firma_usuario = $row['nombre_usuario']." ".$row['apellido_usuario'];
// RECOJO LA FIRMA DEL EMPLEADO
if ($statesignature=='true'){    
    $signature_value_user = signaturesManager("view", "", "", $id_usuario_firma, "user");
    if(!empty($signature_value_user)){
        $textSignature_user = $firma_usuario;
        $signatureInsert_user = generateSignatureContrato( $signature_value_user, $textSignature_user );
    } 
}

// RECOJO LA FIRMA DEL CLIENTE
if ($statesignature=='true'){    
    $signature_value = signaturesManager("view", "", "", $identificador_compra, "lote");
    if(!empty($signature_value)){
        $textSignature = $nombreapellidocliente;
        $signatureInsert = generateSignatureContrato( $signature_value, $textSignature );
    } 
}

include("../MPDF54/mpdf.php");
$mpdfone=new mPDF('win-1252','A4','','',5,5,30,21,5,5); 
$mpdfone->useOnlyCoreFonts = true;    // false is default
$mpdfone->SetProtection(array('print'));
$mpdfone->SetTitle("Compra ".$nameCompany."");
$mpdfone->SetAuthor("".$nameCompany."");
$mpdfone->SetWatermarkText("Enviada");
$mpdfone->showWatermarkText = false;
$mpdfone->watermark_font = 'DejaVuSansCondensed';
$mpdfone->watermarkTextAlpha = 0.1;
$mpdfone->SetDisplayMode('fullpage');
$mpdfone->hyphenate = true;
$mpdfone->SHYlang = 'es';
$mpdfone->SetHTMLHeader('
<table width="100%" style="margin-top:10px;">
    <tr>
        <td width="50%">
        	<img src="'.$logotipoPdf.'" width="300" height="60">
        </td>
        <td width="50%" style="text-align: right; vertical-align: bottom;">
            <span style="text-align:center; float: right; font-size: 18px; " ><strong>Lote Nº '.$id_document.'</strong></span>
        </td>
    </tr>
</table>
');
$mpdfone->SetHTMLFooter('
<div class="footer" style="padding-top: 20px;">
 	<p class="textofooter" style="text-align:right; font-size: 11px;">Página Nº {PAGENO}</p>
</div>
'
);

$html = '
<html>
<head>
<meta charset="utf-8">
    <style type="text/css">
        body {
            color:#666666;
            background-color: #FFFFFF;
            margin: 0;
            font-family:"chelvetica", Arial, sans-serif;
        }
        #container {
            margin:0 auto;
            width:1024px;
        }
        table{
            border:none;
            border-collapse:collapse;
        }
        .titulos {
            font-size:20px;
            font-weight:bold;
        }

        .datos {
            font-size:14px;

        }

        .datostitulo {
            font-size:16px;
            font-weight:bold;
        }

        .condiciones {
            font-size:14px;
        }

        .titulocolumna {
            font-size:13px;
            text-align:center;
            line-height:29px;
            font-weight:bold;
        }
        .titulocolumna_lighter {
            font-size:13px;
            text-align:center;
            line-height:29px;
        }
    </style>
</head>

<body>
    <div id="container">

         <table style=" width:100%;" >
             <tr>
               <td><p class="titulos">Contrato de compra</p></td>
             </tr>
         </table>
         <br/>
         <table style=" width:100%; margin:0 0 15px 0; " class="datos" >
             <tr>	
               <td width="362">'.$nameCompany.'</td>
               <td width="338">'.$nombreapellidocliente.'</td>
             </tr>
             <tr>
               <td>'.$direcciontienda.'</td>
               <td>'.$direccioncliente.'</td>
             </tr>
             <tr>
               <td>'.$codigospotaltienda.' '.$ciudadtienda.' ('.$provinciatienda.')</td>
               <td>'.$codigospotalcliente.' '.$ciudadcliente.' ('.$provinciacliente.')</td>
             </tr>
             <tr>
               <td>Tel.: '.$telefonotienda.'</td>
               <td>Tel.: '.$telefonocliente.'</td>
             </tr>
             <tr>
               <td>CIF: '.$ciftienda.'</td>
               <td>NIF: '.$dnicliente.'</td>
             </tr>
             <tr>
               <td>(en adelante Comprador)</td>
               <td>(en adelate Vendedor)</td>
             </tr>
             <tr>
               <td>&nbsp;</td>
               <td>&nbsp;</td>
             </tr>
             <tr>
                    <td colspan="2">
                        <p class="condiciones">Datos de la venta. El vendedor reconoce que los artículos que se detallan a continuación son de su legítima propiedad.</p>
                    </td>
             </tr>
         </table>

         <table width="100%" border="1" cellpadding="0" cellspacing="0" class="datos">
             <tr class="titulocolumna">
               <td class="titulocolumna">Código</td>
               <td class="titulocolumna">Descripción</td>
               <td class="titulocolumna" width="50">Uds.</td>
               <td class="titulocolumna" width="150">Importe</td>
             </tr>
        ';

        $querys = "SELECT * FROM stock WHERE id_lote = $idl AND stock.sucursal=$sucursal   ";
        mysql_query ("SET NAMES 'utf8'");
        $Itemas = mysql_query($querys, $conexion);
        while($row = mysql_fetch_assoc($Itemas)){ 

            extract($row);
            $id_articulo_tienda = $row['id_articulo_tienda'];
            $unidades_articulo = $row['unidades_articulo'];
            $precio_compra_articulo = $row['precio_compra_articulo'];
            $tipo_de_joya = $row['tipo_de_joya'];
    $kilate_joya = $row['kilate_joya'];
    $descripcion_articulo = $row['descripcion_articulo'];
    $peso_joya = $row['peso_joya'];
    $peso_real_joya = $row['peso_real_joya'];
    $precio_compra_articulo = $row['precio_compra_articulo'];
    $descripcion_piedras_joya = $row['descripcion_piedras_joya'];
    $active_piedras_joya = $row['active_piedras_joya'];
    $active_inscripciones_joya = $row['active_inscripciones_joya'];
    $inscripciones_joya = $row['inscripciones_joya'];
    $merma_joya = $row['merma_joya'];
    
    if($active_inscripciones_joya == 'true'){
        $active_inscripciones_joya = "SI";
        $inscripciones_joya = $row['inscripciones_joya'];
    }else{
        $active_inscripciones_joya = "NO";
        $inscripciones_joya = "-----";
    }
    
    if($active_piedras_joya == 'true'){
        $active_piedras_joya = "SI";
        $descripcion_piedras_joya = $row['descripcion_piedras_joya'];
    }else{
        $active_piedras_joya = "NO";
        $descripcion_piedras_joya = "-----";
    }
    
    if($tipo_de_joya == 'oro'){
        if(empty($kilate_joya)){
            $tipo_de_joya = "Oro";
        }else{
            $tipo_de_joya = "Oro (".$kilate_joya." kilates)";
        }
    }else{
        if(empty($kilate_joya)){
            $tipo_de_joya = "Plata";
        }else{
            $tipo_de_joya = "Plata (Ley ".$kilate_joya.")";
        }
    }

    if(empty($merma_joya)){
            $merma_joya = "----";
        }
    
            $html .='
            <tr class="titulocolumna" >
        <td>'.$id_articulo_tienda.'</td>
        <td style="text-align: left; padding-left: 15px;">
            <p><strong>'.$descripcion_articulo.'</strong></p>
            
            <table width="100%" border="0" cellspacing="0" cellpadding="0" style="font-size: 12px; padding-bottom: 15px;">
              <tbody>
                <tr>
                  <td><strong>Tipo:</strong> '.$tipo_de_joya.'</td>
                  <td><strong>Peso neto:</strong> '.$peso_real_joya.'</td>
                </tr>
                <tr>
                  <td><strong>Peso bruto:</strong> '.$peso_joya.'</td>
                  <td><strong>Merma:</strong> '.$merma_joya.'</td>
                </tr>
                <tr>
                  <td><strong>Posee piedras:</strong> '.$active_piedras_joya.'</td>
                  <td><strong>Descripción piedras:</strong> '.$descripcion_piedras_joya.'</td>
                </tr>
                <tr>
                  <td><strong>Grabaciones:</strong> '.$active_inscripciones_joya.'</td>
                  <td><strong>Descripción grabaciones:</strong> '.$inscripciones_joya.'</td>
                </tr>
              </tbody>
            </table>
            
        </td>
        <td>'.$unidades_articulo.'</td>
        <td>'.$precio_compra_articulo.' €</td>
    </tr>
                 ';
        }

    $html .='
        </table>

        <table width="100%" border="0" cellpadding="0" cellspacing="0" class="datos">

            <tr>
                <td><br><br></br>
                <p class="condiciones" >
                Recibo en este acto por la venta de los artículos que se detallan a continuación y DECLARO BAJO JURAMENTO que son de mi propiedad y no proceden de operación ilegal penada en REGLAMENTOS O LEYES VIGENTES, quedando '.$nombreempresa.', CIF: '.$ciftienda.' salvo de cualquier reclamación si la hubiere y de lo cual me hago absolutamente responsable.
                </p>
                <br>
                </td>
            </tr>
           
            <tr>
                <td width="100%" height="60" style=" text-align: center; font-size:26px; ">
                    <table width="100%" border="0" cellspacing="0" cellpadding="0" style=" text-align: center; font-size:26px; " >
                        <tr style=" text-align: center; font-size:26px; " >
                            <td width="45%" height="80" style=" text-align: center; font-size:26px; " >
                                <strong>Importe</strong><br>'.$preciocompra.'
                            </td>
                            <td width="45%" height="80" style=" text-align: center; font-size:26px; " >
                                <strong>Fecha de venta</strong><br>'.$fechacontrato.'
                            </td>
                        </tr>
                    </table>
                </td>        
            </tr>
            
            <tr>
                <td>
                    <table width="100%" border="0" cellspacing="0" cellpadding="0">
                        <tr>
                            <td width="45%" height="80" style=" text-align: center; " >
                                <p class="datos"><strong>Comprador</strong></p>'.$signatureInsert_user.'
                            </td>
                            <td width="45%" height="80" style=" text-align: center; " >
                                <p class="datos"><strong>Venededor</strong></p>'.$signatureInsert.'
                            </td>
                        </tr>
                    </table>
                </td>        
            </tr>

        </table>
        <br/>
        ';
        $querysas = " SELECT nombre_foto FROM fotos_clientes WHERE id_cliente_foto = $id_cliente AND sucursal = $sucursal LIMIT 2 ";
        mysql_query ("SET NAMES 'utf8'");
        $Itemasas = mysql_query($querysas, $conexion);
        while($rowas = mysql_fetch_assoc($Itemasas)){ 
        extract($rowas); 
            $nombre_foto = $rowas['nombre_foto'];
            $link_foto = $foto_dni_cliente.$nombre_foto;
            $html .='
            <img src="'.$link_foto.'" width="350" height="auto">
            ';

         }
$html .='
    </div>
</body>
</html>
';
$mpdfone->WriteHTML($html);
//$mpdfone->Output(''.$titulodocumento.' Nº '.id_document.'.pdf','D');
$mpdfone->Output('../pdfs/docs/'.$titulodocumento.'-'.$currentyear.'-'.$id_document.'-'.$number_aleatorio.'.pdf','F');
//$mpdfone->Output();
?>