<?php

require("session_file.php");
//mostrarErrores();
?>

<?php
//require_once("conexion.php");
$idl = $_GET["lote"];
if ($usuario == 'mariaarias') {
  if (isset($_GET['sucursal'])) {
    $sucursal = $_GET['sucursal'];  
  }
}

$query = "SELECT * FROM lotes_$sucursal 
LEFT JOIN clientes ON lotes_$sucursal.cliente=clientes.id_cliente 
LEFT JOIN sucursal ON lotes_$sucursal.sucursal=sucursal.id_sucursal 
LEFT JOIN articulos_$sucursal ON lotes_$sucursal.id_lote=articulos_$sucursal.id_lote_articulos 
WHERE id_lote=$idl 
";

mysql_query ("SET NAMES 'utf8'");
$Item = mysql_query($query, $conexion);
$rsItem = mysql_fetch_assoc($Item);


$date= $rsItem['fecha_compra'];
$sqldate=date('d-m-Y',strtotime($date));

$datef= $rsItem['fecha_vencimiento'];
$sqldatef=date('d-m-Y',strtotime($datef));

$difrecompra = $rsItem['precio_recompra']-$rsItem['precio_compra'];

//final de consulta////
///////////////////////
//genero pdf///////////

include("../MPDF54/mpdf.php");



$mpdf=new mPDF('win-1252','A4','','',5,5,53,45,5,10); 
$mpdf->useOnlyCoreFonts = true;    // false is default
$mpdf->SetProtection(array('print'));
$mpdf->SetTitle("Contrato de empeño de oro");
$mpdf->SetAuthor("");
$mpdf->SetWatermarkText("Enviada");
$mpdf->showWatermarkText = false;
$mpdf->watermark_font = 'DejaVuSansCondensed';
$mpdf->watermarkTextAlpha = 0.1;
$mpdf->SetDisplayMode('fullpage');
$mpdf->hyphenate = true;
$mpdf->SHYlang = 'es';
$mpdf->ignore_invalid_utf8 = true;

$html = '
<html>
<head>
<meta charset="UTF-8" />
<style>
body {font-family: arial;
    font-size: 12pt;
	 width:900px;
	 margin:0 auto;
	 color:#000000;
}
p {    margin: 0pt;
}
td { vertical-align: top; }
.items td {
}
table thead td { background-color: #EEEEEE;
    text-align: center;
}
.items td.blanktotal {
    background-color: none;
    border: none;
}
.items td.totals {
    text-align: right;
}
</style>

</head>
<body>
';

$html .= '
<!--mpdf
<htmlpageheader name="myheader" >
<table width="100%"><tr>
<td width="55%">
<img src="../fotos/logotipo.svg" width="250" height="63">
</td>
<td width="45%" style="text-align: right;">
   <table width="100%" border="0" cellspacing="0" cellpadding="0">
	  <tr>
		 <td height="10"  style="vertical-align:middle; font-size:15px; font-weight: bold;">Contrato opción de compra</td>
	    </tr>
	  <tr>
	    <td height="10" style="vertical-align:middle; ">Fecha: <span style="font-weight: bold; font-size: 11pt;">'.$sqldate.'</span></td>
	    </tr>
	  <tr>
	    <td height="10" style="vertical-align:middle; ">Lote Nº <span style="font-weight: bold; font-size: 11pt;">'.$rsItem['id_lote'].'</span>';

    switch ($rsItem['estado_lote']) {
       case 'vencido':
         //var_dump($rsItem['estado_lote']);die();
         $html.= " - Vencido";
         break;

       case 'retirado':
         $html.= " - Retirado";
         break;
     }

    $html .='</td>
      </tr>
      </table>
    </td>
</tr>
</table>

