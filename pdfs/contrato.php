<?php

require("session_file.php");
?>
<?php
require_once("../conexion.php");
$idl = $_GET["lote"];
$query = "SELECT * FROM lotes_$sucursal 
LEFT JOIN clientes ON lotes_$sucursal.cliente=clientes.id_cliente 
LEFT JOIN sucursal ON lotes_$sucursal.sucursal=sucursal.id_sucursal 
LEFT JOIN articulos_$sucursal ON lotes_$sucursal.id_lote=articulos_$sucursal.id_lote_articulos 
WHERE id_lote=$idl 
";

mysql_query ("SET NAMES 'utf8'");
$Item = mysql_query($query, $conexion);
$rsItem = mysql_fetch_assoc($Item);

$cantidadarticulos = $rsItem['cantidad_articulos'];

$pesototal = $rsItem['peso'];

$preciototal = $rsItem['precio_compra'];

$date= $rsItem['fecha_compra'];
$sqldate=date('d-m-Y',strtotime($date));

///////FUNCION FECHA

$dia = date('d',strtotime($date));
$mes = date('m',strtotime($date));
$año = date('Y',strtotime($date));
$meses = array('01' => 'enero','02' => 'febrero','03' => 'marzo','04' => 'abril','05' => 'mayo','06' => 'junio','07' => 'julio','08' => 'agosto','09' => 'septiembre','10' => 'octubre','11' => 'noviembre','12' => 'diciembre');

if($meses[$mes])
{
$d = $dia;
$m = $meses[$mes];
$a = $año;
}

///////END FUNCION FECHA

$daten= $rsItem['f_nacimiento'];
$sqldatne=date('d-m-Y',strtotime($daten));

if($rsItem['tipo_identificacion']==dni){ $documento="checked='checked'"; $otrodocumento=""; }else{ $documento=""; $otrodocumento="checked='checked'"; $otrod=$rsItem['tipo_identificacion']; }

if($rsItem['nacionalidad']==Española){ $nacion="checked='checked'"; $otronacion=""; }else{ $nacion=""; $otronacion="checked='checked'"; $otron=$rsItem['nacionalidad']; }

if($rsItem['sexo']==Hombre){ $sexhombre="checked='checked'"; $sexmujer=""; }else{ $sexhombre=""; $sexmujer="checked='checked'"; }

if($rsItem['compra_opcion']==si){ $empenado="checked='checked'"; $comprado=""; }else{ $empenado=""; $comprado="checked='checked'"; }

include("../API/MPDF54/mpdf.php");

//Create pdf object
$mpdf=new mPDF('c','A4','','',13,13,13,13,16,13);
// $mpdf=new mPDF('win-1252','A4','','',13,13,5,5,5,5); 
$mpdf->useOnlyCoreFonts = true;    // false is default
$mpdf->SetProtection(array('print'));
$mpdf->SetTitle("Oro Efectivo - Contrato de compra de oro");
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
body {font-family: helvetica, arial;
    font-size: 9pt;
	 width:900px;
	 margin:0 auto;
	 color:#000000;
	 text-transform: uppercase; 
}
table{
  margin-bottom:5px;
}
p{ 
margin: 0pt;
line-height:0pt;
text-transform: uppercase; 
}
td { vertical-align: top; }
.items td {
}
.table {
	border-collapse:collapse;
}
.tablearticulo{
	margin:0 0 20px 0;
}
.table .td{
	padding:6px 8px;
	border: 1px solid #333333;
	border-spacing:0px;
	height:30px;
}
table thead td {
	text-align: center;
}
.items td.blanktotal {
    background-color: none;
    border: none;
}
.items td.totals {
    text-align: right;
}
h2{
	text-align:center;
	font-size:13px;
  padding-bottom: 30px;
}
h3{
	text-decoration:none;
	font-size:10px;
	margin-top: 10px;
}
smallbr{
font-size: 1px; 
line-height: 0; 
}
</style>
</head>
<body>
<!--mpdf
<htmlpagefooter name="footer">

</htmlpagefooter>
<sethtmlpagefooter name="footer" value="on" />
mpdf-->

<table width="100%" cellspacing="0" cellpadding="0" class="table" style="margin-bottom:15px;">
  <tr>
    <td align="center"><h2>HOJA - CONTRATO COMPRAVENTA OBJETOS METALES PRECIOSOS</h2></td>
  </tr>
</table>

<table width="100%"  cellspacing="0" cellpadding="0" class="table" >
  <tr>
    <td><h3>Datos del establecimiento</h3></td>
  </tr>
  <tr>
    <td width="63%" class="table td"><p>Nombre</p>
    <p><strong>'.$rsItem['empresa'].' '.$rsItem['nombre_sucursal'].'</strong></p></td>
    <td width="37%" class="table td"><p>NIF</p>
    <p><strong>'.$rsItem['numero_identificacion_tienda'].'</strong></p></td>
  </tr>
  <tr>
    <td class="table td"><p>Calle</p>
    <p><strong>'.$rsItem['calle'].'</strong></p></td>
    <td class="table td"><p>Nº</p>
    <p><strong>'.$rsItem['numero_calle'].'</strong></p></td>
  </tr>
  <tr>
    <td class="table td"><p>Localidad</p>
    <p><strong>'.$rsItem['poblacion_tienda'].'</strong></p></td>
    <td class="table td"><p>Provincia</p>
    <p><strong>'.$rsItem['provincia_tienda'].'</strong></p></td>
  </tr>
