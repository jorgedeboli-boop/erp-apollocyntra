<?php
require_once("conexion.php");
$idl = $_GET["lote"];
if ($usuario == 'mariaarias') {
  if (isset($_GET['sucursal'])) {
    $sucursal = $_GET['sucursal'];  
  }
}

$query = "SELECT * FROM lotes 
LEFT JOIN clientes ON lotes.cliente=clientes.id_cliente 
LEFT JOIN sucursal ON lotes.sucursal=sucursal.nombre_sucursal 
LEFT JOIN articulos ON lotes.id_lote=articulos.id_lote_articulos 
WHERE id_lote=$idl ";

mysql_query ("SET NAMES 'utf8'");
$Item = mysql_query($query, $conexion);
$rsItem = mysql_fetch_assoc($Item);


$date= $rsItem['fecha_compra'];
$sqldate=date('d-m-Y',strtotime($date));

//final de consulta////
///////////////////////
//genero pdf///////////

include("../MPDF54/mpdf.php");

$mpdf=new mPDF('win-1252','A4','','',5,5,53,38,5,10); 
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
		 <td height="10"  style="vertical-align:middle; font-size:15px; font-weight: bold;">Contrato de compra</td>
	    </tr>
	  <tr>
	    <td height="10" style="vertical-align:middle; ">Fecha: <span style="font-weight: bold; font-size: 11pt;">'.$sqldate.'</span></td>
	    </tr>
	  <tr>
	    <td height="10" style="vertical-align:middle; ">Lote Nº <span style="font-weight: bold; font-size: 11pt;">'.$rsItem['id_lote'].'</span></td>
	    </tr>
    </table>
    </td>
</tr>
</table>

<table width="100%" style="font-family: Arial; " cellpadding="5">
<tr>
<td width="30%" style=""><span style="font-size: 9pt;  font-family: arial; background-color: #EEEEEE; padding:5px;">&nbsp;El comprador&nbsp;</span><br /><br />'.$rsItem['empresa'].'<br />'.$rsItem['identificacion_tienda'].'  '.$rsItem['numero_identificacion_tienda'].'<br />'.$rsItem['direccion_tienda'].'<br /></td>
<td width="30%" ><span style="font-size: 9pt;  font-family: arial; padding:5px;">&nbsp;&nbsp;</span><br /><br />'.$rsItem['poblacion_tienda'].'<br />'.$rsItem['codigo_postal_tienda'].', '.$rsItem['provincia_tienda'].'<br />Tel.: '.$rsItem['telefono_tienda'].'</td>
<td width="10%">&nbsp;</td>
<td width="40%" style=""><span style="font-size: 9pt;  font-family: arial; background-color: #EEEEEE; padding:5px;">&nbsp;El vendedor&nbsp;</span><br /><br />
'.$rsItem['nombre'].' '.$rsItem['apellido'].'<br />'.$rsItem['tipo_identificacion'] .' '.$rsItem['identificacion'] .'<br />'.$rsItem['direccion'].'</td>
<td width="30%" style=""><span style="font-size: 9pt;  font-family: arial;  padding:5px;">&nbsp;&nbsp;</span><br /><br />'.$rsItem['c_poblacion'].'<br />'.$rsItem['codigo_postal'].', '.$rsItem['c_provincia'].'
<br />Tel.: '.$rsItem['telefono'].' 
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
<div style="text-align: left; font-style: italic; margin:15px 0 15px 0; color:#999999;  font-size:5pt;">Declaro que las piezas aquí detalladas, son de mi propiedad lícita y se hallan libres de toda carga. Doy conformidad con el precio fijado por las piezas que vendo, y que se encuentran arriba descritas.</div>



<div style="text-align: left; font-style: italic; margin:20px 0 0 0; color:#999999; font-size:5pt; ">CLAUSULAS APLICABLES A LAS OPERACIONES DE COMPRA DE ORO.
Desde la firma del presente contrato, la parte vendedora no podrá reclamar la pieza o piezas vendidas detalladas anteriormente. Siendo desde este momento propiedad de la parte compradora.
<br />
<strong>EN ESTE CONTRATO USTED ESTA VENDIENDO EL OBJETO REFERENCIADO  DEL QUE YA NO ES PROPIETARIO.</strong><br />
NOTA: En cumplimiento de lo dispuesto en la Ley Orgánica 15/1999 del 13 de Diciembre, de Protección de Datos de Carácter personal, sus datos serán incorporados en un fichero automatizado de datos confidencial y podrá utilizarse para enviarle información de Silver Gold. El cliente podrá ejercitar los derechos de acceso, rectificación y oposición, comunicándolo por escrito a esta dirección: '.$rsItem['empresa'].', '.$rsItem['direccion_tienda'].', '.$rsItem['poblacion_tienda'].' '.$rsItem['codigo_postal_tienda'].' '.$rsItem['provincia_tienda'].'.</div>

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
  <td width="65%">Descricpión</td>
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
<td>"'.$inscripciones.'"</td>
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
  <td width="33%">Gramos</td>
  <td width="33%">Total a pagar</td>
  <td width="33%">Fecha de compra</td>
  </tr>
</thead>
<tbody>
<!-- ITEMS HERE -->
<tr>
<td align="center">'.$rsItem['peso'].' grs</td>
<td align="center">'.$rsItem['precio_compra'].' €</td>
<td align="center">'.$sqldate.'</td>
</tr>
<!-- END ITEMS HERE -->
<tr>
<td class="blanktotal" colspan="3"></td>
</tr>
</tbody>
</table>


</body>
</html>
';
$mpdf->WriteHTML($html);
$mpdf->Output('Ficha Contrato Lote Nº '.$rsItem['id_lote'].'.pdf','D');
//$mpdf->Output(); 
exit;
?>