<table width="100%" style="font-family: Arial; " cellpadding="5">
<tr>
<td width="30%" style=""><span style="font-size: 9pt;  font-family: arial; background-color: #EEEEEE; padding:5px;">&nbsp;El comprador&nbsp;</span><br /><br />'.$rsItem['empresa'].'<br />'.$rsItem['identificacion_tienda'].'  '.$rsItem['numero_identificacion_tienda'].'<br />'.$rsItem['direccion_tienda'].'<br /></td>
<td width="30%" ><span style="font-size: 9pt;  font-family: arial; padding:5px;">&nbsp;&nbsp;</span><br /><br />'.$rsItem['poblacion_tienda'].', '.$rsItem['codigo_postal_tienda'].', '.$rsItem['provincia_tienda'].'<br />Tel.: '.$rsItem['telefono_tienda'].'<br />Móvil.: '.$rsItem['movil_tienda'].'</td>
<td width="10%">&nbsp;</td>
<td width="40%" style=""><span style="font-size: 9pt;  font-family: arial; background-color: #EEEEEE; padding:5px;">&nbsp;El vendedor&nbsp;</span><br /><br />
'.$rsItem['nombre'].' '.$rsItem['apellido'].'<br />'.$rsItem['tipo_identificacion'].' '.$rsItem['identificacion'].'<br />'.$rsItem['direccion'].'</td>
<td width="30%" style=""><span style="font-size: 9pt;  font-family: arial;  padding:5px;">&nbsp;&nbsp;</span><br /><br />'.$rsItem['c_poblacion'].', '.$rsItem['codigo_postal'].', '.$rsItem['c_provincia'].'
<br />Tel.: '.$rsItem['telefono'].' <br />Móvil.: '.$rsItem['movil'].' 
</td>
</tr>
<tr>
<td colspan="5"><span style="font-size: 9pt;  font-family: arial;">Datos de la venta. El vendedor reconoce que los artículos que se detallan a continuación son de su legítima propiedad.</span></td>
</tr>
</table>

</htmlpageheader>

<sethtmlpageheader name="myheader" value="on" show-this-page="1"  />
<htmlpagefooter name="footer">
<table class="items" width="100%" style="font-size: 9pt; border-collapse: collapse;" cellpadding="8">
<tr>
  <td width="33%">El comprador:</td>
  <td width="33%">El vendedor:</td>
  <td width="33%">Lote retirado:</td>
</tr>
</table>
<br /><br /><br />
<div style="text-align: justify; font-style: italic; margin:15px 0 15px 0;font-size:5pt;">Declaro que las piezas aquí detalladas, son de mi propiedad lícita y se hallan libres de toda carga. Doy conformidad con el precio fijado por las piezas que vendo, y que se encuentran arriba descritas.</div>
<div style="text-align: justify; font-style: italic; margin:20px 0 0 0; font-size:6pt; ">CLAUSULAS APLICABLES A LAS OPERACIONES DE COMPRA DE ORO.
Desde la firma del presente contrato, la parte vendedora no podrá reclamar la pieza o piezas vendidas detalladas anteriormente. Siendo desde este momento propiedad de la parte compradora.
<br /><br />
<strong>EN ESTE CONTRATO USTED ESTA VENDIENDO EL OBJETO REFERENCIADO  DEL QUE YA NO ES PROPIETARIO.</strong><br />';