</table>


<table width="100%" cellspacing="0" cellpadding="0" class="table" >
  <tr>
    <td><h3>Datos de la operación</h3></td>
  </tr>
  <tr>
    <td width="47%" class="table td"><p>Nº de contrato</p>
    <p><strong>'.$rsItem['id_lote'].'</strong></p></td>
    <td width="53%" class="table td"><p>Fecha</p>
    <p><strong>'.$sqldate.'</strong></p></td>
  </tr>
  <tr>
   <td class="table td"><p>Tipo de contrato</p>
	
	<p><input type="checkbox" name="radio" '.$comprado.' > Compraventa
	
	<input type="checkbox" name="radio"  '.$empenado.' > Empeño
	
	</p></td>
	
	<td class="table td"><p>Papeleta empeño</p>
	
	<p><input type="checkbox" name="radio" > Si
	
	<input type="checkbox" name="radio" checked="checked" > No
	
	</p></td>
  </tr>
</table>


<table width="100%" cellspacing="0" cellpadding="0" class="table" >
  <tr>
    <td><h3>Interesado:</h3></td>
  </tr>
  <tr>
    <td width="39%" class="table td"><p>Nombre</p>
    <p><strong>'.$rsItem['nombre'].'</strong></p></td>
    <td width="61%" class="table td"><p>Apellidos</p>
    <p><strong>'.$rsItem['apellido'].'</strong></p></td>
  </tr>
  
  <tr>
    <td class="table td">
	 <p>Nº documento</p>
    <p>
	 <strong>'.$rsItem['identificacion'] .'</strong></p>
	 </td>
    <td class="table td">
	 <p>Tipo</p>
    <p>
	 <input type="checkbox" name="radio" '.$documento.'> DNI
	 <input type="checkbox" name="radio" '.$otrodocumento.'> Otro (indicar):<strong> '.$otrod.'</strong>
	 </p>
	 </td>
  </tr>
  
  <tr>
   <td class="table td">
	<p>Sexo</p>
	<p>
	<input type="checkbox" name="radio" '.$sexhombre.'>Varón
	<input type="checkbox" name="radio" '.$sexmujer.'>Mujer
	</p>
	</td>
   <td class="table td">
	<p>Fecha de nacimiento</p>
   <p><strong>'.$sqldatne.'</strong></p>
	</td>
  </tr>
  
  <tr>
    <td class="table td">
    <p>País de Nacionalidad</p>
    <p><input type="checkbox" name="radio" '.$nacion.'>España
	 <input type="checkbox" name="radio" '.$otronacion.'>Otro (indicar):<strong>'.$otron.'</strong>
	 </p>
	 </td>
   <td class="table td">
    <p>Teléfono</p>
    <p><strong>'.$rsItem['telefono'].'</strong></p>
   </td>
  </tr>
  <tr>
    <td colspan="2" class="table td"><p>Domicilio actual: /calle y nº</p>
    <p><strong>'.$rsItem['direccion'] .'</strong></p></td>
  </tr>
  <tr>
    <td class="table td"><p>Localidad</p>
    <p><strong>'.$rsItem['c_poblacion'] .'</strong></p></td>
    <td class="table td"><p>Provincia</p>
    <p><strong>'.$rsItem['c_provincia'] .'</strong></p></td>
  </tr>
</table>



';


