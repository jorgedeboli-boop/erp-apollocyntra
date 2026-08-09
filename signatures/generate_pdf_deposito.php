<?php
    
$identificador_deposito = $iditem;

$query = "SELECT * FROM depositos LEFT JOIN clientes ON depositos.cliente_deposito = clientes.id_cliente WHERE depositos.sucursal_deposito LIKE '".$sucursal."' AND identificador = '".$identificador_deposito."'";

mysql_query ("SET NAMES 'utf8'");
$Item = mysql_query($query, $conexion);
$rsItem = mysql_fetch_assoc($Item);
$idl=$rsItem['id_deposito'];
$id_document=$rsItem['id_deposito'];
$numerocontrato=$rsItem['id_deposito'];

$nombreapellidocliente=$rsItem['nombre']." ".$rsItem['apellido'];
$direccioncliente=$rsItem['direccion'];
$codigospotalcliente=$rsItem['codigo_postal'];
$ciudadcliente=$rsItem['poblacion'];
$provinciacliente=$rsItem['provincia'];
$telefonocliente=$rsItem['telefono'];
$dnicliente=$rsItem['identificacion'];
$contratopol = $rsItem['idpol'];
$date= $rsItem['fecha_deposito'];
$fechacontrato=date('d-m-Y',strtotime($date));
$date= $rsItem['fecha_vencimiento_deposito'];
$datevencimiento=date('d-m-Y',strtotime($date));
$preciocompra = $rsItem['precio_deposito'];
$user_generate = $rsItem['depositado_por'];

//LLAMO AL PORCENTAJE BONOTIENDA
$queryporc = "SELECT * FROM porcentaje_deposito_vendedor WHERE sucursal_porcentaje_vendedor = '".$sucursal."' ";
$Itemporc = mysql_query($queryporc, $conexion);
mysql_query ("SET NAMES 'utf8'");
$rsItemporc = mysql_fetch_assoc($Itemporc);
$pdep=$rsItemporc['porcentaje_deposito_vendedor'];
$meses=$rsItemporc['meses'];

//LLAMO AL PORCENTAJE BONOTIENDA
$query = "SELECT valor_dias_vencimiento_depositos FROM dias_vencimiento_depositos WHERE sucursal_dias_vencimiento_depositos = $sucursal ";
$Item = mysql_query($query, $conexion);
mysql_query ("SET NAMES 'utf8'");
$rsItem = mysql_fetch_assoc($Item);
$valor_dias_vencimiento_depositos=$rsItem['valor_dias_vencimiento_depositos'];


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
    $signature_value = signaturesManager("view", "", "", $identificador_deposito, "deposito");
    if(!empty($signature_value)){
        $textSignature = $nombreapellidocliente;
        $signatureInsert = generateSignatureContrato( $signature_value, $textSignature );
    } 
}

