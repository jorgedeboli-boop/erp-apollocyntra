<?php
require("session_file.php");
?>
<?php
require_once("conexion.php");
$idl = $_GET["empeno"];
$query = "
SELECT * FROM empenos_$sucursal 
LEFT JOIN clientes ON empenos_$sucursal.cliente=clientes.id_cliente 
LEFT JOIN sucursal ON empenos_$sucursal.sucursal_empeno=sucursal.id_sucursal 
WHERE id_empeno=$idl 
";

//LEFT JOIN sucursal ON empenos_$sucursal.sucursal=sucursal.nombre_sucursal 

mysql_query ("SET NAMES 'utf8'");
$Item = mysql_query($query, $conexion);
$rsItem = mysql_fetch_assoc($Item);


$date= $rsItem['fecha_compra'];
$sqldate=date('d-m-Y',strtotime($date));


$date= $rsItem['fecha_vencimiento'];
$sqlvdate=date('d-m-Y',strtotime($date));

$difrecompra = $rsItem['precio_recompra']-$rsItem['precio_compra'];

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
		 <td height="10"  style="vertical-align:middle; font-size:15px; font-weight: bold;">Contrato de compra</td>
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
<td width="20%" style=""><span style="font-size: 9pt;  font-family: arial; background-color: #EEEEEE; padding:5px;">&nbsp;El comprador&nbsp;</span><br /><br />'.$rsItem['empresa'].'<br />'.$rsItem['identificacion_tienda'].'  '.$rsItem['numero_identificacion_tienda'].'<br />'.$rsItem['direccion_tienda'].'<br /></td>
<td width="30%" ><span style="font-size: 9pt;  font-family: arial; padding:5px;">&nbsp;&nbsp;</span><br /><br />'.$rsItem['poblacion_tienda'].'<br />'.$rsItem['codigo_postal_tienda'].', '.$rsItem['provincia_tienda'].'<br />Tel.: '.$rsItem['telefono_tienda'].'</td>
<td width="10%">&nbsp;</td>
<td width="30%" style=""><span style="font-size: 9pt;  font-family: arial; background-color: #EEEEEE; padding:5px;">&nbsp;El vendedor&nbsp;</span><br /><br />
'.$rsItem['nombre'].'<br />'.$rsItem['tipo_identificacion'] .' '.$rsItem['identificacion'] .'<br />'.$rsItem['direccion'].'</td>
<td width="30%" style=""><span style="font-size: 9pt;  font-family: arial;  padding:5px;">&nbsp;&nbsp;</span><br /><br />'.$rsItem['c_poblacion'].'<br />'.$rsItem['codigo_postal'].', '.$rsItem['c_provincia'].'
<br />Tel.: '.$rsItem['telefono'].' / '.$rsItem['movil'].'</td>
</tr>
<tr>
<td colspan="5"><span style="font-size: 9pt;  font-family: arial;">Datos de la venta. El vendedor reconoce que los artículos que se detallan a continuación son de su legítima propiedad.</span></td>
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

$querys = "SELECT * FROM articulos_empenos_$sucursal WHERE id_lote_articulo_empeno = $idl ";
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
  <td width="50%">Total a pagar</td>
  <td width="50%">Fecha de compra</td>

  </tr>
</thead>
<tbody>
<!-- ITEMS HERE -->
<tr>
<td align="center">'.$rsItem['precio_compra'].' €</td>
<td align="center">'.$sqldate.'</td>
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
<strong>EN ESTE CONTRATO USTED ESTA VENDIENDO EL OBJETO REFERENCIADO  DEL QUE YA NO ES PROPIETARIO.</strong><br />
NOTA: En cumplimiento de lo dispuesto en la Ley Orgánica 15/1999 del 13 de Diciembre, de Protección de Datos de Carácter personal, sus datos serán incorporados en un fichero automatizado de datos confidencial y podrá utilizarse para enviarle información de Nombre tienda. El cliente podrá ejercitar los derechos de acceso, rectificación y oposición, comunicándolo por escrito a esta dirección: Nombre tienda, C/ Lamarque, 8-Lonja, Madrid 44444 MADRID.</div>

</body>
</html>
';
$mpdf->WriteHTML($html);
//$mpdf->Output('Ficha Contrato Lote Nº '.$rsItem['id_lote'].'.pdf','D');
$mpdf->Output();

exit;

?>