$querys = "SELECT * FROM articulos_$sucursal WHERE id_lote_articulos =$idl ORDER BY id_articulo_lote  ";
mysql_query ("SET NAMES 'utf8'");
$Itemas = mysql_query($querys, $conexion);
while($row = mysql_fetch_assoc($Itemas)){ 

if($row['active_inscripciones']==si){ $inscsi="checked='checked'"; $inscno=""; }else{ $inscsi=""; $inscno="checked='checked'"; }

if($row['active_piedras']==si){ $piedcsi="checked='checked'"; $piedno=""; }else{ $piedcsi=""; $piedno="checked='checked'"; }

extract($row); 




$html .= '
<table width="100%" cellspacing="0" cellpadding="0" class="table tablearticulo" >
  <tr>
    <td><h3>Objeto/s del presente contrato (individualizados)</h3></td>
  </tr>
  <tr>
    <td width="20%" class="table td"><p>Nº orden</p>
    <p><strong>'.$id_articulo_lote.'</strong></p></td>
    <td colspan="2" width="80%" class="table td"><p>Clase</p>
    <p><strong>'.$descripcion_articulo.'</strong></p></td>
  </tr>
  <tr>
    <td class="table td">
		 <p>Peso neto (gr.) <strong>'.$peso_articulo.'</strong></p>
		 <p>Peso bruto (gr.) <strong>'.$peso_bruto.'</strong></p>
    </td>
	 <td class="table td"><p>Metales </p>
    <p><strong>'.$tipo_de_articulo.'</strong></p></td>
    <td class="table td"><p>Peso quilates</p>
    <p><strong>'.$ley.'</strong></p></td>
  </tr>
  <tr>
    <td class="table td"><p>Grabaciones</p>
    <p><input type="checkbox" name="radio" '.$inscsi.' > Si
	 <input type="checkbox" name="radio" '.$inscno.' > No
	 </p>
	 </td>
    <td  colspan="2" class="table td"><p>Detalles  </p>
    <p><strong>'.$inscripciones.'</strong></p></td>
  </tr>
  <tr>
    <td class="table td"><p>Piedras</p>
	 <p><input type="checkbox" name="radio" '.$piedcsi.' > Si
	 <input type="checkbox" name="radio" '.$piedno.' > No
	 </p>
	 </td>
	 <td class="table td"><p>Clase</p>
    <p><strong>'.$descripcion_piedras.'</strong></p></td>
    <td class="table td"><p>Peso quilates</p>
    <p><strong>'.$kilate_piedras.'</strong></p></td>
  </tr>
  <tr>
    <td  colspan="2"  width="47%" class="table td"><p>Precio abonado</p>
    <p><strong>'.$precio_compra_articulo.' €</strong></p></td>
    <td width="53%" class="table td"><p>Fotografia detallada objeto</p>
    <p><input type="checkbox" name="radio" checked="checked"> Si</p></td>
  </tr>
  <tr>
    <td  colspan="3"  width="100%" class="table td" height="15"><p>(*) Fecha venta posterior: <strong></strong></p>
	 </td>
  </tr>
</table>
';
}

$query = "SELECT * FROM lotes_$sucursal WHERE id_lote=$idl ";
mysql_query ("SET NAMES 'utf8'");
$Item = mysql_query($query, $conexion);
$rsItem2 = mysql_fetch_assoc($Item);
$pesobruto = $rsItem2['peso_bruto'];

$html .= '
<table width="100%" cellspacing="0" cellpadding="0" class="table tablearticulo" style="margin-top:0px;" >
  <tr>
    <td width="47%" class="table td"><p>Precio Total</p>
    <p><strong>'.$preciototal.' €</strong></p></td>
	 <td class="table td"><p>Peso total Objetos</p>
    <p><strong>'.$pesototal.' grs</strong></p></td>
	 <td class="table td"><p>Pisua/ peso Bruto Objetos</p>
    <p><strong>'.$pesobruto.' grs</strong></p></td>
  </tr>
</table>
<div style="text-align: left; font-style: italic; margin:5px 0 0 0; color:#000000; font-size:5pt; ">CLAUSULAS APLICABLES A LAS OPERACIONES DE COMPRA DE ORO.
Desde la firma del presente contrato, la parte vendedora no podrá reclamar la pieza o piezas vendidas detalladas anteriormente. Siendo desde este momento propiedad de la parte compradora.
<br />
<strong>EN ESTE CONTRATO USTED ESTA VENDIENDO EL OBJETO REFERENCIADO  DEL QUE YA NO ES PROPIETARIO.</strong><br />
NOTA: En cumplimiento de lo dispuesto en la Ley Orgánica 15/1999 del 13 de Diciembre, de Protección de Datos de Carácter personal, sus datos serán incorporados en un fichero automatizado de datos confidencial y podrá utilizarse para enviarle información. El cliente podrá ejercitar los derechos de acceso, rectificación y oposición, comunicándolo por escrito a esta dirección: '.$rsItem['empresa'].', '.$rsItem['direccion_tienda'].', '.$rsItem['poblacion_tienda'].' '.$rsItem['codigo_postal_tienda'].' '.$rsItem['provincia_tienda'].'.
</div>
<br />
<table width="100%" cellspacing="0" cellpadding="0" class="table" >
  <tr>
    <td align="center"><h4>En '.$rsItem['poblacion_tienda'].' a '.$d.' de '.$m.' de '.$a.'</h4></td>
  </tr>
</table>

<table width="100%" border="0" cellspacing="0" cellpadding="0" class="table" style="margin-top: 30px">
  <tr align="center">
    <td><h6>Sello y firma del establecimiento</h6></td>
    <td><h6>Firma del interesado</h6></td>  
  </tr>
</table>

</body>
</html>
';
$mpdf->WriteHTML($html);
//$mpdf->Output('Contrato Nº '.$rsItem['id_lote'].'.pdf','D');

/* Solo para la sucursal de eibar */
if ($sucursal === 25) {
  $mpdf->SetJS('this.print();');
}

$mpdf->Output(); 
exit;
?>