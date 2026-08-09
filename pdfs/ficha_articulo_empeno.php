<?php
require_once("conexion.php");
$idc = $_GET["empeno"];
$query = "SELECT * FROM articulos_empenos INNER JOIN empenos ON articulos_empenos.id_lote_articulo_empeno=empenos.id_empeno WHERE id_articulo_empeno=$idc ";
mysql_query ("SET NAMES 'utf8'");
$Item = mysql_query($query, $conexion);
$rsItem = mysql_fetch_assoc($Item);

$date= $rsItem['fecha_compra'];
$sqldate=date('d-m-Y',strtotime($date));

$articulo=$rsItem['id_articulo_empeno'];

//final de consulta////
///////////////////////
//genero pdf///////////

include("../MPDF54/mpdf.php");

$mpdf=new mPDF('win-1252','A4-L','','',13,13,25,5,5,10);
$mpdf->useOnlyCoreFonts = true;    // false is default
$mpdf->SetProtection(array('print'));
$mpdf->SetTitle("Silver Gold - Ficha de artículo de empeño");
$mpdf->SetAuthor("Silver Gold");
$mpdf->SetWatermarkText("Enviada");
$mpdf->showWatermarkText = false;
$mpdf->watermark_font = 'DejaVuSansCondensed';
$mpdf->watermarkTextAlpha = 0.1;
//$mpdf->displayDefaultOrientation = true; 
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
	    <td height="10" style="vertical-align:middle; ">Ficha de artículo  Nº: <span style="font-weight: bold; font-size: 11pt;">'.$rsItem['id_articulo_empeno'].'</span></td>
	    </tr>
	  <tr>
	    <td height="10" style="vertical-align:middle; ">Fecha de compra: <span style="font-weight: bold; font-size: 11pt;">'.$sqldate.'</span></td>
	    </tr>
	  <tr>
	    <td height="10" style="vertical-align:middle; ">Lote de empeños Nº: <span style="font-weight: bold; font-size: 11pt;">'.$rsItem['id_empeno'].'</span></td>
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
<td width="33%" style="font-size: 12pt;"><span style="font-size: 12pt;  font-family: arial; background-color: #EEEEEE; padding:5px;">&nbsp;Datos del artículo&nbsp;</span><br />
Tipo de artículo: '.$rsItem['tipo_de_articulo_empeno'].'<br />Marca: '.$rsItem['marca_articulo_empeno'].'<br />Número de serie/Matrícula: '.$rsItem['numero_de_serie'].'</td>
<td width="33%" style="font-size: 12pt;">Unidades: '.$rsItem['unidades_articulo_empeno'] .'<br />Estado del artículo: '.$rsItem['estado_articulo_empeno'].' <br />Descripción del artículo: '.$rsItem['descripcion_articulo_empeno'].'</td>
</tr>
</table>
';
$html .= '
<table width="100%" style=" margin-top:25px; border-collapse: collapse;" >
<tr><td width="100%" align="left" >
';
$query = mysql_query("SELECT * FROM fotos_app_empenos WHERE id_articulo_empeno='$articulo' ", $conexion);
	while($row = mysql_fetch_array($query)){
  $html .= '
<img src="../photos_articulos_empeno/'.$row['nombre_foto'].'" style="width:320px; height:auto; margin:0 15px 35px 0;" />

';
	}
$html .= '
</td></tr>
</table>
<!-- END LISTO ITEMS -->
</body>
</html>
';


$mpdf->WriteHTML($html);
$mpdf->Output('Artículo Nº '.$rsItem['id_articulo_empeno'].'.pdf','D');
//$mpdf->Output(); exit;
exit;
?>