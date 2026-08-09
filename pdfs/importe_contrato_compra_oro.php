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

</htmlpageheader>

<sethtmlpageheader name="myheader" value="on" show-this-page="1"  />

mpdf-->


<table width="100%" style="font-family: Arial; ma" cellpadding="5">

<tr>
<td width="45%" style=""><span style="font-size: 9pt;  font-family: arial; background-color: none; padding:5px;">&nbsp;&nbsp;</span><br /><br />&nbsp;<br />&nbsp;<br />&nbsp;<br />&nbsp;<br />&nbsp;</td>
<td width="10%">&nbsp;</td>
<td width="45%" style=""><span style="font-size: 9pt;  font-family: arial; background-color: none; padding:5px;">&nbsp;</span><br /><br />
&nbsp;<br />&nbsp;<br />&nbsp;<br />&nbsp;<br />&nbsp;</td>
</tr>
<tr>
<td colspan="3"><span style="font-size: 9pt;  font-family: arial;">&nbsp;</span></td>
</tr>
</table>
';


$html .= '
<div style=" height:600px;">
<table class="items" width="100%" style=" font-size: 9pt; border-collapse: collapse;" cellpadding="8">
<thead>
<tr style="background-color: none;">
  <td style="background-color: none;" width="8%">&nbsp;</td>
  <td style="background-color: none;" width="8%">&nbsp; </td>
  <td style="background-color: none;" width="55%">&nbsp;</td>
  <td style="background-color: none;" width="45%">&nbsp;</td>
</tr>
</thead>
<tbody>
<!-- LISTO ITEMS -->
';

$html .= '
<!-- END LISTO ITEMS -->
</tbody>
</table>
</div>';

$html .= '
<!-- SUMATORIA -->
<table class="items" width="100%" style=" font-size: 9pt; border-collapse: collapse; margin-bottom:15px;" cellpadding="8">
<tr>
<td width="8%" height="20" align="center" style="vertical-align:middle; background-color: none;">&nbsp;</td>
<td width="8%" align="center" style="vertical-align:middle; background-color: none;">&nbsp;</td>
<td width="55%" style="vertical-align:middle; ">&nbsp;</td>
<td width="45%" style="vertical-align:middle; ">&nbsp;</td>
</tr>
</table>
<!-- END SUMATORIA -->


<table class="items" width="100%" style="font-size: 9pt; border-collapse: collapse;" cellpadding="5">
<thead>
<tr>
  <td style="background-color: none;" width="33%">&nbsp;</td>
  <td style="background-color: none;" width="33%">Total a pagar</td>
  <td style="background-color: none;" width="33%">&nbsp;</td>
  </tr>
</thead>
<tbody>
<!-- ITEMS HERE -->
<tr>
<td align="center">&nbsp;</td>
<td align="center">'.$rsItem['precio_compra'].' €</td>
<td align="center">&nbsp;</td>
</tr>
<!-- END ITEMS HERE -->
<tr>
<td class="blanktotal" colspan="3"></td>
</tr>
</tbody>
</table>

<div style="text-align: left; font-style: italic; margin:0 0 15px 0; color:#999999;  font-size:5pt;">&nbsp;</div>

<table class="items" width="100%" style="font-size: 9pt; border-collapse: collapse;" cellpadding="8">
<thead>
<tr>
  <td style="background-color: none;" width="33%">&nbsp;</td>
  <td style="background-color: none;" width="33%">&nbsp;</td>
  <td style="background-color: none;" width="33%">&nbsp;</td>
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

<div style="text-align: left; font-style: italic; margin:20px 0 0 0; color:#999999; font-size:5pt; ">&nbsp;
<br />&nbsp;</div>

</body>
</html>
';
$mpdf->WriteHTML($html);
$mpdf->Output('Importe Contrato Lote Nº '.$rsItem['id_lote'].'.pdf','I');
//$mpdf->Output(); exit;

exit;

?>