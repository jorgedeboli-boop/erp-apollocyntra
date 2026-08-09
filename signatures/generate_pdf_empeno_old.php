<?php
    
$identificador_compra = $iditem;

$query = "SELECT * FROM compras LEFT JOIN clientes ON compras.cliente = clientes.id_cliente WHERE compras.sucursal_compra LIKE '".$sucursal."' AND identificador = '".$identificador_compra."'";

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
$user_generate = $rsItem['comprado_por'];
$date= $rsItem['fecha_vencimiento'];
$datevencimiento=date('d-m-Y',strtotime($date));
$preciorecompra = $rsItem['precio_recompra'];
$preciocompra = $rsItem['precio_compra'];
$difrecompra = $rsItem['precio_recompra']-$rsItem['precio_compra'];
$difrecompraiva = $difrecompra * 21 / 100;
$preciorecomprasiniva = $preciorecompra-$difrecompraiva;

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
    $signature_value = signaturesManager("view", "", "", $identificador_compra, "empeno");
    if(!empty($signature_value)){
        $textSignature = $nombreapellidocliente;
        $signatureInsert = generateSignatureContrato( $signature_value, $textSignature );
    } 
}

include("../MPDF54/mpdf.php");
$mpdfone=new mPDF('win-1252','A4','','',5,5,30,21,5,5); 
$mpdfone->useOnlyCoreFonts = true;    // false is default
$mpdfone->SetProtection(array('print'));
$mpdfone->SetTitle("Empeño ".$nameCompany."");
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
            <span style="text-align:center; float: right; font-size: 18px; " ><strong>Compra Nº '.$id_document.'</strong></span>
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
               <td><p class="titulos">Contrato de venta recuperable</p></td>
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
               <td>(en adelante COMPRADOR)</td>
               <td>(en adelate VENDEDOR)</td>
             </tr>
             <tr>
               <td>&nbsp;</td>
               <td>&nbsp;</td>
             </tr>
             <tr>
                    <td colspan="2">
                        <p class="condiciones">Vencimiento: '.$datevencimiento.'</span> Este contrato podrá ser renovado por un periodo de 30 días. Sujeto a condiciones de contratación</p>
                    </td>
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

        $querys = "SELECT descripcion_articulo, numero_serie, precio_compra_articulo FROM stock WHERE articulo_compra = $idl AND stock.sucursal=$sucursal   ";
        mysql_query ("SET NAMES 'utf8'");
        $Itemas = mysql_query($querys, $conexion);
        while($row = mysql_fetch_assoc($Itemas)){ 

            extract($row);
            $descripcion_articulo = $row['descripcion_articulo'];
            $numero_serie = $row['numero_serie'];
            $total = "1";
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
                <td></br>
                <p class="condiciones">Las partes se reconocen  mutua capacidad para celebrar y obligarse a tal efecto. Por el presente documento el VENDEDOR declara que:</p>
                <p class="condiciones" style="text-indent:60px; line-height:0px" >1.	Dicho bien pertenece al VENDEDOR en propiedad y con plenas facultades para enajenarlo, sin que esté afectado por ningún tipo de traba, garantía o embargo y sin</p>
                <p class="condiciones" style="text-indent:60px; line-height:0px" >que existiera ninguna carga o gravamen sobre el mismo.<p/>
                <p class="condiciones" style="text-indent:60px; line-height:0px" >2.	El referido bien se encuentra en perfecto estado de uso y conservación acordes con su naturaleza y antigüedad.<p/>
                <p class="condiciones" style="text-indent:60px; line-height:0px" >3.	Asume el compromiso de reembolsar al COMPRADOR(en el plazo máximo de 30 días naturales desde que fuese requerido para ello) el importe del precio de</p>
                <p class="condiciones" style="text-indent:60px; line-height:0px" >compra estipulado así como los daños y perjuicios, incluido el lucro cesante, que pudieran derivarse contra el COMPRADOR, en el  supuesto de que lo manifestado</p> 
                <p class="condiciones" style="text-indent:60px; line-height:0px" >anteriormente fuere incorrecto o inexacto, y se produjese cualquier tipo de reclamación de terceros sobre el bien transmitido en el presente contrato o este fuese</p> 
                <p class="condiciones" style="text-indent:60px; line-height:0px" >inservible para su uso ordinario por causas imputables al VENDEDOR.</p>

                <p class="condiciones">
                Ambas partes acuerdan la celebración de un contrato de compraventa recuperable, lo que llevan a efecto conforme a las siguientes condiciones:
                <br>Primera. El VENDEDOR vende y hace entrega en este acto al COMPRADOR quien compra, recibe y adquiere el bien antes descrito en el precio y condiciones previamente negociadas.
                <br>Segunda. No obstante, el COMPRADOR concede al VENDEDOR la facultad de recomprar dicho bien; facultad que deberá ejercitarse antes del cierre de la tienda del día '.$datevencimiento.'
                <br>Tercera. La facultad de recomprar el bien se otorga a petición del VENDEDOR, manifestando éste que, dicha solicitud no se debe a dificultades económicas. El COMPRADOR acepta que el VENDEDOR pueda recomprar el bien en función de la veracidad de esa manifestación.
                <br>Cuarta. Si el VENDEDOR ejercitase el derecho a recomprar el bien en el plazo estipulado, las partes convienen que el precio de la recompra es de <strong>#'.$preciorecompra.' €#</strong> (de los que <strong>'.$difrecompra.' €</strong> son en concepto de gastos conforme el artículo 1507 Código Civil y <strong>'.$difrecompraiva.' €</strong> en concepto del IGIC)*. Salvo pacto expreso en contrario, el precio deberá abonarse en efectivo y simultáneamente a la recompra del bien por el VENDEDOR.
                <br>Quinta. El COMPRADOR ha adquirido el bien para revenderlo pero, no podrá exhibirlo en tienda para su reventa hasta que no haya vencido el plazo pactado para su recompra, o éste no haya sido prorrogado a instancias del VENDEDOR.
                <br>Sexta. Si ejercitada la facultad de recompra del bien por el VENDEDOR, el COMPRADOR, por cualquier circunstancia no imputable a él como robo, sustracción, etc., no pudiere entregárselo, aquél tendrá derecho al reembolso en efectivo del precio pactado para la recompra más el diez por ciento de dicho importe en concepto de daños y perjuicios.
                <br>Séptima. El bien va a pasar el control de la policía, por lo que no podrá ser recomprado hasta el cumplimiento de dicho trámite o, en su caso, obtener la autorización policial.
                <br>Octava. En el caso de que el/los producto/s vendidos puedan contener imágenes, vídeos y datos personales, así como software en él instalados: “El vendedor consiente y autoriza al comprador para que venda en su establecimiento el hardware con todo lo a él inherente, incluidos los datos o información de carácter personal que el dispositivo pudiera contener, así como los software instalados, afirmando que fueron adquiridos de una forma lícita; asumiendo ser el único responsable de su contenido hasta el día de hoy, eximiendo al comprador de toda responsabilidad que se pudiera derivar por este motivo”.
                <br /><br />Y en prueba de conformidad y aceptación firman el presente documento por duplicado, en el lugar y la fecha indicados.</p>

              
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
            
            <tr>
                <td>
                <p class="condiciones">Notas.
                 <br>USTED HA VENDIDO EL OBJETO REFERENCIADO, POR  LO QUE YA NO ES EL PROPIETARIO DE DICHO OBJETO.
                 <br>EL CLIENTE MANIFIESTA QUE HA SIDO INFORMADO ADECUADAMENTE DE LAS CARACTERISTICAS, TERMINOS Y DEMAS CONDICIONES DEL CONTRATO VENTA RECUPERABLE, COMPRENDE SU SIGNIFICADO Y LO ACEPTA.
                 <br>
                           *GASTOS: DE GESTION; CUSTODIA/ALMACENAMIENTO; SEGURIDAD
                 <br>De conformidad con lo dispuesto en la Ley Orgánica 15/1999 de Protección de Datos de Carácter Personal. '.$nameCompany.', en adelante "'.$nameCompany.'", con domicilio en '.$direcciontienda.','.$codigospotaltienda.' '.$ciudadtienda.' ('.$provinciatienda.'), le informa que los datos que nos ha proporcionado formarán parte del fichero responsabilidad de dicha entidad “Clientes Vendedores” debidamente protegido e inscrito ante la Agencia Española de Protección de Datos.
                 <br>
                           El tratamiento de sus datos tiene la finalidad de llevar a cabo la relación contractual vinculante, realizar gestiones administrativas, fiscales y contables y, eventualmente, enviarle información comercial sobre los productos comercializados, o sobre ofertas y descuentos preferentes.
                           Los datos de carácter personal incorporados en el fichero titularidad de '.$nameCompany.', podrán ser cedidos a las empresas del grupo o entidades relacionadas con '.$nameCompany.' siempre y  cuando dicha cesión se realice para las mismas finalidades que las establecidas en el párrafo anterior. Igualmente, sus datos serán cedidos a administraciones Públicas competentes según normativa vigente, para la comprobación de la legalidad de los productos adquiridos. En caso de que nos autorice le rogamos firme este documento.
                 <br>
                           En el supuesto de que desee ejercitar sus derechos de Acceso, Rectificación, Cancelación, Oposición o Revocación del consentimiento otorgado anteriormente, dirija una comunicación por escrito a '.$nameCompany.' a la dirección indicada anteriormente, acompañada de su DNI e indicando como referencia “Protección de Datos”

                           <br>'.$appWeb.'</p>
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