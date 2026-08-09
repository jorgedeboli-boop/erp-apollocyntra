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


$date= $rsItem['fecha_compra'];
$sqldate=date('d-m-Y',strtotime($date));


$daten= $rsItem['f_nacimiento'];
$sqldaten=date('d-m-Y',strtotime($daten));

$datefe= $rsItem['f_expedicion'];
$sqldatefe=date('d-m-Y',strtotime($datefe));


$cliente=$rsItem['id_cliente'];

//final de consulta////
///////////////////////
//genero pdf///////////

include("../MPDF54/mpdf.php");

$mpdf=new mPDF('win-1252','A4-L','','',13,13,25,5,5,10);
$mpdf->useOnlyCoreFonts = true;    // false is default
$mpdf->SetProtection(array('print'));
$mpdf->SetTitle("Silver Gold - Ficha de policía");
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
		 <td height="10"  style="vertical-align:middle; font-size:15px; font-weight: bold;">Hoja resumen de operación para la policia.</td>
	    </tr>
	  <tr>
	    <td height="10" style="vertical-align:middle; ">Fecha según libro de registro: <span style="font-weight: bold; font-size: 11pt;">'.$sqldate.'</span></td>
	    </tr>
	  <tr>
	    <td height="10" style="vertical-align:middle; ">Operación Nº según libro de registro: <span style="font-weight: bold; font-size: 11pt;">'.$rsItem['id_lote'].'</span></td>
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
<td width="30%" style=""><span style="font-size: 9pt;  font-family: arial; background-color: #EEEEEE; padding:5px;">&nbsp;Datos del vendedor&nbsp;</span><br />
Nombre y apellido: '.$rsItem['nombre'].'<br />Fecha de nacimiento: '.$sqldaten.'<br />Nacionalidad: '.$rsItem['nacionalidad'].'</td>

<td width="30%" style="">Domicilio: '.$rsItem['direccion'] .'<br />Población: '.$rsItem['c_poblacion'].' <br />Provincia: '.$rsItem['c_provincia'].' ('.$rsItem['codigo_postal'].')<br />Teléfono: '.$rsItem['telefono'].'</td>

<td width="30%" style="">'.$rsItem['tipo_identificacion'] .': '.$rsItem['identificacion'] .' <br />Fecha de Expedición: '.$sqldatefe.'<br />Lugar de Expedición: '.$rsItem['l_expedicion'].'</td>

</tr>
<tr>
<td colspan="3"><span style="font-size: 9pt;  font-family: arial;">Silver Gold que ejerce la actividad mercantil de Compra-Venta en el establecimiento de '.$rsItem['nombre_sucursal'] .', con domicilio en '.$rsItem['direccion_tienda'] .', '.$rsItem['identificacion_tienda'] .': '.$rsItem['numero_identificacion_tienda'] .'. Según lo establecido en el artículo 3º del Real Decreto 2290/1981 10 de 18 de Diciembre, ha registrado la siguiente operación de objetos usados de metales preciosos, que se hallan en dicho establecimiento hasta recoger esta comunicación debidamente visitada. <br /></span></td>
</tr>
</table>
';

$quer = "SELECT * FROM articulos_$sucursal WHERE id_lote_articulos =$idl ";
mysql_query ("SET NAMES 'utf8'");
$Its = mysql_query($quer, $conexion);
while ($rItem = mysql_fetch_assoc($Its))
$smatoria+=$rItem['unidades'];
$html .= '
<table class="items" width="100%" style="font-size: 10pt; border-collapse: collapse;" cellpadding="5">
<thead>
<tr>
  <td width="20%" style="text-align: center;">Nº de objetos: '.$smatoria.'</td>
  <td width="20%" style="text-align: center;">Importe en euros: '.$rsItem['precio_compra'].' €</td>
  <td width="20%" style="text-align: center;">Peso (gr): '.$rsItem['peso'].'</td>
</tr>

</thead>
</table>
';

$html .= '
<tr>
<td>
';
$querys = "SELECT * FROM articulos_$sucursal WHERE id_lote_articulos =$idl ";
mysql_query ("SET NAMES 'utf8'");
$Itemas = mysql_query($querys, $conexion);
while($row = mysql_fetch_assoc($Itemas)){ 
extract($row); 

$html .= '
'.$unidades.' '.$descripcion_articulo.' ('.$tipo_de_articulo.' '.$ley.'). Inscripciones: '.$inscripciones.'.// 
';
}
$html .= '
</tr>
</td>
';
$html .= '
<!-- END LISTO ITEMS -->
</tbody>
</table>
</body>
</html>
';


$mpdf->WriteHTML($html);
//$mpdf->Output('Ficha Policia Lote Nº '.$rsItem['id_lote'].'.pdf','D');
$mpdf->Output(); exit;
exit;

?>