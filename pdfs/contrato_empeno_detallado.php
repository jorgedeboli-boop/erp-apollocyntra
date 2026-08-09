<?php
require_once("conexion.php");
$idl = $_GET["empeno"];
$query = "SELECT * FROM empenos LEFT JOIN clientes ON empenos.cliente=clientes.id_cliente WHERE id_empeno=$idl ";

//LEFT JOIN sucursal ON empenos.sucursal=sucursal.nombre_sucursal 

mysql_query ("SET NAMES 'utf8'");
$Item = mysql_query($query, $conexion);
$rsItem = mysql_fetch_assoc($Item);


$date= $rsItem['fecha_compra'];
$sqldate=date('d-m-Y',strtotime($date));


$date= $rsItem['fecha_vencimiento'];
$sqlvdate=date('d-m-Y',strtotime($date));

$difrecompra = $rsItem['precio_recompra']-$rsItem['precio_compra'];

//Consulto sucursal
$querys = "SELECT * FROM sucursal WHERE nombre_sucursal= 'Barakaldo' ";
mysql_query ("SET NAMES 'utf8'");
$Ietem = mysql_query($querys, $conexion);
$rseItem = mysql_fetch_assoc($Ietem);

//final de consulta////
///////////////////////
//genero pdf///////////

include("../MPDF54/mpdf.php");

$mpdf=new mPDF('win-1252','A4','','',13,13,25,5,5,10); 
$mpdf->useOnlyCoreFonts = true;    // false is default
$mpdf->SetProtection(array('print'));
$mpdf->SetTitle("Silver Gold - Contrato de compra de oro");
$mpdf->SetAuthor("Silver Gold");
$mpdf->SetWatermarkText("Enviada");
$mpdf->showWatermarkText = false;
$mpdf->watermark_font = 'DejaVuSansCondensed';
$mpdf->watermarkTextAlpha = 0.1;
$mpdf->SetDisplayMode('fullpage');
$mpdf->hyphenate = true;
$mpdf->SHYlang = 'es';

