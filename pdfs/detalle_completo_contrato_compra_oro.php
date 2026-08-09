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
$mpdf->ignore_invalid_utf8 = false;

$html = '
<html>
<head>
<meta charset="UTF-8" />
<style>
body {font-family: arial;
    font-size: 10pt;
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

</htmlpageheader>

<sethtmlpageheader name="myheader" value="on" show-this-page="1"  />
	<htmlpagefooter name="footer">
	</htmlpagefooter>
<sethtmlpagefooter name="footer" value="on" />
mpdf-->


';


$html .= '
<div style=" height:auto;">
<table class="items" width="100%" style=" font-size: 11px; border-collapse: collapse;" cellpadding="8">
<thead>
<tr>
  <td width="5%" style="background-color: #ffffff; color:#ffffff;" >Nº</td>
  <td width="5%" style="background-color: #ffffff; color:#ffffff;" >Us</td>
  <td width="65%" style="background-color: #ffffff; color:#ffffff;" >Descricpión</td>
  <td width="25%" style="background-color: #ffffff; color:#ffffff;" >Inscripciones</td>
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
<td align="center" style="text-align:center;">'.$unidades.'</td>
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
<td width="8%" height="20" align="center" style="background-color: #EEEEEE;">Total</td>
<td width="8%" align="center" style="background-color: #EEEEEE;">'.$smatoria.'</td>
<td width="55%">&nbsp;</td>
<td width="45%">&nbsp;</td>
</tr>
</table>
<!-- END SUMATORIA -->

<table class="items" width="100%" style="font-size: 9pt; border-collapse: collapse;" cellpadding="5">
<thead>
<tr>
  <td width="33%" style="vertical-align:middle; background-color: #ffffff; color:#ffffff; ">Gramos</td>
  <td width="33%" style="vertical-align:middle; background-color: #ffffff; color:#ffffff; ">Total a pagar</td>
  <td width="33%" style="vertical-align:middle; background-color: #ffffff; color:#ffffff; ">Fecha de compra</td>
  </tr>
</thead>
<tbody>
<!-- ITEMS HERE -->
<tr>
<td align="center" style="vertical-align:middle; background-color: #ffffff; color:#ffffff; ">'.$rsItem['peso'].' grs</td>
<td align="center" style="vertical-align:middle; background-color: #ffffff; color:#ffffff; ">'.$rsItem['precio_compra'].' €</td>
<td align="center" style="vertical-align:middle; background-color: #ffffff; color:#ffffff; ">'.$sqldate.'</td>
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
//$mpdf->Output('Ficha Contrato Lote Nº '.$rsItem['id_lote'].'.pdf','D');
$mpdf->Output();
exit;
?>