switch($sucursal){
	//ESTER JOYERIA
	case 51:
	case 3:
	case 41:
	case 4:
	case 25:
	case 24:
	case 23:
	case 16:
	case 14:
	case 17:
	case 18:
		$html .= 'De acuerdo con los derechos que le confiere la normativa vigente y aplicable en protección de datos podrá
				ejercer los derechos de acceso, rectificación, limitación de tratamiento, supresión (“derecho al olvido”),
				portabilidad y oposición al tratamiento de sus datos de carácter personal así como la revocación del
				consentimiento prestado para el tratamiento de los mismos, dirigiendo su petición a la dirección C/Goya, 127 
				28009, MADRID o al correo electrónico esjollerias19@gmail.com. Podrá dirigirse a la Autoridad de
				Control competente para presentar la reclamación que considere oportuna.<br /><br />
				Si desea ampliar la información sobre los procedimientos y protocolos adoptados por ESTER JOYERIA 2019 SL,
				le informamos, que puede contactar con el Delegado de Protección de Datos, dirigiéndose por escrito a la
				dirección de correo dpd.cliente@conversia.es o al teléfono 902 877 192.</div>';
		break;
	//AYALA RECICLADOS
	case 22:
	case 21:
	case 20:
	case 15:
		$html .= 'De acuerdo con los derechos que le confiere la normativa vigente y aplicable en protección de datos podrá
				ejercer los derechos de acceso, rectificación, limitación de tratamiento, supresión (“derecho al olvido”),
				portabilidad y oposición al tratamiento de sus datos de carácter personal así como la revocación del
				consentimiento prestado para el tratamiento de los mismos, dirigiendo su petición a la dirección C/Pensamiento 27, 3º Esc. Izda. 
				28020, MADRID o al correo electrónico ayalareciclados16@hotmail.com. Podrá dirigirse a la Autoridad de
				Control competente para presentar la reclamación que considere oportuna.<br /><br />
				Si desea ampliar la información sobre los procedimientos y protocolos adoptados por AYALA RECICLADOS 16 SLU,
				le informamos, que puede contactar con el Delegado de Protección de Datos, dirigiéndose por escrito a la
				dirección de correo dpd.cliente@conversia.es o al teléfono 902 877 192.</div>';
		break;
	//NANOPAC
	case 47:
		$html .= 'De acuerdo con los derechos que le confiere la normativa vigente y aplicable en protección de datos podrá
				ejercer los derechos de acceso, rectificación, limitación de tratamiento, supresión (“derecho al olvido”),
				portabilidad y oposición al tratamiento de sus datos de carácter personal así como la revocación del
				consentimiento prestado para el tratamiento de los mismos, dirigiendo su petición a la dirección Avda. Brasil, 6 Planta 1 
				28020, MADRID o al correo electrónico nanopacjoyeriaydsl@gmail.com. Podrá dirigirse a la Autoridad de
				Control competente para presentar la reclamación que considere oportuna.<br /><br />
				Si desea ampliar la información sobre los procedimientos y protocolos adoptados por NANOPAC JOYERIA Y DISEÑO SL,
				le informamos, que puede contactar con el Delegado de Protección de Datos, dirigiéndose por escrito a la
				dirección de correo dpd.cliente@conversia.es o al teléfono 902 877 192.</div>';
			break;
	//OPELIA
	case 50:
		$html .= 'De acuerdo con los derechos que le confiere la normativa vigente y aplicable en protección de datos podrá
				ejercer los derechos de acceso, rectificación, limitación de tratamiento, supresión (“derecho al olvido”),
				portabilidad y oposición al tratamiento de sus datos de carácter personal así como la revocación del
				consentimiento prestado para el tratamiento de los mismos, dirigiendo su petición a la dirección C/Velázquez 53, 2º Izda 
				28001, MADRID o al correo electrónico opeliaservices@gmail.com. Podrá dirigirse a la Autoridad de
				Control competente para presentar la reclamación que considere oportuna.<br /><br />
				Si desea ampliar la información sobre los procedimientos y protocolos adoptados por OPELIA SERVICES SL,
				le informamos, que puede contactar con el Delegado de Protección de Datos, dirigiéndose por escrito a la
				dirección de correo dpd.cliente@conversia.es o al teléfono 902 877 192.</div>';
		break;
}

$html .= '
</htmlpagefooter>
<sethtmlpagefooter name="footer" value="on" />

mpdf-->
';

$html .= '
<div style=" height:auto;">
<table class="items" width="100%" style=" font-size: 11px; border-collapse: collapse;" cellpadding="8">
<thead>
<tr>
  <td width="5%">Nº</td>
  <td width="5%">Us.</td>
  <td width="65%" align="left">Descricpión de los artículos</td>
  <td width="25%">Inscripciones</td>
</tr>
</thead>
<tbody>
';

$querys = "SELECT * FROM articulos_$sucursal WHERE id_lote_articulos =$idl ";
mysql_query ("SET NAMES 'utf8'");
$Itemas = mysql_query($querys, $conexion);
while($row = mysql_fetch_assoc($Itemas)){ 
extract($row); 

$html .= '<tr>
<td height="10" align="center">'.$id_articulo_lote.'</td>
<td align="center">'.$unidades.'</td>
<td>'.$descripcion_articulo.' ('.$tipo_de_articulo.' '.$ley.')</td>
<td>'.$inscripciones.'</td>
</tr>';
}

$html .= '
<!-- END LISTO ITEMS -->
</tbody>
</table>
</div>';