include("../MPDF54/mpdf.php");
$mpdfone=new mPDF('win-1252','A4','','',5,5,30,21,5,5); 
$mpdfone->useOnlyCoreFonts = true;    // false is default
$mpdfone->SetProtection(array('print'));
$mpdfone->SetTitle("Deposito ".$nameCompany."");
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
            <span style="text-align:center; float: right; font-size: 18px; " ><strong>Contrato Nº '.$id_document.'</strong></span>
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
               <td><p class="titulos">Contrato de consignación de mercancías</p></td>
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
               <td>(en adelante el "Consignatario")</td>
               <td>(en adelante el "Comitente")</td>
             </tr>
             <tr>
               <td>&nbsp;</td>
               <td>&nbsp;</td>
             </tr>
             <tr>
                    <td colspan="2"><p class="condiciones">El Consignatario y el Comitente, celebran mediante este documento Contrato de Consignación de Mercancías, regido por las siguientes cláusulas:  
        <br /><br />
        1.	El Comitente se compromete a entregar al Consignatario, la siguiente mercancía de su propiedad, en consignación para su venta, lo cual acepta el Consignatario. <br>La mercancía entregada en consignación es la siguiente:</p>			</td>
             </tr>
         </table>

         <table width="100%" border="1" cellpadding="0" cellspacing="0" class="datos">
             <tr class="titulocolumna">
               <td class="titulocolumna">Descripción</td>
               <td class="titulocolumna" width="180">Nº Serie</td>
               <td class="titulocolumna" width="50">Uds.</td>
               <td class="titulocolumna" width="150">Importe unitario</td>
             </tr>
        ';

        $querys = "SELECT descripcion_articulo, numero_serie, precio_venta_articulo, precio_compra_articulo, COUNT(*) FROM stock WHERE articulo_deposito = $idl AND stock.sucursal=$sucursal GROUP BY stock.descripcion_articulo  ";
        mysql_query ("SET NAMES 'utf8'");
        $Itemas = mysql_query($querys, $conexion);
        while($row = mysql_fetch_assoc($Itemas)){ 

            extract($row);
            $descripcion_articulo = $row['descripcion_articulo'];
            $numero_serie = $row['numero_serie'];
            $total = $row['COUNT(*)'];
            $precio_compra_articulo = $row['precio_compra_articulo'];
            $html .='
            <tr class="titulocolumna_lighter">
                   <td class="titulocolumna_lighter">'.$descripcion_articulo.'</td>
                   <td class="titulocolumna_lighter">'.$numero_serie.'</td>
                   <td class="titulocolumna_lighter">'.$total.'</td>
                   <td class="titulocolumna_lighter">'.$precio_compra_articulo.' €</td>
                 </tr>
                 ';
        }

    $html .='
        </table>

        <table width="100%" border="0" cellpadding="0" cellspacing="0" class="datos">

            <tr>
                <td><br><br></br>
                    <p class="condiciones" >
                    2.	Este contrato tiene una vigencia de '.$valor_dias_vencimiento_depositos.' días; vencido dicho término, la mercancía consignada en poder del Consignatario deberá ser devuelta al Comitente en las mismas condiciones de conservación en que fue recibida. <br>Una vez vencido el presente contrato, el Comitente dispondrá de un plazo de 1 mes para retirar la mercancía de su propiedad. En caso de no ser retirado en dicho periodo, el Consignatario no se hará cargo del robo, pérdida o daños ocasionados a la misma. No obstante, transcurridos los citados '.$valor_dias_vencimiento_depositos.' días, el comitente podrá ampliar en 1 mes la vigencia del contrato, reduciendo el precio de venta.
                    <br>

                    3. La comisión a percibir por el Consignatario será la que se expresa en la columna "Comisión" de cada artículo, i.v.a. incluido. El valor restante que corresponde al valor de la mercancía, será entregado al Comitente. 
                    <br>

                    4. El Consignatario deberá avisar al Comitente de cualquier daño, avería o sustracción, etc., que sufra la mercancía de propiedad de este último.<br>
                    El Consignatario responde por la buena conservación de la mercancía consignada, salvo por fuerza mayor o caso fortuito o vicio inherente a la cosa.
                    <br>
                    5. El Consignatario deberá cumplir con todas las normas legales sobre gravámenes de impuestos, tasas, etc., en las negociaciones que realice con la mercancía consignada. 
                    <br>
                    6. Recepción de productos de lunes a mièrcoles de 10:00 a 15:00h.
                    Los productos deberán estar en buen estado y funcionamiento.
                    <br>
                    7. Todos los artículos serán expuestos un máximo de 3 semanas, de no ser vendidos podrán retirar su articulo sin coste alguno.
                    <br>
                    8. Los artículos no vendidos y no retirados en el plazo de 1 mes desde su entrada en el local estarán sujetos a abonar 1€ por día pasado o perderàn el artículo.
                    <br>
                    9. Los artículos a la venta permanecen bajo la responsabilidad de los vendedores. '.$nombreempresa.' no se hace responsable de daños, robos, fuego, etc de productos que no tengan factura de compra.
                    <br>
                    10. Al precio de venta se retendrá el '.$pdep.'% de comisión + IVA de la misma.
                    <br>
                    11. Recoge tu dinero a partir de lunes siguiente, rápido y sencillo.
                    <br>
                    12. Los vendedores que no retiren su dinero en un plazo de 3 meses perderán el importe.
                    </p> 
                    <br>
                </td>
            </tr>
            
            <tr>
                <td width="100%" height="60" style=" text-align: center; font-size:20px; ">
                    <strong>En '.$ciudadtienda.', a  '.$fechacontrato.'</strong>
                </td>        
            </tr>
           
           
            <tr>
                <td>
                    <table width="100%" border="0" cellspacing="0" cellpadding="0">
                        <tr>
                            <td width="45%" height="80" style=" text-align: center; " >
                                <p class="datos"><strong>Consignatario</strong></p>'.$signatureInsert_user.'
                            </td>
                            <td width="45%" height="80" style=" text-align: center; " >
                                <p class="datos"><strong>Comitente</strong></p>'.$signatureInsert.'
                            </td>
                        </tr>
                    </table>
                </td>        
            </tr>

        </table>

    </div>
</body>
</html>
';
$mpdfone->WriteHTML($html);
//$mpdfone->Output(''.$titulodocumento.' Nº '.id_document.'.pdf','D');
$mpdfone->Output('../pdfs/docs/'.$titulodocumento.'-'.$currentyear.'-'.$id_document.'-'.$number_aleatorio.'.pdf','F');
//$mpdfone->Output();
?>