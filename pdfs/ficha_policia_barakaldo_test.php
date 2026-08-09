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

$cantidadarticulos = $rsItem['cantidad_articulos'];

$pesototal = $rsItem['peso'];

$preciototal = $rsItem['precio_compra'];

$date= $rsItem['fecha_compra'];
$sqldate=date('d-m-Y',strtotime($date));

///////FUNCION FECHA

$dia = date("d");
$mes = date("m");
$año = date("Y");
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


//final de consulta////
///////////////////////
//genero pdf///////////

include("../MPDF54/mpdf.php");
/////////////////////////////////////iz,de,top,bottom,
$mpdf=new mPDF('win-1252','A4','','',13,13,1,5,5,5); 
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
body {font-family: chelvetica, arial;
    font-size: 9pt;
	 width:900px;
	 margin:0 auto;
	 color:#000000;
}
p{ 
margin: 0pt;
line-height:1pt;
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
	padding:5px 10px;
	border: 1px solid #333333;
	border-spacing:0px;
	height:40px;
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
	font-size:15px;
}
h3{
	text-decoration:none;
	font-size:13px;
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
<table width="100%" border="0" cellspacing="0" cellpadding="0" class="table">
  <tr>
    <td><h6>Establezimenduaren zigilua eta sinadura /<br /> Sello y firma del establecimiento</h6></td>
    <td><h6>Interesatuaren sinadura /<br /> Firma del interesado</h6></td>
	 <td><h6>&nbsp;<br /> &nbsp;</h6></td>
	 <td align="right" style="text-align:right;" ><h5>&nbsp; <br />Folio Zk / Nº Pág.&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;</h5></td>
  </tr>
</table>
</htmlpagefooter>
<sethtmlpagefooter name="footer" value="on" />
mpdf-->

<table width="100%" cellspacing="0" cellpadding="0" class="table" >
  <tr>
    <td align="center"><h2>HOJA - CONTRATO COMPRAVENTA OBJETOS METALES PRECIOSOS</h2></td>
  </tr>
  <tr>
    <td><h3>Establezimenduaren datuak / Datos del establecimiento</h3></td>
  </tr>
</table>

<table width="100%"  cellspacing="0" cellpadding="0" class="table" >
  <tr>
    <td width="63%" class="table td"><p>Izena/Nombre</p>
    <p><strong>Silvergold</strong></p></td>
    <td width="37%" class="table td"><p>IFZ/NIF</p>
    <p><strong>'.$rsItem['numero_identificacion_tienda'].'</strong></p></td>
  </tr>
  <tr>
    <td class="table td"><p>Kalea/Calle</p>
    <p><strong>Arrontegui</strong></p></td>
    <td class="table td"><p>Zk./Nº</p>
    <p><strong>8</strong></p></td>
  </tr>
  <tr>
    <td class="table td"><p>Udalerria/Localidad</p>
    <p><strong>'.$rsItem['poblacion_tienda'].'</strong></p></td>
    <td class="table td"><p>Lurraldea/Provincia</p>
    <p><strong>Bizkaia</strong></p></td>
  </tr>
</table>

<table width="100%" cellspacing="0" cellpadding="0" class="table" >
  <tr>
    <td><h3>Operazio datuak / Datos de la operación</h3></td>
  </tr>
</table>

<table width="100%" cellspacing="0" cellpadding="0" class="table" >
  <tr>
    <td width="47%" class="table td"><p>Kontratu zk./nº de contrato</p>
    <p><strong>'.$rsItem['id_lote'].'</strong></p></td>
    <td width="53%" class="table td"><p>Data/Fecha</p>
    <p><strong>'.$sqldate.'</strong></p></td>
  </tr>
  <tr>
   <td class="table td"><p>Kontrato mota/Tipo de contrato</p>
	
	<p><input type="checkbox" name="radio" '.$comprado.' > Salerosi/Compraventa
	
	<input type="checkbox" name="radio"  '.$empenado.' > Bahitura/Empeño
	
	</p></td>
	
	<td class="table td"><p>Bahitura txartela/Papeleta empeño</p>
	
	<p><input type="checkbox" name="radio" > Bai/Si
	
	<input type="checkbox" name="radio" checked="checked" > Ez/No
	
	</p></td>
  </tr>
</table>

<table width="100%" cellspacing="0" cellpadding="0" class="table" >
  <tr>
    <td><h3>Interesatua/Interesado:</h3></td>
  </tr>
</table>

<table width="100%" cellspacing="0" cellpadding="0" class="table" >
  <tr>
    <td width="39%" class="table td"><p>Izena/Nombre</p>
    <p><strong>'.$rsItem['nombre'].'</strong></p></td>
    <td width="61%" class="table td"><p>Abezinak/Apellidos</p>
    <p><strong>'.$rsItem['apellido'].'</strong></p></td>
  </tr>
  
  <tr>
    <td class="table td">
	 <p>Dok. zk./Nº documento</p>
    <p>
	 <strong>'.$rsItem['identificacion'] .'</strong></p>
	 </td>
    <td class="table td">
	 <p>Mota/Tipo</p>
    <p>
	 <input type="checkbox" name="radio" '.$documento.'> ENA/DNI
	 <input type="checkbox" name="radio" '.$otrodocumento.'> Beste bat (zehaztu)/Otro (indicar):<strong> '.$otrod.'</strong>
	 </p>
	 </td>
  </tr>
  
  <tr>
   <td class="table td">
	<p>Sexua/Sexo</p>
	<p>
	<input type="checkbox" name="radio" '.$sexhombre.'>Gizonezkoa/Varón
	<input type="checkbox" name="radio" '.$sexmujer.'>Emakumezkoa/Mujer
	</p>
	</td>
   <td class="table td">
	<p>Jaiotze data/Fecha de nacimiento</p>
   <p><strong>'.$sqldatne.'</strong></p>
	</td>
  </tr>
  
  
  
  <tr>
    <td colspan="2" class="table td">
    <p>Jatorrizko herritartasuna/País de Nacionalidad</p>
    <p><input type="checkbox" name="radio" '.$nacion.'>Espainia/España
	 <input type="checkbox" name="radio" '.$otronacion.'>Beste bat (zehaztu)/Otro (indicar):<strong>'.$otron.'</strong>
	 </p>
	 </td>
  </tr>
  <tr>
    <td colspan="2" class="table td"><p>Ohiko bizilekua, kale eta zk./Domicilio actual: /calle y nº</p>
    <p><strong>'.$rsItem['direccion'] .'</strong></p></td>
  </tr>
  <tr>
    <td class="table td"><p>Udalerria/Localidad</p>
    <p><strong>'.$rsItem['c_poblacion'] .'</strong></p></td>
    <td class="table td"><p>Lurraldea/Provincia</p>
    <p><strong>'.$rsItem['c_provincia'] .'</strong></p></td>
  </tr>
</table>



<table width="100%" cellspacing="0" cellpadding="0" class="table" style="margin-top:10px;" >
  <tr>
    <td><h3>Kontratu honen objektua/k (banakakoak)/ Objeto/s del presente contrato (individualizados)</h3></td>
  </tr>
</table>
';

$html .= '
<table width="100%" cellspacing="0" cellpadding="0" class="table tablearticulo" >
  <tr>
    <td width="20%" class="table td"><p>Orden zk./Nº orden</p>
    <p><strong>'.$id_articulo_lote.'</strong></p></td>
    <td colspan="2" width="80%" height="60" class="table td"><p>Mota/Clase</p>
    <p><strong>'.$descripcion_articulo.'</strong></p></td>
  </tr>
  <tr>
    <td class="table td"><p>Pisua (gr)/peso (gr.)</p>
    <p><strong>'.$peso_articulo.'</strong></p></td>
	 <td class="table td"><p>Metalak/Metales </p>
    <p><strong>'.$tipo_de_articulo.'</strong></p></td>
    <td class="table td"><p>Kilate-pisua/peso quilates</p>
    <p><strong>'.$ley.'</strong></p></td>
  </tr>
  <tr>
    <td class="table td"><p>Grabaketak/grabaciones</p>
    <p><input type="checkbox" name="radio" '.$inscsi.' > Bai/Si
	 <input type="checkbox" name="radio" '.$inscno.' > Ez/No
	 </p>
	 </td>
    <td  colspan="2" class="table td"><p>Detaileak/Detalles  </p>
    <p><strong>'.$inscripciones.'</strong></p></td>
  </tr>
  <tr>
    <td class="table td"><p>Harriak/Piedras</p>
	 <p><input type="checkbox" name="radio" '.$piedcsi.' > Bai/Si
	 <input type="checkbox" name="radio" '.$piedno.' > Ez/No
	 </p>
	 </td>
	 <td class="table td"><p>Mota/clase</p>
    <p><strong>'.$descripcion_piedras.'</strong></p></td>
    <td class="table td"><p>Kilate-pisua/peso quilates</p>
    <p><strong>'.$kilate_piedras.'</strong></p></td>
  </tr>
  <tr>
    <td  colspan="2"  width="47%" class="table td"><p>Prezioa / Precio abonado</p>
    <p><strong>'.$precio_compra_articulo.'</strong></p></td>
    <td width="53%" class="table td"><p>Objektu argazkia (beharrezkoa)/Fotografia detallada objeto</p>
    <p><input type="checkbox" name="radio" checked="checked"> Bai/Si</p></td>
  </tr>
  <tr>
    <td  colspan="3"  width="100%" class="table td" height="15"><p>(*) Salmenta data /Fecha venta posterior: <strong></strong></p>
	 </td>
  </tr>
</table>
';

$html .= '
<table width="100%" cellspacing="0" cellpadding="0" class="table tablearticulo" >
  <tr>
    <td width="20%" class="table td"><p>Orden zk./Nº orden</p>
    <p><strong>'.$id_articulo_lote.'</strong></p></td>
    <td colspan="2" width="80%" height="60" class="table td"><p>Mota/Clase</p>
    <p><strong>'.$descripcion_articulo.'</strong></p></td>
  </tr>
  <tr>
    <td class="table td"><p>Pisua (gr)/peso (gr.)</p>
    <p><strong>'.$peso_articulo.'</strong></p></td>
	 <td class="table td"><p>Metalak/Metales </p>
    <p><strong>'.$tipo_de_articulo.'</strong></p></td>
    <td class="table td"><p>Kilate-pisua/peso quilates</p>
    <p><strong>'.$ley.'</strong></p></td>
  </tr>
  <tr>
    <td class="table td"><p>Grabaketak/grabaciones</p>
    <p><input type="checkbox" name="radio" '.$inscsi.' > Bai/Si
	 <input type="checkbox" name="radio" '.$inscno.' > Ez/No
	 </p>
	 </td>
    <td  colspan="2" class="table td"><p>Detaileak/Detalles  </p>
    <p><strong>'.$inscripciones.'</strong></p></td>
  </tr>
  <tr>
    <td class="table td"><p>Harriak/Piedras</p>
	 <p><input type="checkbox" name="radio" '.$piedcsi.' > Bai/Si
	 <input type="checkbox" name="radio" '.$piedno.' > Ez/No
	 </p>
	 </td>
	 <td class="table td"><p>Mota/clase</p>
    <p><strong>'.$descripcion_piedras.'</strong></p></td>
    <td class="table td"><p>Kilate-pisua/peso quilates</p>
    <p><strong>'.$kilate_piedras.'</strong></p></td>
  </tr>
  <tr>
    <td  colspan="2"  width="47%" class="table td"><p>Prezioa / Precio abonado</p>
    <p><strong>'.$precio_compra_articulo.'</strong></p></td>
    <td width="53%" class="table td"><p>Objektu argazkia (beharrezkoa)/Fotografia detallada objeto</p>
    <p><input type="checkbox" name="radio" checked="checked"> Bai/Si</p></td>
  </tr>
  <tr>
    <td  colspan="3"  width="100%" class="table td" height="15"><p>(*) Salmenta data /Fecha venta posterior: <strong></strong></p>
	 </td>
  </tr>
</table>
';

$html .= '
<table width="100%" cellspacing="0" cellpadding="0" class="table tablearticulo" >
  <tr>
    <td width="20%" class="table td"><p>Orden zk./Nº orden</p>
    <p><strong>'.$id_articulo_lote.'</strong></p></td>
    <td colspan="2" width="80%" height="60" class="table td"><p>Mota/Clase</p>
    <p><strong>'.$descripcion_articulo.'</strong></p></td>
  </tr>
  <tr>
    <td class="table td"><p>Pisua (gr)/peso (gr.)</p>
    <p><strong>'.$peso_articulo.'</strong></p></td>
	 <td class="table td"><p>Metalak/Metales </p>
    <p><strong>'.$tipo_de_articulo.'</strong></p></td>
    <td class="table td"><p>Kilate-pisua/peso quilates</p>
    <p><strong>'.$ley.'</strong></p></td>
  </tr>
  <tr>
    <td class="table td"><p>Grabaketak/grabaciones</p>
    <p><input type="checkbox" name="radio" '.$inscsi.' > Bai/Si
	 <input type="checkbox" name="radio" '.$inscno.' > Ez/No
	 </p>
	 </td>
    <td  colspan="2" class="table td"><p>Detaileak/Detalles  </p>
    <p><strong>'.$inscripciones.'</strong></p></td>
  </tr>
  <tr>
    <td class="table td"><p>Harriak/Piedras</p>
	 <p><input type="checkbox" name="radio" '.$piedcsi.' > Bai/Si
	 <input type="checkbox" name="radio" '.$piedno.' > Ez/No
	 </p>
	 </td>
	 <td class="table td"><p>Mota/clase</p>
    <p><strong>'.$descripcion_piedras.'</strong></p></td>
    <td class="table td"><p>Kilate-pisua/peso quilates</p>
    <p><strong>'.$kilate_piedras.'</strong></p></td>
  </tr>
  <tr>
    <td  colspan="2"  width="47%" class="table td"><p>Prezioa / Precio abonado</p>
    <p><strong>'.$precio_compra_articulo.'</strong></p></td>
    <td width="53%" class="table td"><p>Objektu argazkia (beharrezkoa)/Fotografia detallada objeto</p>
    <p><input type="checkbox" name="radio" checked="checked"> Bai/Si</p></td>
  </tr>
  <tr>
    <td  colspan="3"  width="100%" class="table td" height="15"><p>(*) Salmenta data /Fecha venta posterior: <strong></strong></p>
	 </td>
  </tr>
</table>
';

$html .= '
<table width="100%" cellspacing="0" cellpadding="0" class="table tablearticulo" >
  <tr>
    <td width="20%" class="table td"><p>Orden zk./Nº orden</p>
    <p><strong>'.$id_articulo_lote.'</strong></p></td>
    <td colspan="2" width="80%" height="60" class="table td"><p>Mota/Clase</p>
    <p><strong>'.$descripcion_articulo.'</strong></p></td>
  </tr>
  <tr>
    <td class="table td"><p>Pisua (gr)/peso (gr.)</p>
    <p><strong>'.$peso_articulo.'</strong></p></td>
	 <td class="table td"><p>Metalak/Metales </p>
    <p><strong>'.$tipo_de_articulo.'</strong></p></td>
    <td class="table td"><p>Kilate-pisua/peso quilates</p>
    <p><strong>'.$ley.'</strong></p></td>
  </tr>
  <tr>
    <td class="table td"><p>Grabaketak/grabaciones</p>
    <p><input type="checkbox" name="radio" '.$inscsi.' > Bai/Si
	 <input type="checkbox" name="radio" '.$inscno.' > Ez/No
	 </p>
	 </td>
    <td  colspan="2" class="table td"><p>Detaileak/Detalles  </p>
    <p><strong>'.$inscripciones.'</strong></p></td>
  </tr>
  <tr>
    <td class="table td"><p>Harriak/Piedras</p>
	 <p><input type="checkbox" name="radio" '.$piedcsi.' > Bai/Si
	 <input type="checkbox" name="radio" '.$piedno.' > Ez/No
	 </p>
	 </td>
	 <td class="table td"><p>Mota/clase</p>
    <p><strong>'.$descripcion_piedras.'</strong></p></td>
    <td class="table td"><p>Kilate-pisua/peso quilates</p>
    <p><strong>'.$kilate_piedras.'</strong></p></td>
  </tr>
  <tr>
    <td  colspan="2"  width="47%" class="table td"><p>Prezioa / Precio abonado</p>
    <p><strong>'.$precio_compra_articulo.'</strong></p></td>
    <td width="53%" class="table td"><p>Objektu argazkia (beharrezkoa)/Fotografia detallada objeto</p>
    <p><input type="checkbox" name="radio" checked="checked"> Bai/Si</p></td>
  </tr>
  <tr>
    <td  colspan="3"  width="100%" class="table td" height="15"><p>(*) Salmenta data /Fecha venta posterior: <strong></strong></p>
	 </td>
  </tr>
</table>
';

$html .= '
<table width="100%" cellspacing="0" cellpadding="0" class="table tablearticulo" >
  <tr>
    <td width="20%" class="table td"><p>Orden zk./Nº orden</p>
    <p><strong>'.$id_articulo_lote.'</strong></p></td>
    <td colspan="2" width="80%" height="60" class="table td"><p>Mota/Clase</p>
    <p><strong>'.$descripcion_articulo.'</strong></p></td>
  </tr>
  <tr>
    <td class="table td"><p>Pisua (gr)/peso (gr.)</p>
    <p><strong>'.$peso_articulo.'</strong></p></td>
	 <td class="table td"><p>Metalak/Metales </p>
    <p><strong>'.$tipo_de_articulo.'</strong></p></td>
    <td class="table td"><p>Kilate-pisua/peso quilates</p>
    <p><strong>'.$ley.'</strong></p></td>
  </tr>
  <tr>
    <td class="table td"><p>Grabaketak/grabaciones</p>
    <p><input type="checkbox" name="radio" '.$inscsi.' > Bai/Si
	 <input type="checkbox" name="radio" '.$inscno.' > Ez/No
	 </p>
	 </td>
    <td  colspan="2" class="table td"><p>Detaileak/Detalles  </p>
    <p><strong>'.$inscripciones.'</strong></p></td>
  </tr>
  <tr>
    <td class="table td"><p>Harriak/Piedras</p>
	 <p><input type="checkbox" name="radio" '.$piedcsi.' > Bai/Si
	 <input type="checkbox" name="radio" '.$piedno.' > Ez/No
	 </p>
	 </td>
	 <td class="table td"><p>Mota/clase</p>
    <p><strong>'.$descripcion_piedras.'</strong></p></td>
    <td class="table td"><p>Kilate-pisua/peso quilates</p>
    <p><strong>'.$kilate_piedras.'</strong></p></td>
  </tr>
  <tr>
    <td  colspan="2"  width="47%" class="table td"><p>Prezioa / Precio abonado</p>
    <p><strong>'.$precio_compra_articulo.'</strong></p></td>
    <td width="53%" class="table td"><p>Objektu argazkia (beharrezkoa)/Fotografia detallada objeto</p>
    <p><input type="checkbox" name="radio" checked="checked"> Bai/Si</p></td>
  </tr>
  <tr>
    <td  colspan="3"  width="100%" class="table td" height="15"><p>(*) Salmenta data /Fecha venta posterior: <strong></strong></p>
	 </td>
  </tr>
</table>
';

$html .= '
<table width="100%" cellspacing="0" cellpadding="0" class="table tablearticulo" >
  <tr>
    <td width="20%" class="table td"><p>Orden zk./Nº orden</p>
    <p><strong>'.$id_articulo_lote.'</strong></p></td>
    <td colspan="2" width="80%" height="60" class="table td"><p>Mota/Clase</p>
    <p><strong>'.$descripcion_articulo.'</strong></p></td>
  </tr>
  <tr>
    <td class="table td"><p>Pisua (gr)/peso (gr.)</p>
    <p><strong>'.$peso_articulo.'</strong></p></td>
	 <td class="table td"><p>Metalak/Metales </p>
    <p><strong>'.$tipo_de_articulo.'</strong></p></td>
    <td class="table td"><p>Kilate-pisua/peso quilates</p>
    <p><strong>'.$ley.'</strong></p></td>
  </tr>
  <tr>
    <td class="table td"><p>Grabaketak/grabaciones</p>
    <p><input type="checkbox" name="radio" '.$inscsi.' > Bai/Si
	 <input type="checkbox" name="radio" '.$inscno.' > Ez/No
	 </p>
	 </td>
    <td  colspan="2" class="table td"><p>Detaileak/Detalles  </p>
    <p><strong>'.$inscripciones.'</strong></p></td>
  </tr>
  <tr>
    <td class="table td"><p>Harriak/Piedras</p>
	 <p><input type="checkbox" name="radio" '.$piedcsi.' > Bai/Si
	 <input type="checkbox" name="radio" '.$piedno.' > Ez/No
	 </p>
	 </td>
	 <td class="table td"><p>Mota/clase</p>
    <p><strong>'.$descripcion_piedras.'</strong></p></td>
    <td class="table td"><p>Kilate-pisua/peso quilates</p>
    <p><strong>'.$kilate_piedras.'</strong></p></td>
  </tr>
  <tr>
    <td  colspan="2"  width="47%" class="table td"><p>Prezioa / Precio abonado</p>
    <p><strong>'.$precio_compra_articulo.'</strong></p></td>
    <td width="53%" class="table td"><p>Objektu argazkia (beharrezkoa)/Fotografia detallada objeto</p>
    <p><input type="checkbox" name="radio" checked="checked"> Bai/Si</p></td>
  </tr>
  <tr>
    <td  colspan="3"  width="100%" class="table td" height="15"><p>(*) Salmenta data /Fecha venta posterior: <strong></strong></p>
	 </td>
  </tr>
</table>
';

$html .= '
<table width="100%" cellspacing="0" cellpadding="0" class="table tablearticulo" >
  <tr>
    <td width="20%" class="table td"><p>Orden zk./Nº orden</p>
    <p><strong>'.$id_articulo_lote.'</strong></p></td>
    <td colspan="2" width="80%" height="60" class="table td"><p>Mota/Clase</p>
    <p><strong>'.$descripcion_articulo.'</strong></p></td>
  </tr>
  <tr>
    <td class="table td"><p>Pisua (gr)/peso (gr.)</p>
    <p><strong>'.$peso_articulo.'</strong></p></td>
	 <td class="table td"><p>Metalak/Metales </p>
    <p><strong>'.$tipo_de_articulo.'</strong></p></td>
    <td class="table td"><p>Kilate-pisua/peso quilates</p>
    <p><strong>'.$ley.'</strong></p></td>
  </tr>
  <tr>
    <td class="table td"><p>Grabaketak/grabaciones</p>
    <p><input type="checkbox" name="radio" '.$inscsi.' > Bai/Si
	 <input type="checkbox" name="radio" '.$inscno.' > Ez/No
	 </p>
	 </td>
    <td  colspan="2" class="table td"><p>Detaileak/Detalles  </p>
    <p><strong>'.$inscripciones.'</strong></p></td>
  </tr>
  <tr>
    <td class="table td"><p>Harriak/Piedras</p>
	 <p><input type="checkbox" name="radio" '.$piedcsi.' > Bai/Si
	 <input type="checkbox" name="radio" '.$piedno.' > Ez/No
	 </p>
	 </td>
	 <td class="table td"><p>Mota/clase</p>
    <p><strong>'.$descripcion_piedras.'</strong></p></td>
    <td class="table td"><p>Kilate-pisua/peso quilates</p>
    <p><strong>'.$kilate_piedras.'</strong></p></td>
  </tr>
  <tr>
    <td  colspan="2"  width="47%" class="table td"><p>Prezioa / Precio abonado</p>
    <p><strong>'.$precio_compra_articulo.'</strong></p></td>
    <td width="53%" class="table td"><p>Objektu argazkia (beharrezkoa)/Fotografia detallada objeto</p>
    <p><input type="checkbox" name="radio" checked="checked"> Bai/Si</p></td>
  </tr>
  <tr>
    <td  colspan="3"  width="100%" class="table td" height="15"><p>(*) Salmenta data /Fecha venta posterior: <strong></strong></p>
	 </td>
  </tr>
</table>
';

$html .= '
<table width="100%" cellspacing="0" cellpadding="0" class="table tablearticulo" >
  <tr>
    <td width="20%" class="table td"><p>Orden zk./Nº orden</p>
    <p><strong>'.$id_articulo_lote.'</strong></p></td>
    <td colspan="2" width="80%" height="60" class="table td"><p>Mota/Clase</p>
    <p><strong>'.$descripcion_articulo.'</strong></p></td>
  </tr>
  <tr>
    <td class="table td"><p>Pisua (gr)/peso (gr.)</p>
    <p><strong>'.$peso_articulo.'</strong></p></td>
	 <td class="table td"><p>Metalak/Metales </p>
    <p><strong>'.$tipo_de_articulo.'</strong></p></td>
    <td class="table td"><p>Kilate-pisua/peso quilates</p>
    <p><strong>'.$ley.'</strong></p></td>
  </tr>
  <tr>
    <td class="table td"><p>Grabaketak/grabaciones</p>
    <p><input type="checkbox" name="radio" '.$inscsi.' > Bai/Si
	 <input type="checkbox" name="radio" '.$inscno.' > Ez/No
	 </p>
	 </td>
    <td  colspan="2" class="table td"><p>Detaileak/Detalles  </p>
    <p><strong>'.$inscripciones.'</strong></p></td>
  </tr>
  <tr>
    <td class="table td"><p>Harriak/Piedras</p>
	 <p><input type="checkbox" name="radio" '.$piedcsi.' > Bai/Si
	 <input type="checkbox" name="radio" '.$piedno.' > Ez/No
	 </p>
	 </td>
	 <td class="table td"><p>Mota/clase</p>
    <p><strong>'.$descripcion_piedras.'</strong></p></td>
    <td class="table td"><p>Kilate-pisua/peso quilates</p>
    <p><strong>'.$kilate_piedras.'</strong></p></td>
  </tr>
  <tr>
    <td  colspan="2"  width="47%" class="table td"><p>Prezioa / Precio abonado</p>
    <p><strong>'.$precio_compra_articulo.'</strong></p></td>
    <td width="53%" class="table td"><p>Objektu argazkia (beharrezkoa)/Fotografia detallada objeto</p>
    <p><input type="checkbox" name="radio" checked="checked"> Bai/Si</p></td>
  </tr>
  <tr>
    <td  colspan="3"  width="100%" class="table td" height="15"><p>(*) Salmenta data /Fecha venta posterior: <strong></strong></p>
	 </td>
  </tr>
</table>
';

$html .= '
<table width="100%" cellspacing="0" cellpadding="0" class="table tablearticulo" >
  <tr>
    <td width="20%" class="table td"><p>Orden zk./Nº orden</p>
    <p><strong>'.$id_articulo_lote.'</strong></p></td>
    <td colspan="2" width="80%" height="60" class="table td"><p>Mota/Clase</p>
    <p><strong>'.$descripcion_articulo.'</strong></p></td>
  </tr>
  <tr>
    <td class="table td"><p>Pisua (gr)/peso (gr.)</p>
    <p><strong>'.$peso_articulo.'</strong></p></td>
	 <td class="table td"><p>Metalak/Metales </p>
    <p><strong>'.$tipo_de_articulo.'</strong></p></td>
    <td class="table td"><p>Kilate-pisua/peso quilates</p>
    <p><strong>'.$ley.'</strong></p></td>
  </tr>
  <tr>
    <td class="table td"><p>Grabaketak/grabaciones</p>
    <p><input type="checkbox" name="radio" '.$inscsi.' > Bai/Si
	 <input type="checkbox" name="radio" '.$inscno.' > Ez/No
	 </p>
	 </td>
    <td  colspan="2" class="table td"><p>Detaileak/Detalles  </p>
    <p><strong>'.$inscripciones.'</strong></p></td>
  </tr>
  <tr>
    <td class="table td"><p>Harriak/Piedras</p>
	 <p><input type="checkbox" name="radio" '.$piedcsi.' > Bai/Si
	 <input type="checkbox" name="radio" '.$piedno.' > Ez/No
	 </p>
	 </td>
	 <td class="table td"><p>Mota/clase</p>
    <p><strong>'.$descripcion_piedras.'</strong></p></td>
    <td class="table td"><p>Kilate-pisua/peso quilates</p>
    <p><strong>'.$kilate_piedras.'</strong></p></td>
  </tr>
  <tr>
    <td  colspan="2"  width="47%" class="table td"><p>Prezioa / Precio abonado</p>
    <p><strong>'.$precio_compra_articulo.'</strong></p></td>
    <td width="53%" class="table td"><p>Objektu argazkia (beharrezkoa)/Fotografia detallada objeto</p>
    <p><input type="checkbox" name="radio" checked="checked"> Bai/Si</p></td>
  </tr>
  <tr>
    <td  colspan="3"  width="100%" class="table td" height="15"><p>(*) Salmenta data /Fecha venta posterior: <strong></strong></p>
	 </td>
  </tr>
</table>
';

$html .= '
<table width="100%" cellspacing="0" cellpadding="0" class="table tablearticulo" >
  <tr>
    <td width="20%" class="table td"><p>Orden zk./Nº orden</p>
    <p><strong>'.$id_articulo_lote.'</strong></p></td>
    <td colspan="2" width="80%" height="60" class="table td"><p>Mota/Clase</p>
    <p><strong>'.$descripcion_articulo.'</strong></p></td>
  </tr>
  <tr>
    <td class="table td"><p>Pisua (gr)/peso (gr.)</p>
    <p><strong>'.$peso_articulo.'</strong></p></td>
	 <td class="table td"><p>Metalak/Metales </p>
    <p><strong>'.$tipo_de_articulo.'</strong></p></td>
    <td class="table td"><p>Kilate-pisua/peso quilates</p>
    <p><strong>'.$ley.'</strong></p></td>
  </tr>
  <tr>
    <td class="table td"><p>Grabaketak/grabaciones</p>
    <p><input type="checkbox" name="radio" '.$inscsi.' > Bai/Si
	 <input type="checkbox" name="radio" '.$inscno.' > Ez/No
	 </p>
	 </td>
    <td  colspan="2" class="table td"><p>Detaileak/Detalles  </p>
    <p><strong>'.$inscripciones.'</strong></p></td>
  </tr>
  <tr>
    <td class="table td"><p>Harriak/Piedras</p>
	 <p><input type="checkbox" name="radio" '.$piedcsi.' > Bai/Si
	 <input type="checkbox" name="radio" '.$piedno.' > Ez/No
	 </p>
	 </td>
	 <td class="table td"><p>Mota/clase</p>
    <p><strong>'.$descripcion_piedras.'</strong></p></td>
    <td class="table td"><p>Kilate-pisua/peso quilates</p>
    <p><strong>'.$kilate_piedras.'</strong></p></td>
  </tr>
  <tr>
    <td  colspan="2"  width="47%" class="table td"><p>Prezioa / Precio abonado</p>
    <p><strong>'.$precio_compra_articulo.'</strong></p></td>
    <td width="53%" class="table td"><p>Objektu argazkia (beharrezkoa)/Fotografia detallada objeto</p>
    <p><input type="checkbox" name="radio" checked="checked"> Bai/Si</p></td>
  </tr>
  <tr>
    <td  colspan="3"  width="100%" class="table td" height="15"><p>(*) Salmenta data /Fecha venta posterior: <strong></strong></p>
	 </td>
  </tr>
</table>
';

$html .= '
<table width="100%" cellspacing="0" cellpadding="0" class="table tablearticulo" >
  <tr>
    <td width="20%" class="table td"><p>Orden zk./Nº orden</p>
    <p><strong>'.$id_articulo_lote.'</strong></p></td>
    <td colspan="2" width="80%" height="60" class="table td"><p>Mota/Clase</p>
    <p><strong>'.$descripcion_articulo.'</strong></p></td>
  </tr>
  <tr>
    <td class="table td"><p>Pisua (gr)/peso (gr.)</p>
    <p><strong>'.$peso_articulo.'</strong></p></td>
	 <td class="table td"><p>Metalak/Metales </p>
    <p><strong>'.$tipo_de_articulo.'</strong></p></td>
    <td class="table td"><p>Kilate-pisua/peso quilates</p>
    <p><strong>'.$ley.'</strong></p></td>
  </tr>
  <tr>
    <td class="table td"><p>Grabaketak/grabaciones</p>
    <p><input type="checkbox" name="radio" '.$inscsi.' > Bai/Si
	 <input type="checkbox" name="radio" '.$inscno.' > Ez/No
	 </p>
	 </td>
    <td  colspan="2" class="table td"><p>Detaileak/Detalles  </p>
    <p><strong>'.$inscripciones.'</strong></p></td>
  </tr>
  <tr>
    <td class="table td"><p>Harriak/Piedras</p>
	 <p><input type="checkbox" name="radio" '.$piedcsi.' > Bai/Si
	 <input type="checkbox" name="radio" '.$piedno.' > Ez/No
	 </p>
	 </td>
	 <td class="table td"><p>Mota/clase</p>
    <p><strong>'.$descripcion_piedras.'</strong></p></td>
    <td class="table td"><p>Kilate-pisua/peso quilates</p>
    <p><strong>'.$kilate_piedras.'</strong></p></td>
  </tr>
  <tr>
    <td  colspan="2"  width="47%" class="table td"><p>Prezioa / Precio abonado</p>
    <p><strong>'.$precio_compra_articulo.'</strong></p></td>
    <td width="53%" class="table td"><p>Objektu argazkia (beharrezkoa)/Fotografia detallada objeto</p>
    <p><input type="checkbox" name="radio" checked="checked"> Bai/Si</p></td>
  </tr>
  <tr>
    <td  colspan="3"  width="100%" class="table td" height="15"><p>(*) Salmenta data /Fecha venta posterior: <strong></strong></p>
	 </td>
  </tr>
</table>
';

$html .= '
<table width="100%" cellspacing="0" cellpadding="0" class="table tablearticulo" >
  <tr>
    <td width="20%" class="table td"><p>Orden zk./Nº orden</p>
    <p><strong>'.$id_articulo_lote.'</strong></p></td>
    <td colspan="2" width="80%" height="60" class="table td"><p>Mota/Clase</p>
    <p><strong>'.$descripcion_articulo.'</strong></p></td>
  </tr>
  <tr>
    <td class="table td"><p>Pisua (gr)/peso (gr.)</p>
    <p><strong>'.$peso_articulo.'</strong></p></td>
	 <td class="table td"><p>Metalak/Metales </p>
    <p><strong>'.$tipo_de_articulo.'</strong></p></td>
    <td class="table td"><p>Kilate-pisua/peso quilates</p>
    <p><strong>'.$ley.'</strong></p></td>
  </tr>
  <tr>
    <td class="table td"><p>Grabaketak/grabaciones</p>
    <p><input type="checkbox" name="radio" '.$inscsi.' > Bai/Si
	 <input type="checkbox" name="radio" '.$inscno.' > Ez/No
	 </p>
	 </td>
    <td  colspan="2" class="table td"><p>Detaileak/Detalles  </p>
    <p><strong>'.$inscripciones.'</strong></p></td>
  </tr>
  <tr>
    <td class="table td"><p>Harriak/Piedras</p>
	 <p><input type="checkbox" name="radio" '.$piedcsi.' > Bai/Si
	 <input type="checkbox" name="radio" '.$piedno.' > Ez/No
	 </p>
	 </td>
	 <td class="table td"><p>Mota/clase</p>
    <p><strong>'.$descripcion_piedras.'</strong></p></td>
    <td class="table td"><p>Kilate-pisua/peso quilates</p>
    <p><strong>'.$kilate_piedras.'</strong></p></td>
  </tr>
  <tr>
    <td  colspan="2"  width="47%" class="table td"><p>Prezioa / Precio abonado</p>
    <p><strong>'.$precio_compra_articulo.'</strong></p></td>
    <td width="53%" class="table td"><p>Objektu argazkia (beharrezkoa)/Fotografia detallada objeto</p>
    <p><input type="checkbox" name="radio" checked="checked"> Bai/Si</p></td>
  </tr>
  <tr>
    <td  colspan="3"  width="100%" class="table td" height="15"><p>(*) Salmenta data /Fecha venta posterior: <strong></strong></p>
	 </td>
  </tr>
</table>
';

$html .= '
<table width="100%" cellspacing="0" cellpadding="0" class="table tablearticulo" >
  <tr>
    <td width="20%" class="table td"><p>Orden zk./Nº orden</p>
    <p><strong>'.$id_articulo_lote.'</strong></p></td>
    <td colspan="2" width="80%" height="60" class="table td"><p>Mota/Clase</p>
    <p><strong>'.$descripcion_articulo.'</strong></p></td>
  </tr>
  <tr>
    <td class="table td"><p>Pisua (gr)/peso (gr.)</p>
    <p><strong>'.$peso_articulo.'</strong></p></td>
	 <td class="table td"><p>Metalak/Metales </p>
    <p><strong>'.$tipo_de_articulo.'</strong></p></td>
    <td class="table td"><p>Kilate-pisua/peso quilates</p>
    <p><strong>'.$ley.'</strong></p></td>
  </tr>
  <tr>
    <td class="table td"><p>Grabaketak/grabaciones</p>
    <p><input type="checkbox" name="radio" '.$inscsi.' > Bai/Si
	 <input type="checkbox" name="radio" '.$inscno.' > Ez/No
	 </p>
	 </td>
    <td  colspan="2" class="table td"><p>Detaileak/Detalles  </p>
    <p><strong>'.$inscripciones.'</strong></p></td>
  </tr>
  <tr>
    <td class="table td"><p>Harriak/Piedras</p>
	 <p><input type="checkbox" name="radio" '.$piedcsi.' > Bai/Si
	 <input type="checkbox" name="radio" '.$piedno.' > Ez/No
	 </p>
	 </td>
	 <td class="table td"><p>Mota/clase</p>
    <p><strong>'.$descripcion_piedras.'</strong></p></td>
    <td class="table td"><p>Kilate-pisua/peso quilates</p>
    <p><strong>'.$kilate_piedras.'</strong></p></td>
  </tr>
  <tr>
    <td  colspan="2"  width="47%" class="table td"><p>Prezioa / Precio abonado</p>
    <p><strong>'.$precio_compra_articulo.'</strong></p></td>
    <td width="53%" class="table td"><p>Objektu argazkia (beharrezkoa)/Fotografia detallada objeto</p>
    <p><input type="checkbox" name="radio" checked="checked"> Bai/Si</p></td>
  </tr>
  <tr>
    <td  colspan="3"  width="100%" class="table td" height="15"><p>(*) Salmenta data /Fecha venta posterior: <strong></strong></p>
	 </td>
  </tr>
</table>
';

$html .= '
<table width="100%" cellspacing="0" cellpadding="0" class="table tablearticulo" >
  <tr>
    <td width="20%" class="table td"><p>Orden zk./Nº orden</p>
    <p><strong>'.$id_articulo_lote.'</strong></p></td>
    <td colspan="2" width="80%" height="60" class="table td"><p>Mota/Clase</p>
    <p><strong>'.$descripcion_articulo.'</strong></p></td>
  </tr>
  <tr>
    <td class="table td"><p>Pisua (gr)/peso (gr.)</p>
    <p><strong>'.$peso_articulo.'</strong></p></td>
	 <td class="table td"><p>Metalak/Metales </p>
    <p><strong>'.$tipo_de_articulo.'</strong></p></td>
    <td class="table td"><p>Kilate-pisua/peso quilates</p>
    <p><strong>'.$ley.'</strong></p></td>
  </tr>
  <tr>
    <td class="table td"><p>Grabaketak/grabaciones</p>
    <p><input type="checkbox" name="radio" '.$inscsi.' > Bai/Si
	 <input type="checkbox" name="radio" '.$inscno.' > Ez/No
	 </p>
	 </td>
    <td  colspan="2" class="table td"><p>Detaileak/Detalles  </p>
    <p><strong>'.$inscripciones.'</strong></p></td>
  </tr>
  <tr>
    <td class="table td"><p>Harriak/Piedras</p>
	 <p><input type="checkbox" name="radio" '.$piedcsi.' > Bai/Si
	 <input type="checkbox" name="radio" '.$piedno.' > Ez/No
	 </p>
	 </td>
	 <td class="table td"><p>Mota/clase</p>
    <p><strong>'.$descripcion_piedras.'</strong></p></td>
    <td class="table td"><p>Kilate-pisua/peso quilates</p>
    <p><strong>'.$kilate_piedras.'</strong></p></td>
  </tr>
  <tr>
    <td  colspan="2"  width="47%" class="table td"><p>Prezioa / Precio abonado</p>
    <p><strong>'.$precio_compra_articulo.'</strong></p></td>
    <td width="53%" class="table td"><p>Objektu argazkia (beharrezkoa)/Fotografia detallada objeto</p>
    <p><input type="checkbox" name="radio" checked="checked"> Bai/Si</p></td>
  </tr>
  <tr>
    <td  colspan="3"  width="100%" class="table td" height="15"><p>(*) Salmenta data /Fecha venta posterior: <strong></strong></p>
	 </td>
  </tr>
</table>
';

$html .= '
<table width="100%" cellspacing="0" cellpadding="0" class="table tablearticulo" >
  <tr>
    <td width="20%" class="table td"><p>Orden zk./Nº orden</p>
    <p><strong>'.$id_articulo_lote.'</strong></p></td>
    <td colspan="2" width="80%" height="60" class="table td"><p>Mota/Clase</p>
    <p><strong>'.$descripcion_articulo.'</strong></p></td>
  </tr>
  <tr>
    <td class="table td"><p>Pisua (gr)/peso (gr.)</p>
    <p><strong>'.$peso_articulo.'</strong></p></td>
	 <td class="table td"><p>Metalak/Metales </p>
    <p><strong>'.$tipo_de_articulo.'</strong></p></td>
    <td class="table td"><p>Kilate-pisua/peso quilates</p>
    <p><strong>'.$ley.'</strong></p></td>
  </tr>
  <tr>
    <td class="table td"><p>Grabaketak/grabaciones</p>
    <p><input type="checkbox" name="radio" '.$inscsi.' > Bai/Si
	 <input type="checkbox" name="radio" '.$inscno.' > Ez/No
	 </p>
	 </td>
    <td  colspan="2" class="table td"><p>Detaileak/Detalles  </p>
    <p><strong>'.$inscripciones.'</strong></p></td>
  </tr>
  <tr>
    <td class="table td"><p>Harriak/Piedras</p>
	 <p><input type="checkbox" name="radio" '.$piedcsi.' > Bai/Si
	 <input type="checkbox" name="radio" '.$piedno.' > Ez/No
	 </p>
	 </td>
	 <td class="table td"><p>Mota/clase</p>
    <p><strong>'.$descripcion_piedras.'</strong></p></td>
    <td class="table td"><p>Kilate-pisua/peso quilates</p>
    <p><strong>'.$kilate_piedras.'</strong></p></td>
  </tr>
  <tr>
    <td  colspan="2"  width="47%" class="table td"><p>Prezioa / Precio abonado</p>
    <p><strong>'.$precio_compra_articulo.'</strong></p></td>
    <td width="53%" class="table td"><p>Objektu argazkia (beharrezkoa)/Fotografia detallada objeto</p>
    <p><input type="checkbox" name="radio" checked="checked"> Bai/Si</p></td>
  </tr>
  <tr>
    <td  colspan="3"  width="100%" class="table td" height="15"><p>(*) Salmenta data /Fecha venta posterior: <strong></strong></p>
	 </td>
  </tr>
</table>
';

$html .= '
<table width="100%" cellspacing="0" cellpadding="0" class="table tablearticulo" >
  <tr>
    <td width="20%" class="table td"><p>Orden zk./Nº orden</p>
    <p><strong>'.$id_articulo_lote.'</strong></p></td>
    <td colspan="2" width="80%" height="60" class="table td"><p>Mota/Clase</p>
    <p><strong>'.$descripcion_articulo.'</strong></p></td>
  </tr>
  <tr>
    <td class="table td"><p>Pisua (gr)/peso (gr.)</p>
    <p><strong>'.$peso_articulo.'</strong></p></td>
	 <td class="table td"><p>Metalak/Metales </p>
    <p><strong>'.$tipo_de_articulo.'</strong></p></td>
    <td class="table td"><p>Kilate-pisua/peso quilates</p>
    <p><strong>'.$ley.'</strong></p></td>
  </tr>
  <tr>
    <td class="table td"><p>Grabaketak/grabaciones</p>
    <p><input type="checkbox" name="radio" '.$inscsi.' > Bai/Si
	 <input type="checkbox" name="radio" '.$inscno.' > Ez/No
	 </p>
	 </td>
    <td  colspan="2" class="table td"><p>Detaileak/Detalles  </p>
    <p><strong>'.$inscripciones.'</strong></p></td>
  </tr>
  <tr>
    <td class="table td"><p>Harriak/Piedras</p>
	 <p><input type="checkbox" name="radio" '.$piedcsi.' > Bai/Si
	 <input type="checkbox" name="radio" '.$piedno.' > Ez/No
	 </p>
	 </td>
	 <td class="table td"><p>Mota/clase</p>
    <p><strong>'.$descripcion_piedras.'</strong></p></td>
    <td class="table td"><p>Kilate-pisua/peso quilates</p>
    <p><strong>'.$kilate_piedras.'</strong></p></td>
  </tr>
  <tr>
    <td  colspan="2"  width="47%" class="table td"><p>Prezioa / Precio abonado</p>
    <p><strong>'.$precio_compra_articulo.'</strong></p></td>
    <td width="53%" class="table td"><p>Objektu argazkia (beharrezkoa)/Fotografia detallada objeto</p>
    <p><input type="checkbox" name="radio" checked="checked"> Bai/Si</p></td>
  </tr>
  <tr>
    <td  colspan="3"  width="100%" class="table td" height="15"><p>(*) Salmenta data /Fecha venta posterior: <strong></strong></p>
	 </td>
  </tr>
</table>
';

$html .= '
<table width="100%" cellspacing="0" cellpadding="0" class="table tablearticulo" >
  <tr>
    <td width="20%" class="table td"><p>Orden zk./Nº orden</p>
    <p><strong>'.$id_articulo_lote.'</strong></p></td>
    <td colspan="2" width="80%" height="60" class="table td"><p>Mota/Clase</p>
    <p><strong>'.$descripcion_articulo.'</strong></p></td>
  </tr>
  <tr>
    <td class="table td"><p>Pisua (gr)/peso (gr.)</p>
    <p><strong>'.$peso_articulo.'</strong></p></td>
	 <td class="table td"><p>Metalak/Metales </p>
    <p><strong>'.$tipo_de_articulo.'</strong></p></td>
    <td class="table td"><p>Kilate-pisua/peso quilates</p>
    <p><strong>'.$ley.'</strong></p></td>
  </tr>
  <tr>
    <td class="table td"><p>Grabaketak/grabaciones</p>
    <p><input type="checkbox" name="radio" '.$inscsi.' > Bai/Si
	 <input type="checkbox" name="radio" '.$inscno.' > Ez/No
	 </p>
	 </td>
    <td  colspan="2" class="table td"><p>Detaileak/Detalles  </p>
    <p><strong>'.$inscripciones.'</strong></p></td>
  </tr>
  <tr>
    <td class="table td"><p>Harriak/Piedras</p>
	 <p><input type="checkbox" name="radio" '.$piedcsi.' > Bai/Si
	 <input type="checkbox" name="radio" '.$piedno.' > Ez/No
	 </p>
	 </td>
	 <td class="table td"><p>Mota/clase</p>
    <p><strong>'.$descripcion_piedras.'</strong></p></td>
    <td class="table td"><p>Kilate-pisua/peso quilates</p>
    <p><strong>'.$kilate_piedras.'</strong></p></td>
  </tr>
  <tr>
    <td  colspan="2"  width="47%" class="table td"><p>Prezioa / Precio abonado</p>
    <p><strong>'.$precio_compra_articulo.'</strong></p></td>
    <td width="53%" class="table td"><p>Objektu argazkia (beharrezkoa)/Fotografia detallada objeto</p>
    <p><input type="checkbox" name="radio" checked="checked"> Bai/Si</p></td>
  </tr>
  <tr>
    <td  colspan="3"  width="100%" class="table td" height="15"><p>(*) Salmenta data /Fecha venta posterior: <strong></strong></p>
	 </td>
  </tr>
</table>
';

$html .= '
<table width="100%" cellspacing="0" cellpadding="0" class="table tablearticulo" >
  <tr>
    <td width="20%" class="table td"><p>Orden zk./Nº orden</p>
    <p><strong>'.$id_articulo_lote.'</strong></p></td>
    <td colspan="2" width="80%" height="60" class="table td"><p>Mota/Clase</p>
    <p><strong>'.$descripcion_articulo.'</strong></p></td>
  </tr>
  <tr>
    <td class="table td"><p>Pisua (gr)/peso (gr.)</p>
    <p><strong>'.$peso_articulo.'</strong></p></td>
	 <td class="table td"><p>Metalak/Metales </p>
    <p><strong>'.$tipo_de_articulo.'</strong></p></td>
    <td class="table td"><p>Kilate-pisua/peso quilates</p>
    <p><strong>'.$ley.'</strong></p></td>
  </tr>
  <tr>
    <td class="table td"><p>Grabaketak/grabaciones</p>
    <p><input type="checkbox" name="radio" '.$inscsi.' > Bai/Si
	 <input type="checkbox" name="radio" '.$inscno.' > Ez/No
	 </p>
	 </td>
    <td  colspan="2" class="table td"><p>Detaileak/Detalles  </p>
    <p><strong>'.$inscripciones.'</strong></p></td>
  </tr>
  <tr>
    <td class="table td"><p>Harriak/Piedras</p>
	 <p><input type="checkbox" name="radio" '.$piedcsi.' > Bai/Si
	 <input type="checkbox" name="radio" '.$piedno.' > Ez/No
	 </p>
	 </td>
	 <td class="table td"><p>Mota/clase</p>
    <p><strong>'.$descripcion_piedras.'</strong></p></td>
    <td class="table td"><p>Kilate-pisua/peso quilates</p>
    <p><strong>'.$kilate_piedras.'</strong></p></td>
  </tr>
  <tr>
    <td  colspan="2"  width="47%" class="table td"><p>Prezioa / Precio abonado</p>
    <p><strong>'.$precio_compra_articulo.'</strong></p></td>
    <td width="53%" class="table td"><p>Objektu argazkia (beharrezkoa)/Fotografia detallada objeto</p>
    <p><input type="checkbox" name="radio" checked="checked"> Bai/Si</p></td>
  </tr>
  <tr>
    <td  colspan="3"  width="100%" class="table td" height="15"><p>(*) Salmenta data /Fecha venta posterior: <strong></strong></p>
	 </td>
  </tr>
</table>
';

$html .= '
<table width="100%" cellspacing="0" cellpadding="0" class="table tablearticulo" >
  <tr>
    <td width="20%" class="table td"><p>Orden zk./Nº orden</p>
    <p><strong>'.$id_articulo_lote.'</strong></p></td>
    <td colspan="2" width="80%" height="60" class="table td"><p>Mota/Clase</p>
    <p><strong>'.$descripcion_articulo.'</strong></p></td>
  </tr>
  <tr>
    <td class="table td"><p>Pisua (gr)/peso (gr.)</p>
    <p><strong>'.$peso_articulo.'</strong></p></td>
	 <td class="table td"><p>Metalak/Metales </p>
    <p><strong>'.$tipo_de_articulo.'</strong></p></td>
    <td class="table td"><p>Kilate-pisua/peso quilates</p>
    <p><strong>'.$ley.'</strong></p></td>
  </tr>
  <tr>
    <td class="table td"><p>Grabaketak/grabaciones</p>
    <p><input type="checkbox" name="radio" '.$inscsi.' > Bai/Si
	 <input type="checkbox" name="radio" '.$inscno.' > Ez/No
	 </p>
	 </td>
    <td  colspan="2" class="table td"><p>Detaileak/Detalles  </p>
    <p><strong>'.$inscripciones.'</strong></p></td>
  </tr>
  <tr>
    <td class="table td"><p>Harriak/Piedras</p>
	 <p><input type="checkbox" name="radio" '.$piedcsi.' > Bai/Si
	 <input type="checkbox" name="radio" '.$piedno.' > Ez/No
	 </p>
	 </td>
	 <td class="table td"><p>Mota/clase</p>
    <p><strong>'.$descripcion_piedras.'</strong></p></td>
    <td class="table td"><p>Kilate-pisua/peso quilates</p>
    <p><strong>'.$kilate_piedras.'</strong></p></td>
  </tr>
  <tr>
    <td  colspan="2"  width="47%" class="table td"><p>Prezioa / Precio abonado</p>
    <p><strong>'.$precio_compra_articulo.'</strong></p></td>
    <td width="53%" class="table td"><p>Objektu argazkia (beharrezkoa)/Fotografia detallada objeto</p>
    <p><input type="checkbox" name="radio" checked="checked"> Bai/Si</p></td>
  </tr>
  <tr>
    <td  colspan="3"  width="100%" class="table td" height="15"><p>(*) Salmenta data /Fecha venta posterior: <strong></strong></p>
	 </td>
  </tr>
</table>
';

$html .= '
<table width="100%" cellspacing="0" cellpadding="0" class="table tablearticulo" >
  <tr>
    <td width="20%" class="table td"><p>Orden zk./Nº orden</p>
    <p><strong>'.$id_articulo_lote.'</strong></p></td>
    <td colspan="2" width="80%" height="60" class="table td"><p>Mota/Clase</p>
    <p><strong>'.$descripcion_articulo.'</strong></p></td>
  </tr>
  <tr>
    <td class="table td"><p>Pisua (gr)/peso (gr.)</p>
    <p><strong>'.$peso_articulo.'</strong></p></td>
	 <td class="table td"><p>Metalak/Metales </p>
    <p><strong>'.$tipo_de_articulo.'</strong></p></td>
    <td class="table td"><p>Kilate-pisua/peso quilates</p>
    <p><strong>'.$ley.'</strong></p></td>
  </tr>
  <tr>
    <td class="table td"><p>Grabaketak/grabaciones</p>
    <p><input type="checkbox" name="radio" '.$inscsi.' > Bai/Si
	 <input type="checkbox" name="radio" '.$inscno.' > Ez/No
	 </p>
	 </td>
    <td  colspan="2" class="table td"><p>Detaileak/Detalles  </p>
    <p><strong>'.$inscripciones.'</strong></p></td>
  </tr>
  <tr>
    <td class="table td"><p>Harriak/Piedras</p>
	 <p><input type="checkbox" name="radio" '.$piedcsi.' > Bai/Si
	 <input type="checkbox" name="radio" '.$piedno.' > Ez/No
	 </p>
	 </td>
	 <td class="table td"><p>Mota/clase</p>
    <p><strong>'.$descripcion_piedras.'</strong></p></td>
    <td class="table td"><p>Kilate-pisua/peso quilates</p>
    <p><strong>'.$kilate_piedras.'</strong></p></td>
  </tr>
  <tr>
    <td  colspan="2"  width="47%" class="table td"><p>Prezioa / Precio abonado</p>
    <p><strong>'.$precio_compra_articulo.'</strong></p></td>
    <td width="53%" class="table td"><p>Objektu argazkia (beharrezkoa)/Fotografia detallada objeto</p>
    <p><input type="checkbox" name="radio" checked="checked"> Bai/Si</p></td>
  </tr>
  <tr>
    <td  colspan="3"  width="100%" class="table td" height="15"><p>(*) Salmenta data /Fecha venta posterior: <strong></strong></p>
	 </td>
  </tr>
</table>
';

$html .= '
<table width="100%" cellspacing="0" cellpadding="0" class="table tablearticulo" >
  <tr>
    <td width="20%" class="table td"><p>Orden zk./Nº orden</p>
    <p><strong>'.$id_articulo_lote.'</strong></p></td>
    <td colspan="2" width="80%" height="60" class="table td"><p>Mota/Clase</p>
    <p><strong>'.$descripcion_articulo.'</strong></p></td>
  </tr>
  <tr>
    <td class="table td"><p>Pisua (gr)/peso (gr.)</p>
    <p><strong>'.$peso_articulo.'</strong></p></td>
	 <td class="table td"><p>Metalak/Metales </p>
    <p><strong>'.$tipo_de_articulo.'</strong></p></td>
    <td class="table td"><p>Kilate-pisua/peso quilates</p>
    <p><strong>'.$ley.'</strong></p></td>
  </tr>
  <tr>
    <td class="table td"><p>Grabaketak/grabaciones</p>
    <p><input type="checkbox" name="radio" '.$inscsi.' > Bai/Si
	 <input type="checkbox" name="radio" '.$inscno.' > Ez/No
	 </p>
	 </td>
    <td  colspan="2" class="table td"><p>Detaileak/Detalles  </p>
    <p><strong>'.$inscripciones.'</strong></p></td>
  </tr>
  <tr>
    <td class="table td"><p>Harriak/Piedras</p>
	 <p><input type="checkbox" name="radio" '.$piedcsi.' > Bai/Si
	 <input type="checkbox" name="radio" '.$piedno.' > Ez/No
	 </p>
	 </td>
	 <td class="table td"><p>Mota/clase</p>
    <p><strong>'.$descripcion_piedras.'</strong></p></td>
    <td class="table td"><p>Kilate-pisua/peso quilates</p>
    <p><strong>'.$kilate_piedras.'</strong></p></td>
  </tr>
  <tr>
    <td  colspan="2"  width="47%" class="table td"><p>Prezioa / Precio abonado</p>
    <p><strong>'.$precio_compra_articulo.'</strong></p></td>
    <td width="53%" class="table td"><p>Objektu argazkia (beharrezkoa)/Fotografia detallada objeto</p>
    <p><input type="checkbox" name="radio" checked="checked"> Bai/Si</p></td>
  </tr>
  <tr>
    <td  colspan="3"  width="100%" class="table td" height="15"><p>(*) Salmenta data /Fecha venta posterior: <strong></strong></p>
	 </td>
  </tr>
</table>
';

$html .= '
<table width="100%" cellspacing="0" cellpadding="0" class="table tablearticulo" >
  <tr>
    <td width="20%" class="table td"><p>Orden zk./Nº orden</p>
    <p><strong>'.$id_articulo_lote.'</strong></p></td>
    <td colspan="2" width="80%" height="60" class="table td"><p>Mota/Clase</p>
    <p><strong>'.$descripcion_articulo.'</strong></p></td>
  </tr>
  <tr>
    <td class="table td"><p>Pisua (gr)/peso (gr.)</p>
    <p><strong>'.$peso_articulo.'</strong></p></td>
	 <td class="table td"><p>Metalak/Metales </p>
    <p><strong>'.$tipo_de_articulo.'</strong></p></td>
    <td class="table td"><p>Kilate-pisua/peso quilates</p>
    <p><strong>'.$ley.'</strong></p></td>
  </tr>
  <tr>
    <td class="table td"><p>Grabaketak/grabaciones</p>
    <p><input type="checkbox" name="radio" '.$inscsi.' > Bai/Si
	 <input type="checkbox" name="radio" '.$inscno.' > Ez/No
	 </p>
	 </td>
    <td  colspan="2" class="table td"><p>Detaileak/Detalles  </p>
    <p><strong>'.$inscripciones.'</strong></p></td>
  </tr>
  <tr>
    <td class="table td"><p>Harriak/Piedras</p>
	 <p><input type="checkbox" name="radio" '.$piedcsi.' > Bai/Si
	 <input type="checkbox" name="radio" '.$piedno.' > Ez/No
	 </p>
	 </td>
	 <td class="table td"><p>Mota/clase</p>
    <p><strong>'.$descripcion_piedras.'</strong></p></td>
    <td class="table td"><p>Kilate-pisua/peso quilates</p>
    <p><strong>'.$kilate_piedras.'</strong></p></td>
  </tr>
  <tr>
    <td  colspan="2"  width="47%" class="table td"><p>Prezioa / Precio abonado</p>
    <p><strong>'.$precio_compra_articulo.'</strong></p></td>
    <td width="53%" class="table td"><p>Objektu argazkia (beharrezkoa)/Fotografia detallada objeto</p>
    <p><input type="checkbox" name="radio" checked="checked"> Bai/Si</p></td>
  </tr>
  <tr>
    <td  colspan="3"  width="100%" class="table td" height="15"><p>(*) Salmenta data /Fecha venta posterior: <strong></strong></p>
	 </td>
  </tr>
</table>
';

$html .= '
<table width="100%" cellspacing="0" cellpadding="0" class="table tablearticulo" >
  <tr>
    <td width="20%" class="table td"><p>Orden zk./Nº orden</p>
    <p><strong>'.$id_articulo_lote.'</strong></p></td>
    <td colspan="2" width="80%" height="60" class="table td"><p>Mota/Clase</p>
    <p><strong>'.$descripcion_articulo.'</strong></p></td>
  </tr>
  <tr>
    <td class="table td"><p>Pisua (gr)/peso (gr.)</p>
    <p><strong>'.$peso_articulo.'</strong></p></td>
	 <td class="table td"><p>Metalak/Metales </p>
    <p><strong>'.$tipo_de_articulo.'</strong></p></td>
    <td class="table td"><p>Kilate-pisua/peso quilates</p>
    <p><strong>'.$ley.'</strong></p></td>
  </tr>
  <tr>
    <td class="table td"><p>Grabaketak/grabaciones</p>
    <p><input type="checkbox" name="radio" '.$inscsi.' > Bai/Si
	 <input type="checkbox" name="radio" '.$inscno.' > Ez/No
	 </p>
	 </td>
    <td  colspan="2" class="table td"><p>Detaileak/Detalles  </p>
    <p><strong>'.$inscripciones.'</strong></p></td>
  </tr>
  <tr>
    <td class="table td"><p>Harriak/Piedras</p>
	 <p><input type="checkbox" name="radio" '.$piedcsi.' > Bai/Si
	 <input type="checkbox" name="radio" '.$piedno.' > Ez/No
	 </p>
	 </td>
	 <td class="table td"><p>Mota/clase</p>
    <p><strong>'.$descripcion_piedras.'</strong></p></td>
    <td class="table td"><p>Kilate-pisua/peso quilates</p>
    <p><strong>'.$kilate_piedras.'</strong></p></td>
  </tr>
  <tr>
    <td  colspan="2"  width="47%" class="table td"><p>Prezioa / Precio abonado</p>
    <p><strong>'.$precio_compra_articulo.'</strong></p></td>
    <td width="53%" class="table td"><p>Objektu argazkia (beharrezkoa)/Fotografia detallada objeto</p>
    <p><input type="checkbox" name="radio" checked="checked"> Bai/Si</p></td>
  </tr>
  <tr>
    <td  colspan="3"  width="100%" class="table td" height="15"><p>(*) Salmenta data /Fecha venta posterior: <strong></strong></p>
	 </td>
  </tr>
</table>
';

$html .= '
<table width="100%" cellspacing="0" cellpadding="0" class="table tablearticulo" >
  <tr>
    <td width="20%" class="table td"><p>Orden zk./Nº orden</p>
    <p><strong>'.$id_articulo_lote.'</strong></p></td>
    <td colspan="2" width="80%" height="60" class="table td"><p>Mota/Clase</p>
    <p><strong>'.$descripcion_articulo.'</strong></p></td>
  </tr>
  <tr>
    <td class="table td"><p>Pisua (gr)/peso (gr.)</p>
    <p><strong>'.$peso_articulo.'</strong></p></td>
	 <td class="table td"><p>Metalak/Metales </p>
    <p><strong>'.$tipo_de_articulo.'</strong></p></td>
    <td class="table td"><p>Kilate-pisua/peso quilates</p>
    <p><strong>'.$ley.'</strong></p></td>
  </tr>
  <tr>
    <td class="table td"><p>Grabaketak/grabaciones</p>
    <p><input type="checkbox" name="radio" '.$inscsi.' > Bai/Si
	 <input type="checkbox" name="radio" '.$inscno.' > Ez/No
	 </p>
	 </td>
    <td  colspan="2" class="table td"><p>Detaileak/Detalles  </p>
    <p><strong>'.$inscripciones.'</strong></p></td>
  </tr>
  <tr>
    <td class="table td"><p>Harriak/Piedras</p>
	 <p><input type="checkbox" name="radio" '.$piedcsi.' > Bai/Si
	 <input type="checkbox" name="radio" '.$piedno.' > Ez/No
	 </p>
	 </td>
	 <td class="table td"><p>Mota/clase</p>
    <p><strong>'.$descripcion_piedras.'</strong></p></td>
    <td class="table td"><p>Kilate-pisua/peso quilates</p>
    <p><strong>'.$kilate_piedras.'</strong></p></td>
  </tr>
  <tr>
    <td  colspan="2"  width="47%" class="table td"><p>Prezioa / Precio abonado</p>
    <p><strong>'.$precio_compra_articulo.'</strong></p></td>
    <td width="53%" class="table td"><p>Objektu argazkia (beharrezkoa)/Fotografia detallada objeto</p>
    <p><input type="checkbox" name="radio" checked="checked"> Bai/Si</p></td>
  </tr>
  <tr>
    <td  colspan="3"  width="100%" class="table td" height="15"><p>(*) Salmenta data /Fecha venta posterior: <strong></strong></p>
	 </td>
  </tr>
</table>
';

$html .= '
<table width="100%" cellspacing="0" cellpadding="0" class="table tablearticulo" >
  <tr>
    <td width="20%" class="table td"><p>Orden zk./Nº orden</p>
    <p><strong>'.$id_articulo_lote.'</strong></p></td>
    <td colspan="2" width="80%" height="60" class="table td"><p>Mota/Clase</p>
    <p><strong>'.$descripcion_articulo.'</strong></p></td>
  </tr>
  <tr>
    <td class="table td"><p>Pisua (gr)/peso (gr.)</p>
    <p><strong>'.$peso_articulo.'</strong></p></td>
	 <td class="table td"><p>Metalak/Metales </p>
    <p><strong>'.$tipo_de_articulo.'</strong></p></td>
    <td class="table td"><p>Kilate-pisua/peso quilates</p>
    <p><strong>'.$ley.'</strong></p></td>
  </tr>
  <tr>
    <td class="table td"><p>Grabaketak/grabaciones</p>
    <p><input type="checkbox" name="radio" '.$inscsi.' > Bai/Si
	 <input type="checkbox" name="radio" '.$inscno.' > Ez/No
	 </p>
	 </td>
    <td  colspan="2" class="table td"><p>Detaileak/Detalles  </p>
    <p><strong>'.$inscripciones.'</strong></p></td>
  </tr>
  <tr>
    <td class="table td"><p>Harriak/Piedras</p>
	 <p><input type="checkbox" name="radio" '.$piedcsi.' > Bai/Si
	 <input type="checkbox" name="radio" '.$piedno.' > Ez/No
	 </p>
	 </td>
	 <td class="table td"><p>Mota/clase</p>
    <p><strong>'.$descripcion_piedras.'</strong></p></td>
    <td class="table td"><p>Kilate-pisua/peso quilates</p>
    <p><strong>'.$kilate_piedras.'</strong></p></td>
  </tr>
  <tr>
    <td  colspan="2"  width="47%" class="table td"><p>Prezioa / Precio abonado</p>
    <p><strong>'.$precio_compra_articulo.'</strong></p></td>
    <td width="53%" class="table td"><p>Objektu argazkia (beharrezkoa)/Fotografia detallada objeto</p>
    <p><input type="checkbox" name="radio" checked="checked"> Bai/Si</p></td>
  </tr>
  <tr>
    <td  colspan="3"  width="100%" class="table td" height="15"><p>(*) Salmenta data /Fecha venta posterior: <strong></strong></p>
	 </td>
  </tr>
</table>
';

$html .= '
<table width="100%" cellspacing="0" cellpadding="0" class="table tablearticulo" >
  <tr>
    <td width="20%" class="table td"><p>Orden zk./Nº orden</p>
    <p><strong>'.$id_articulo_lote.'</strong></p></td>
    <td colspan="2" width="80%" height="60" class="table td"><p>Mota/Clase</p>
    <p><strong>'.$descripcion_articulo.'</strong></p></td>
  </tr>
  <tr>
    <td class="table td"><p>Pisua (gr)/peso (gr.)</p>
    <p><strong>'.$peso_articulo.'</strong></p></td>
	 <td class="table td"><p>Metalak/Metales </p>
    <p><strong>'.$tipo_de_articulo.'</strong></p></td>
    <td class="table td"><p>Kilate-pisua/peso quilates</p>
    <p><strong>'.$ley.'</strong></p></td>
  </tr>
  <tr>
    <td class="table td"><p>Grabaketak/grabaciones</p>
    <p><input type="checkbox" name="radio" '.$inscsi.' > Bai/Si
	 <input type="checkbox" name="radio" '.$inscno.' > Ez/No
	 </p>
	 </td>
    <td  colspan="2" class="table td"><p>Detaileak/Detalles  </p>
    <p><strong>'.$inscripciones.'</strong></p></td>
  </tr>
  <tr>
    <td class="table td"><p>Harriak/Piedras</p>
	 <p><input type="checkbox" name="radio" '.$piedcsi.' > Bai/Si
	 <input type="checkbox" name="radio" '.$piedno.' > Ez/No
	 </p>
	 </td>
	 <td class="table td"><p>Mota/clase</p>
    <p><strong>'.$descripcion_piedras.'</strong></p></td>
    <td class="table td"><p>Kilate-pisua/peso quilates</p>
    <p><strong>'.$kilate_piedras.'</strong></p></td>
  </tr>
  <tr>
    <td  colspan="2"  width="47%" class="table td"><p>Prezioa / Precio abonado</p>
    <p><strong>'.$precio_compra_articulo.'</strong></p></td>
    <td width="53%" class="table td"><p>Objektu argazkia (beharrezkoa)/Fotografia detallada objeto</p>
    <p><input type="checkbox" name="radio" checked="checked"> Bai/Si</p></td>
  </tr>
  <tr>
    <td  colspan="3"  width="100%" class="table td" height="15"><p>(*) Salmenta data /Fecha venta posterior: <strong></strong></p>
	 </td>
  </tr>
</table>
';




$html .= '
<table width="100%" cellspacing="0" cellpadding="0" class="table tablearticulo" style="margin-top:0px;" >
  <tr>
    <td width="47%" class="table td"><p>Guztiko prezio / Precio Total</p>
    <p><strong>'.$preciototal.' €</strong></p></td>
	 <td class="table td"><p>Pisua/ peso total Objetos</p>
    <p><strong>'.$pesototal.' grs</strong></p></td>
  </tr>
</table>
<div style="text-align: left; font-style: italic; margin:5px 0 0 0; color:#000000; font-size:6pt; ">(*) Objektuen salmenta datak ez du inola ere lotuko oraingo kontratuko interesatuari, eta kontratu honen sinadurako dataren ondoren egingo da / La fecha de venta de los objetos, por parte del establecimiento, no vincula en ningún caso al vendedor-interesado del presente contrato, y será siempre posterior a la fecha de firma del mismo.<br />
Datu Pertsonalak Babesteari buruzko abenduaren 13ko 15/1999 Lege Organikoaren 5.artikuluarekin bat etorriz eta  Herritarren Segurtasuna Babesteari buruzko otsailaren 21eko 1/1992 Lege Organikoaren 12.1 artikuluan xedatutakoaren itzalpean, jakinarazi zaizu, informazio hau kudeatzeko, bildutako datu pertsonalak Segurtasun sailburuordearen SIP JOYAS Y METALES PRECIOSOS fitxategian sartuko direla.  Fitxategi automatizatuan agertzen direnek eskubidea dute datuen aurka egiteko nahiz datuak ikusi, zuzendu edo deuseztatzeko, fitxategiaren arduradunaren aurrean, CEDSP Segurtasun Sailburuordearen Kabinetea (Larrauri-Mendotxe Bidea 18, 48950 Erandio / Bizkaia)./ En cumplimiento del artículo 5 de la Ley Orgánica 15/1999, de 13 de diciembre, de Protección de Datos de Carácter Personal y al amparo de lo dispuesto en el artículo 12 de la Ley Orgánica 1/1992, de 21 de febrero, sobre protección de la Seguridad Ciudadana, se informa que, con la finalidad de gestionar esta información, los datos de caracter personal se incluirán en el fichero-tratamiento denominado SIP JOYAS Y METALES PRECIOSOS de la Viceconsejería de Seguridad. La persona afectada podrá ejercitar los derechos de acceso, rectificación, oposición y cancelación ante la persona responsable del fichero, CEDSP Gabinete Viceconsejero de Seguridad, Larrauri-Mendotxe Bidea 18, 48950 Erandio / Bizkaia.
</div>
<br />
<table width="100%" cellspacing="0" cellpadding="0" class="table" >
  <tr>
    <td align="center"><h4>En Barakaldo a '.$d.' (ko) De '.$m.' (ren) de '.$a.'  (e)a</h4></td>
  </tr>
</table>
</body>
</html>
';
$mpdf->WriteHTML($html);
//$mpdf->Output('Ficha Policia Lote Nº '.$rsItem['id_lote'].'.pdf','D');
$mpdf->Output(); 
exit;
?>
</body>
</html>
