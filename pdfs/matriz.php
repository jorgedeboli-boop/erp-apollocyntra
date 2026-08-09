<?php
require_once("conexion.php");
$idl = $_GET["lote"];
$query = "SELECT * FROM lotes 
LEFT JOIN clientes ON lotes.cliente=clientes.id_cliente 
LEFT JOIN sucursal ON lotes.sucursal=sucursal.nombre_sucursal 
LEFT JOIN articulos ON lotes.id_lote=articulos.id_lote_articulos 
WHERE id_lote=$idl 
";

mysql_query ("SET NAMES 'utf8'");
$Item = mysql_query($query, $conexion);
$rsItem = mysql_fetch_assoc($Item);

//while ($rItem = mysql_fetch_assoc($Item))
//$smatoria+=$rItem['unidades'];


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
	    <td height="10" style="vertical-align:middle; ">Contrato de compra de oro</td>
	    </tr>
	  <tr>
	    <td height="10" style="vertical-align:middle; ">Fecha: <span style="font-weight: bold; font-size: 11pt;">'.$rsItem['fecha_compra'].'</span></td>
	    </tr>
	  <tr>
	    <td height="10" style="vertical-align:middle; ">Operación Nº <span style="font-weight: bold; font-size: 11pt;">'.$rsItem['id_lote'].'</span></td>
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
<td width="45%" style=""><span style="font-size: 9pt;  font-family: arial; background-color: #EEEEEE; padding:5px;">&nbsp;Comprador&nbsp;</span><br /><br />Silver Gold<br />'.$rsItem['identificacion_tienda'].'  '.$rsItem['numero_identificacion_tienda'].'<br />'.$rsItem['direccion_tienda'].'<br />'.$rsItem['poblacion_tienda'].'<br />'.$rsItem['codigo_postal_tienda'].', '.$rsItem['provincia_tienda'].'</td>
<td width="10%">&nbsp;</td>
<td width="45%" style=""><span style="font-size: 9pt;  font-family: arial; background-color: #EEEEEE; padding:5px;">&nbsp;Vendedor&nbsp;</span><br /><br />
'.$rsItem['nombre'].'<br />'.$rsItem['tipo_identificacion'] .' '.$rsItem['identificacion'] .'<br />'.$rsItem['direccion'].'<br />'.$rsItem['c_poblacion'].'<br />'.$rsItem['codigo_postal'].', '.$rsItem['c_provincia'].'</td>
</tr>
<tr>
<td colspan="3"><span style="font-size: 9pt;  font-family: arial;">Datos de la venta. El vendedor reconoce que los artículos que se detallan a continuación son de su legítima propiedad.</span></td>
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
  <td width="55%">Descricpión</td>
  <td width="45%">Inscirpciones</td>
</tr>
</thead>
<tbody>
<!-- LISTO ITEMS -->
';
$querys = "SELECT * FROM articulos_$sucursal WHERE id_lote_articulos =$idl ";
mysql_query ("SET NAMES 'utf8'");
$Itemas = mysql_query($querys, $conexion);
while($row = mysql_fetch_assoc($Itemas)){ 
extract($row); 

$html .= '<tr>
<td height="20" align="center" style="vertical-align:middle; ">'.$id_articulo.'</td>
<td align="center" style="vertical-align:middle; ">'.$unidades.'</td>
<td style="vertical-align:middle; ">'.$descripcion_articulo.'</td>
<td style="vertical-align:middle; ">'.$inscripciones.'</td>
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
<td width="8%" height="20" align="center" style="vertical-align:middle; background-color: #EEEEEE;">Total</td>
<td width="8%" align="center" style="vertical-align:middle; background-color: #EEEEEE;">'.$smatoria.'</td>
<td width="55%" style="vertical-align:middle; ">&nbsp;</td>
<td width="45%" style="vertical-align:middle; ">&nbsp;</td>
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
<td align="center">76.00</td>
<td align="center">4584 €</td>
<td align="center">20/11/2012</td>
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

<div style="text-align: left; font-style: italic; margin:20px 0 0 0; color:#999999; font-size:5pt; ">CLAUSULAS APLICABLES A LAS OPERACIONES DE COMPRA DE ORO.
Desde la firma del presente contrato, la parte vendedora no podrá reclamar la pieza o piezas vendidas detalladas anteriormente. Siendo desde este momento propiedad de la parte compradora.
<br />
EN ESTE CONTRATO USTED ESTA VENDIENDO EL OBJETO REFERENCIADO  DEL QUE YA NO ES PROPIETARIO.
NOTA: En cumplimiento de lo dispuesto en la Ley Orgánica 15/1999 del 13 de Diciembre, de Protección de Datos de Carácter personal, sus datos serán incorporados en un fichero automatizado de datos confidencial y podrá utilizarse para enviarle información de Silver Gold. El cliente podrá ejercitar los derechos de acceso, rectificación y oposición, comunicándolo por escrito a esta dirección: Silver Gold, C/ Arrontegui, 8-Lonja, Barakaldo 48901 BIZKAIA.</div>

</body>
</html>
';
$mpdf->WriteHTML($html);
$mpdf->Output('Ficha-Contrato-.pdf','D');
//$mpdf->Output(); exit;

exit;

?>