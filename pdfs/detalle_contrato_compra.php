<?php
require_once("conexion.php");
$idl = $_GET["empeno"];
$query = "SELECT * FROM empenos 
LEFT JOIN clientes ON empenos.cliente=clientes.id_cliente 
LEFT JOIN sucursal ON empenos.sucursal=sucursal.nombre_sucursal 
WHERE id_empeno=$idl 
";

mysql_query ("SET NAMES 'utf8'");
$Item = mysql_query($query, $conexion);
$rsItem = mysql_fetch_assoc($Item);


$date= $rsItem['fecha_compra'];
$sqldate=date('d-m-Y',strtotime($date));


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
table thead td { background-color: none;
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
<td width="20%" style=""><span style="font-size: 9pt;  font-family: arial; background-color: none; padding:5px;">&nbsp;&nbsp;&nbsp;</span><br /><br />&nbsp;<br />&nbsp;  &nbsp;<br />&nbsp;<br /></td>
<td width="30%" ><span style="font-size: 9pt;  font-family: arial; padding:5px;">&nbsp;&nbsp;</span><br /><br />&nbsp;<br />&nbsp;<br />&nbsp;</td>
<td width="10%">&nbsp;</td>
<td width="30%" style=""><span style="font-size: 9pt;  font-family: arial; background-color: none; padding:5px;">&nbsp;&nbsp;&nbsp;</span><br /><br />
&nbsp;<br />&nbsp;<br />&nbsp;</td>
<td width="30%" style=""><span style="font-size: 9pt;  font-family: arial;  padding:5px;">&nbsp;&nbsp;</span><br /><br />&nbsp;<br />&nbsp;<br />&nbsp;</td>
</tr>
<tr>
<td colspan="5"><span style="font-size: 9pt;  font-family: arial;">&nbsp;</span></td>
</tr>
</table>
';


$html .= '
<div style=" height:600px;">
<table class="items" width="100%" style=" font-size: 9pt; border-collapse: collapse;" cellpadding="8">
<thead>
<tr>
  <td width="8%">&nbsp;</td>
  <td width="8%">&nbsp;</td>
  <td width="25%">&nbsp;</td>
  <td width="45%">&nbsp;</td>
  <td width="25%">&nbsp;</td>
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
  <td width="50%">&nbsp;</td>
  <td width="50%">&nbsp;</td>

  </tr>
</thead>
<tbody>
<!-- ITEMS HERE -->
<tr>
<td align="center">&nbsp;</td>
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
  <td width="33%">&nbsp;</td>
  <td width="33%">&nbsp;</td>
  <td width="33%">&nbsp;</td>
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
<br />
<strong>&nbsp;</strong><br />
&nbsp;</div>

</body>
</html>
';
$mpdf->WriteHTML($html);
$mpdf->Output('Ficha Contrato Lote Nº '.$rsItem['id_lote'].'.pdf','D');

//$mpdf->Output(); exit;

exit;

?>