$quer = "SELECT * FROM articulos_$sucursal WHERE id_lote_articulos =$idl ";
mysql_query ("SET NAMES 'utf8'");
$Its = mysql_query($quer, $conexion);
while ($rItem = mysql_fetch_assoc($Its))
$smatoria+=$rItem['unidades'];
$interestotales = $rsItem['precio_recompra']-$rsItem['precio_compra'];
$html .= '
<!-- SUMATORIA -->
<table class="items" width="100%" style=" font-size: 9pt; border-collapse: collapse; margin-bottom:15px;" cellpadding="8">
<tr>
<td width="4%" height="20" align="center" style="background-color: #EEEEEE;">Total</td>
<td width="4%" align="center" style="background-color: #EEEEEE;">'.$smatoria.'</td>
<td width="65%">&nbsp;</td>
<td width="25%">&nbsp;</td>
</tr>
</table>
<!-- END SUMATORIA -->

<table class="items" width="100%" style="font-size: 9pt; border-collapse: collapse;" cellpadding="5">
<thead>
<tr>
  <td width="15%">Gramos</td>
  <td width="15%">Importe</td>
  <td width="25%">Vencimiento</td>
  <td width="15%">Importe recompra</td>
  <!--<td width="20%">Interés</td>-->
</tr>
</thead>
<tbody>
<!-- ITEMS HERE -->
<tr>
<td align="center">'.$rsItem['peso'].' grs</td>
<td align="center">'.$rsItem['precio_compra'].' €</td>
<td align="center">'.$sqldatef.'</td>
<td align="center">'.$rsItem['precio_recompra'].' €</td>
<!--<td align="center">'.$interestotales.' €</td>-->
</tr>
<!-- END ITEMS HERE -->
<tr>
<td class="blanktotal" colspan="3"></td>
</tr>
</tbody>
</table>

<div style="text-align: justify; font-style: italic; margin:0 0 15px 0;font-size:7pt;line-height:150%;">
Declara que las piezas aquí detalladas, son de mi propiedad lícita y se hallan libres de toda carga. Doy conformidad con el precio fijado por las piezas que vendo, y que se encuentran arriba descritas. Las partes se reconocen mutua capacidad para celebrar y obligarse a tal efecto. Por el presente documento el VENDEDOR declara que 1.- Dicho bien pertenece al VENDEDOR que no procede de acto ilícito y que no está afectado por ningún tipo de traba, garantía o embargo sobre el mismo. 2.- Que el referido bien se encuentra en perfecto estado de uso y conservación acorde con su naturaleza y antigüedad. 3.- Que el VENDEDOR asume el compromiso de reembolsar al COMPRADOR en el plazo estipulado en este contrato el importe de recompra acordado así como los daños y perjuicios, incluido el lucro cesante, que pudiera derivarse contra el VENDEDOR en el caso que lo manifestado anteriormente fuere incorrecto o inexacto produciéndose cualquier tipo de reclamación de terceros sobre el bien transmitido en el presente contrato o este fuera inservible para el uso ordinario por causas imputables al VENDEDOR. 