$html = '
<html>
<head>
<meta charset="UTF-8" />
<style>
body {font-family: arial;
    font-size: 9pt;
	 width:900px;
	 margin:0 auto;
	 color:#666666;
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
		 <td height="10"  style="vertical-align:middle; font-size:15px; font-weight: bold;">Contrato de compra de opción</td>
	    </tr>
	  <tr>
	    <td height="10" style="vertical-align:middle; ">Fecha: <span style="font-weight: bold; font-size: 11pt;">'.$sqldate.'</span></td>
	    </tr>
	  <tr>
	    <td height="10" style="vertical-align:middle; ">Lote Nº <span style="font-weight: bold; font-size: 11pt;">'.$rsItem['id_empeno'].'</span></td>
	    </tr>
    </table>
    </td>
</tr>
</table>
</htmlpageheader>

<sethtmlpageheader name="myheader" value="on" show-this-page="1"  />

mpdf-->


<table width="100%" style="font-family: Arial; ma" cellpadding="5">
<tr>
<td width="20%" style=""><span style="font-size: 9pt;  font-family: arial; background-color: #EEEEEE; padding:5px;">&nbsp;El comprador&nbsp;</span><br /><br />Silver Gold<br />'.$rseItem['identificacion_tienda'].'  '.$rseItem['numero_identificacion_tienda'].'<br />'.$rseItem['direccion_tienda'].'<br /></td>
<td width="30%" ><span style="font-size: 9pt;  font-family: arial; padding:5px;">&nbsp;&nbsp;</span><br /><br />'.$rseItem['poblacion_tienda'].'<br />'.$rseItem['codigo_postal_tienda'].', '.$rseItem['provincia_tienda'].'<br />Tel.: '.$rseItem['telefono_tienda'].'</td>
<td width="10%">&nbsp;</td>
<td width="30%" style=""><span style="font-size: 9pt;  font-family: arial; background-color: #EEEEEE; padding:5px;">&nbsp;El vendedor&nbsp;</span><br /><br />
'.$rsItem['nombre'].'<br />'.$rsItem['tipo_identificacion'] .' '.$rsItem['identificacion'] .'<br />'.$rsItem['direccion'].'</td>
<td width="30%" style=""><span style="font-size: 9pt;  font-family: arial;  padding:5px;">&nbsp;&nbsp;</span><br /><br />'.$rsItem['c_poblacion'].'<br />'.$rsItem['codigo_postal'].', '.$rsItem['c_provincia'].'
<br />Tel.: '.$rsItem['telefono'].' / '.$rsItem['movil'].'</td>
</tr>
<tr>
<td colspan="5"><span style="font-size: 9pt;  font-family: arial;">
Datos de la venta. Declara que las piezas aquí detalladas, son de mi propiedad lícita y se hallan libres de toda carga. 
Doy conformidad con el precio fijado por las piezas que vendo, y que se encuentran arriba descritas.
Las partes se renonocen mutua capacidad para celebrar y obligarse a tal efecto. Por el presente documento
el VENDEDOR declara que 1.- Dicho bien pertenece al VENDEDOR que no procede de acto ilícito y que no está
afectado por ningún tipo de traba, garantia o embargo sobr el mismo. 2.- Que el referido bien se encuentra
en perfecto estado de uso y conservación acorde con su naturaleza y antigüedad.</span></td>
</tr>
</table>
';


$html .= '
<div style=" height:600px;">
<table class="items" width="100%" style=" font-size: 9pt; border-collapse: collapse;" cellpadding="8">
<thead>
<tr>
  <td width="8%">Ref. nº</td>
  <td width="8%">Cantidad</td>
  <td width="25%">Tipo de artículo</td>
  <td width="45%">Descripción</td>
  <td width="25%">N1 Serie / Matrícula</td>
</tr>
</thead>
<tbody>
';

$querys = "SELECT * FROM articulos_empenos WHERE id_lote_articulo_empeno = $idl ";
mysql_query ("SET NAMES 'utf8'");
$Itemas = mysql_query($querys, $conexion);
while($row = mysql_fetch_assoc($Itemas)){ 
extract($row); 

$html .= '
<!-- END LISTO ITEMS -->
<tr>
  <td width="8%">'.$id_articulo_empeno.'</td>
  <td width="8%">'.$unidades_articulo_empeno.'</td>
  <td width="25%">'.$tipo_de_articulo_empeno.'</td>
  <td width="45%">'.$descripcion_articulo_empeno.'</td>
  <td width="25%">'.$numero_de_serie.'</td>
</tr>
';
}
$html .= '
</tbody>
</table>
</div>';


$html .= '
<!-- SUMATORIA -->
<table class="items" width="100%" style=" font-size: 9pt; border-collapse: collapse; margin-bottom:15px;" cellpadding="8">
<tr>
<td width="8%" height="20" align="center" style="vertical-align:middle; background-color: none;">&nbsp;</td>
<td width="8%" align="center" style="vertical-align:middle; background-color: none;">&nbsp;</td>
<td width="25%" style="vertical-align:middle; ">&nbsp;</td>
<td width="45%" style="vertical-align:middle; ">&nbsp;</td>
<td width="55%" style="vertical-align:middle; ">&nbsp;</td>
<td width="25%" style="vertical-align:middle; ">&nbsp;</td>
</tr>
</table>
<!-- END SUMATORIA -->


<table class="items" width="100%" style="font-size: 9pt; border-collapse: collapse;" cellpadding="5">
<thead>
<tr>
  <td width="25%">Total a pagar</td>
  <td width="25%">Fecha de compra</td>
  <td width="25%">Importe de recompra</td>
   <td width="25%">Fecha de vencimiento</td>
	<td width="25%">Importe de renovación</td>
  </tr>
</thead>
<tbody>
<!-- ITEMS HERE -->
<tr>
<td align="center">'.$rsItem['precio_compra'].' €</td>
<td align="center">'.$sqldate.'</td>
<td align="center">'.$rsItem['precio_recompra'].' €</td>
<td align="center">'.$sqlvdate.'</td>
<td align="center">'.$difrecompra.' €</td>
</tr>
<!-- END ITEMS HERE -->
<tr>
<td class="blanktotal" colspan="3"></td>
</tr>
</tbody>
</table>


<div style="text-align: left; font-style: italic; margin:0 0 15px 0; color:#999999;  font-size:5pt;">Declaro que las piezas aquí detalladas, son de mi propiedad lícita y se hallan libres de toda carga. Doy conformidad con el precio fijado por las piezas que vendo, y que se encuentran arriba descritas.</div>

<table class="items" width="100%" style="font-size: 9pt; border-collapse: collapse;" cellpadding="8">
<thead>
<tr>
  <td width="33%">El comprador</td>
  <td width="33%">El vendedor</td>
  <td width="33%">Lote retirado</td>
  </tr>
</thead>
<tbody>
<!-- ITEMS HERE -->
<tr  height="95">
<td align="center" style=" color:#cccccc;">&nbsp;</td>
<td align="center" style=" color:#cccccc;">&nbsp;</td>
<td align="center" style=" color:#cccccc;">&nbsp;</td>
</tr>

<!-- END ITEMS HERE -->
<tr>
<td class="blanktotal" colspan="3"></td>
</tr>
</tbody>
</table>

<div style="text-align: left; font-style: italic; margin:20px 0 0 0; color:#999999; font-size:5pt; ">CLAUSULAS APLICABLES A LAS OPERACIONES DE COMPRA DE ARTÍCULOS DE SEGUNDAMANO.
Desde la firma del presente contrato, la parte vendedora no podrá reclamar la pieza o piezas vendidas detalladas anteriormente. Siendo desde este momento propiedad de la parte compradora.
<br />
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
				dirección de correo dpd.cliente@conversia.es o al teléfono 902 877 192.<br/><br/>Firmado';
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
				dirección de correo dpd.cliente@conversia.es o al teléfono 902 877 192.<br/><br/>Firmado';
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
				dirección de correo dpd.cliente@conversia.es o al teléfono 902 877 192.<br/><br/>Firmado';
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
				dirección de correo dpd.cliente@conversia.es o al teléfono 902 877 192.<br/><br/>Firmado';
		break;
}
$html .= '
</body>
</html>
';
$mpdf->WriteHTML($html);
$mpdf->Output('Ficha Contrato Lote Nº '.$rsItem['id_lote'].'.pdf','D');

//$mpdf->Output(); exit;

exit;

?>