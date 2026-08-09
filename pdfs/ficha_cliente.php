<?php
require_once("conexion.php");
$idc = $_GET["cliente"];
$query = "SELECT * FROM clientes WHERE id_cliente=$idc ";
mysql_query ("SET NAMES 'utf8'");
$Item = mysql_query($query, $conexion);
$rsItem = mysql_fetch_assoc($Item);

$date= $rsItem['f_alta'];
$sqldate=date('d-m-Y',strtotime($date));

$nacimiento= $rsItem['f_nacimiento'];
$nac=date('d-m-Y',strtotime($nacimiento));

$expedicion= $rsItem['f_expedicion'];
$exp=date('d-m-Y',strtotime($expedicion));

$cliente=$rsItem['id_cliente'];

//final de consulta////
///////////////////////
//genero pdf///////////

include("../MPDF54/mpdf.php");

$mpdf=new mPDF('win-1252','A4-L','','',13,13,25,5,5,10);
$mpdf->useOnlyCoreFonts = true;    // false is default
$mpdf->SetProtection(array('print'));
$mpdf->SetTitle("Silver Gold - Contrato de compra de oro");
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
	    <td height="10" style="vertical-align:middle; ">Ficha de cliente</td>
	    </tr>
	  <tr>
	    <td height="10" style="vertical-align:middle; ">Fecha de alta: <span style="font-weight: bold; font-size: 11pt;">'.$sqldate.'</span></td>
	    </tr>
	  <tr>
	    <td height="10" style="vertical-align:middle; ">Cliente número: <span style="font-weight: bold; font-size: 11pt;">'.$rsItem['id_cliente'].'</span></td>
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
<td width="33%" style="font-size: 12pt;"><span style="font-size: 12pt;  font-family: arial; background-color: #EEEEEE; padding:5px;">&nbsp;Datos del cliente&nbsp;</span><br />
Nombre y apellido: '.$rsItem['nombre'].'<br />Fecha de nacimiento: '.$nac.'<br />Nacionalidad: '.$rsItem['nacionalidad'].'</td>
<td width="33%" style="font-size: 12pt;">Domicilio: '.$rsItem['direccion'] .'<br />Población: '.$rsItem['c_poblacion'].' <br />Provincia: '.$rsItem['c_provincia'].' ('.$rsItem['codigo_postal'].')<br />Teléfono: '.$rsItem['telefono'].' / Móvil.: '.$rsItem['movil'].'</td>
<td width="33%" style="font-size: 12pt;">'.$rsItem['tipo_identificacion'] .': '.$rsItem['identificacion'] .' <br />Fecha de Expedición: '.$exp.'<br />Lugar de Expedición: '.$rsItem['l_expedicion'] .'<br />Alta en sucursal: '.$rsItem['sucursal'] .'  </td>
</tr>
<tr>
<td colspan="3"><span style="font-size: 12pt;  font-family: arial;">Documentación aportada. <br /></span></td>
</tr>
</table>
<table width="100%" style=" margin-top:5px; border-collapse: collapse;" cellpadding="10">
<tr>
';
$query = mysql_query("SELECT * FROM fotos_app WHERE id_cliente='$cliente' ", $conexion);
while($row = mysql_fetch_array($query)){
$html .= '
<td width="500px" align="center" ><img src="../photos/'.$row['nombre_foto'].'" style="width:auto; height:375px; " /></td>
';
	}
$html .= '
</tr>
</table>
<!-- END LISTO ITEMS -->
</body>
</html>
';
$mpdf->WriteHTML($html);
$mpdf->Output('Ficha Cliente Nº '.$rsItem['id_cliente'].'.pdf','D');
//$mpdf->Output(); exit;
exit;

?>