Ambas partes acuerdan la celebración de un contrato de compra y venta con pacto de opción a recompra, lo que llevan a efecto conforme a las siguientes condiciones: 1. El VENDEDOR vende al COMPRADOR el elemento descrito en precio y condiciones arriba señalados sirviendo el presente documento de total y eficaz carta de pago. 2. La presente compra y venta no está sujeta a ningún tipo especial de condición por la que desde ahora es plenamente válida y eficaz, entendiendo el VENDEDOR que ya no es propietario de los bienes a ningún efecto. 3. El COMPRADOR concede al VENDEDOR una opción de compra sobre el o los artículos antes señalados la cual deberá ejecutarse antes de la fecha de vencimiento anteriormente concretada. Si la fecha de vencimiento coincidiera con un Domingo o festivo el COMPRADOR deberá ejercitar la opción de recompra como máximo, el día laborable inmediatamente anterior, al de la finalización del contrato. Transcurrido el plazo, quedará sin efecto la opción, cancelando automáticamente sin necesidad de notificación de ninguna clase. 4. La presente opción de compra se otorga a petición y como facultad al VENDEDOR, quien acepta libremente con pleno conocimiento de sus consecuencias y sin que en su decisión influya una situación económica tal que le obligue a usar esta opción como alternativa a los medios ordinarios de crédito, asumiendo que el comprador ofrece la opción basada en estas manifestaciones. 5. El VENDEDOR optante podrá ceder su derecho de opción por cualquier título sin necesidad de aprobación por parte del COMPRADOR. No obstante, deberá poner, personalmente, en conocimiento del COMPRADOR, el hecho de la cesión y las circunstancias personales del tercero adquiriente, dejando constancia escrita de su cesión y notificación al COMPRADOR. Ejecutada la cesión, el cesionario como ejercita el mismo derecho de cedente, no podrá exigir del COMPRADOR la garantía regulada en la Ley 23/2003, al quedar excluido el derecho de opción cedido de lo establecido en dicha ley. 6. Salvo pacto expreso en contrario el precio de venta del bien (ya anteriormente concretado) deberá de hacerse efectivo en el momento de ejercitar la opción. 7. El COMPRADOR se obliga por su parte a no transmitir el objeto sujeto a la opción durante el plazo de vigencia de la misma y a mantenerlo en el mismo estado de uso y conservación en el que fue entregado. Si por cualquier circunstancia no imputable al COMPRADOR, especialmente por pérdida, deterioro o substracción, no fuese posible transmitirle los objetos sujetos a la operación de compra, el VENDEDOR tendrá derecho a ser reembolsado por las cantidades que hubiese podido satisfacer al COMPRADOR por el derecho a esta opción de compra o posibles prorrogas, junto con el equivalente al diez por ciento del precio de compra satisfecho por el COMPRADOR al adquirir este objeto. 8. Ese producto pasará controles y el caso de ser intervenido policial o judicialmente, no podrá ser recuperado hasta tener la autorización de los correspondientes entes. 9. El tiempo mínimo de retirada lo marcan los Cuerpos y Fuerzas de Seguridad del Estado y oscilan entre los 15 y 30 días en los casos habituales, pudiendo ser este plazo superior en casos excepcionales. 10. En caso de robo en nuestras instalaciones nos veremos obligados a pagar el 10% del valor de recompra a los beneficiarios de los contratos de recompra. 
Si el vendedor quisiera renovar la opción de compra deberá proceder con anterioridad a la finalización de la fecha de vencimiento a comunicarlo al comprador, en cuyo caso el artículo objeto de venta quedará en deposito debiendo el vendedor abonar al comprador los gastos originados por la guarda y custodia del artículo objeto de venta que ascenderán al 10%, impuestos incluidos, de la valoración dada al citado artículo. 

Si alguna de las partes quisiera resolver dicha renovación de la opción deberá comunicarlo a la otra por medio de whatsapp, sms, correo electrónico o carta certificada con acuse de recibo, debiendo el vendedor abonar los importes debidos en concepto de gastos originados por el depósito, dando por cancelada la opción de compra. 


Y en prueba de conformidad y aceptación firman el presente documento por duplicado, en el lugar y la fecha indicada.

</div>

<div style="text-align: justify; font-style: italic; margin:5px 0 0 0; color:#000000; font-size:4pt;line-height:auto;">
<strong>Gestión económica y administrativa</strong>
Finalidad: Gestión administrativa, facturación, contabilidad y obligaciones legales.
Plazo de conservación: 5 años en cumplimiento de la ley tributaria y 10 años la
documentación fiscal en cumplimiento de la L.O. 7/2012.
Base legítima: El cumplimiento de una ley.
Cesiones: sus datos serán comunicados en caso de ser necesario a Agencia
Tributaria, Bancos, Cajas y Organismos y/o administración pública con
competencia en la materia con la finalidad de cumplir con las obligaciones
tributarias y fiscales establecidas en la normativa aplicable. Además, se informa
que la base legitimadora de la cesión es el cumplimiento de una ley.
  <br>  <br>
<strong>Gestión del cumplimiento normativo</strong>
Finalidad: Gestión y tramitación de las obligaciones y deberes que se deriven del
cumplimiento de la normativa a la cual está sujeta la entidad.
Plazo de conservación: conservación de las copias de los documentos hasta que
prescriban las acciones para reclamarle una posible responsabilidad.
Base legítima: El cumplimiento de una ley.
Cesiones: sus datos serán comunicados en caso de ser necesario a Organismos
y/o administración pública con competencia en la materia con la finalidad de cumplir
con las obligaciones establecidas en la normativa aplicable. Además, se informa
que la base legitimadora de la cesión es el cumplimiento de una ley.
</div>

</body>
</html>
';
$mpdf->WriteHTML($html);
//$mpdf->Output('Ficha Contrato Lote Nº '.$rsItem['id_lote'].'.pdf','D');
$mpdf->Output();
exit;
?>