<?php
require("session_file.php");
?>
<?php
require_once("../../conexion.php");
$idl = $_GET["lote"];
if ($usuario == 'mariaarias') {
  if (isset($_GET['sucursal'])) {
    $sucursal = $_GET['sucursal'];  
  }
}

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

$nombreempresa= $rsItem['empresa'];
$nombresucursal=$rsItem['nombre_sucursal'];
$direcciontienda=$rsItem['direccion_tienda'];
$poblaciontienda=$rsItem['poblacion_tienda'];
$codigopostaltienda=$rsItem['codigo_postal_tienda'];
$provinciatienda=$rsItem['provincia_tienda'];
$numeroidentificaciontienda=$rsItem['numero_identificacion_tienda'];
$calletienda=$rsItem['calle'];
$numerocalle=$rsItem['numero_calle'];
///////END FUNCION FECHA

$daten= $rsItem['f_nacimiento'];
$sqldatne=date('d-m-Y',strtotime($daten));

if($rsItem['tipo_identificacion']==dni){ $documento="checked='checked'"; $otrodocumento=""; }else{ $documento=""; $otrodocumento="checked='checked'"; $otrod=$rsItem['tipo_identificacion']; }

if($rsItem['nacionalidad']==Española){ $nacion="checked='checked'"; $otronacion=""; }else{ $nacion=""; $otronacion="checked='checked'"; $otron=$rsItem['nacionalidad']; }

if($rsItem['sexo']==Hombre){ $sexhombre="checked='checked'"; $sexmujer=""; }else{ $sexhombre=""; $sexmujer="checked='checked'"; }

if($rsItem['compra_opcion']==si){ $empenado="checked='checked'"; $comprado=""; }else{ $empenado=""; $comprado="checked='checked'"; }

?>
<html>
<head>
<meta charset="UTF-8" />
<style>
body {font-family: helvetica, arial;
    font-size: 7pt;
	 width:900px;
	 margin:0 auto;
	 color:#000000;
	 text-transform: uppercase; 
}
p{ 
margin: 0pt;
line-height:auto;
font-size:12px;
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
.inputesocndidos  { visibility: hidden; }
.table .td{
	padding:2px 5px;
	border: 1px solid #ffffff;
	border-spacing:0px;
	height:35px;
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
.checkseleccionado{
	background-color:#0a090a;
	-webkit-border-top-left-radius:3px;
	-moz-border-radius-topleft:3px;
	border-top-left-radius:3px;
	-webkit-border-top-right-radius:3px;
	-moz-border-radius-topright:3px;
	border-top-right-radius:3px;
	-webkit-border-bottom-right-radius:3px;
	-moz-border-radius-bottomright:3px;
	border-bottom-right-radius:3px;
	-webkit-border-bottom-left-radius:3px;
	-moz-border-radius-bottomleft:3px;
	border-bottom-left-radius:3px;
	text-indent:0px;
	display:inline-block;
	color:#ffffff;
	font-family:Verdana;
	font-size:11px;
	font-weight:bold;
	font-style:normal;
	height:16px;
	line-height:16px;
	width:16px;
	text-decoration:none;
	text-align:center;
}
.checknoseleccionado {
	background-color:#ffffff;
	-webkit-border-top-left-radius:3px;
	-moz-border-radius-topleft:3px;
	border-top-left-radius:3px;
	-webkit-border-top-right-radius:3px;
	-moz-border-radius-topright:3px;
	border-top-right-radius:3px;
	-webkit-border-bottom-right-radius:3px;
	-moz-border-radius-bottomright:3px;
	border-bottom-right-radius:3px;
	-webkit-border-bottom-left-radius:3px;
	-moz-border-radius-bottomleft:3px;
	border-bottom-left-radius:3px;
	text-indent:0px;
	border:1px solid #a3a3a3;
	display:inline-block;
	color:#ffffff;
	font-family:Verdana;
	font-size:11px;
	font-weight:bold;
	font-style:normal;
	height:14px;
	line-height:14px;
	width:14px;
	text-decoration:none;
	text-align:center;
}
</style>
<style type="text/css" media="print">
@page {
    size: auto;   /* auto is the initial value */
    margin-top: 0;  /* this affects the margin in the printer settings */
    margin-bottom:0;
}
body{
  margin-top: 50px;
}
</style>
<script type="text/javascript"> 
function PrintWindow() { 
window.print(); 
CheckWindowState(); 
} 

function CheckWindowState() { 
if(document.readyState=="complete") { 
window.close(); 
} else { 
setTimeout("CheckWindowState()", 10) 
} 
} 
PrintWindow(); 
</script>
</head>
<body>
<table width="100%" cellspacing="0" cellpadding="0" class="table" style=" color:#ffffff; border:#FFFFFF; visibility:hidden;" >
  <tr>
    <td align="center"><h2>HOJA - CONTRATO COMPRAVENTA OBJETOS METALES PRECIOSOS</h2></td>
  </tr>
  <tr>
    <td><h3>Establezimenduaren datuak / Datos del establecimiento</h3></td>
  </tr>
</table>

<table width="100%"  cellspacing="0" cellpadding="0" class="table" style=" color:#ffffff; border-color:#FFFFFF; visibility:hidden;"  >
  <tr>
    <td width="63%" class="table td"><p>Izena/Nombre</p>
    <p><strong><?php echo $nombreempresa; ?> <?php echo $nombresucursal; ?></strong></p></td>
    <td width="37%" class="table td"><p>IFZ/NIF</p>
    <p><strong><?php echo $numeroidentificaciontienda; ?></strong></p></td>
  </tr>
  <tr>
    <td class="table td"><p>Kalea/Calle</p>
    <p><strong><?php echo $calletienda; ?></strong></p></td>
    <td class="table td"><p>Zk./Nº</p>
    <p><strong><?php echo $numerocalle; ?></strong></p></td>
  </tr>
  <tr>
    <td class="table td"><p>Udalerria/Localidad</p>
    <p><strong><?php echo $poblaciontienda; ?></strong></p></td>
    <td class="table td"><p>Lurraldea/Provincia</p>
    <p><strong><?php echo $provinciatienda; ?></strong></p></td>
  </tr>
</table>

<table width="100%" cellspacing="0" cellpadding="0" class="table" style=" color:#ffffff; border:#FFFFFF; visibility:hidden;"  >
  <tr>
    <td><h3>Operazio datuak /Datos de la operación</h3></td>
  </tr>
</table>

<table width="100%" cellspacing="0" cellpadding="0" class="table" style=" color:#ffffff; border:#FFFFFF; visibility:hidden;"  >
  <tr>
    <td width="47%" class="table td"><p>Kontratu zk./Nº de contrato</p>
    <p><strong><?php echo $rsItem['id_lote']; ?></strong></p></td>
    <td width="53%" class="table td"><p>Data/Fecha</p>
    <p><strong><?php echo $sqldate; ?></strong></p></td>
  </tr>
  <tr>
   <td class="table td"><p>Kontrato mota/Tipo de contrato</p>
	
	<p><input class="inputesocndidos" type="checkbox" name="radio" <?php echo $comprado; ?> > Salerosi/Compraventa
	
	<input class="inputesocndidos" type="checkbox" name="radio"  <?php echo $empenado; ?> > Bahitura/Empeño
	
	</p></td>
	
	<td class="table td"><p>Bahitura txartela/Papeleta empeño</p>
	
	<p><input class="inputesocndidos" type="checkbox" name="radio" > Bai/Si
	
	<input class="inputesocndidos" type="checkbox" name="radio" checked="checked" > Ez/No
	
	</p></td>
  </tr>
</table>

<table width="100%" cellspacing="0" cellpadding="0" class="table" style=" color:#ffffff; border:#FFFFFF; visibility:hidden;"  >
  <tr>
    <td><h3>Interesatua/Interesado:</h3></td>
  </tr>
</table>

<table width="100%" cellspacing="0" cellpadding="0" class="table" style=" color:#ffffff; border:#FFFFFF; visibility:hidden;"  >
  <tr>
    <td width="39%" class="table td"><p>Izena/Nombre</p>
    <p><strong><?php echo $rsItem['nombre']; ?></strong></p></td>
    <td width="61%" class="table td"><p>Abezinak/Apellidos</p>
    <p><strong><?php echo $rsItem['apellido']; ?></strong></p></td>
  </tr>
  
  <tr>
    <td class="table td">
	 <p>Dok. zk./Nº documento</p>
    <p>
	 <strong><?php echo $rsItem['identificacion']; ?></strong></p>
	 </td>
    <td class="table td">
	 <p>Mota/Tipo</p>
    <p>
	 <input class="inputesocndidos" type="checkbox" name="radio" <?php echo $documento; ?> > ENA/DNI
	 <input class="inputesocndidos" type="checkbox" name="radio" <?php echo $otrodocumento; ?> > Beste bat (zehaztu)/Otro (indicar):<strong> <?php echo $otrod; ?></strong>
	 </p>
	 </td>
  </tr>
  
  <tr>
   <td class="table td">
	<p>Sexua/Sexo</p>
	<p>
	<input class="inputesocndidos" type="checkbox" name="radio" <?php echo $sexhombre; ?> >Gizonezkoa/Varón
	<input class="inputesocndidos" type="checkbox" name="radio" <?php echo $sexmujer; ?> >Emakumezkoa/Mujer
	</p>
	</td>
   <td class="table td">
	<p>Jaiotze data/Fecha de nacimiento</p>
   <p><strong><?php echo $sqldatne; ?></strong></p>
	</td>
  </tr>
  
  <tr>
    <td colspan="2" class="table td">
    <p>Jatorrizko herritartasuna/País de Nacionalidad</p>
    <p><input class="inputesocndidos" type="checkbox" name="radio" <?php echo $nacion; ?> >Espainia/España
	 <input class="inputesocndidos" type="checkbox" name="radio" <?php echo $otronacion; ?> >Beste bat (zehaztu)/Otro (indicar):<strong><?php echo $otron; ?></strong>
	 </p>
	 </td>
  </tr>
  <tr>
    <td colspan="2" class="table td"><p>Ohiko bizilekua, kale eta zk./Domicilio actual: /calle y nº</p>
    <p><strong><?php echo $rsItem['direccion']; ?></strong></p></td>
  </tr>
  <tr>
    <td class="table td"><pUdalerria/>Localidad</p>
    <p><strong><?php echo $rsItem['c_poblacion']; ?></strong></p></td>
    <td class="table td"><p>Lurraldea/Provincia</p>
    <p><strong><?php echo $rsItem['c_provincia']; ?></strong></p></td>
  </tr>
</table>



<table width="100%" cellspacing="0" cellpadding="0" class="table" >
  <tr>
    <td style=" height:50px; padding-top:10px; color:#ffffff; border-color:#FFFFFF; visibility:hidden;"><h3>Kontratu honen objektua/k (banakakoak)/Objeto/s del presente contrato (individualizados)</h3></td>
  </tr>
</table>

<?
$querys = "SELECT * FROM articulos_$sucursal WHERE id_lote_articulos =$idl ORDER BY id_articulo_lote  ";
mysql_query ("SET NAMES 'utf8'");
$Itemas = mysql_query($querys, $conexion);
while($row = mysql_fetch_assoc($Itemas)){ 

if($row['active_inscripciones']==si){ $inscsi="checked='checked'"; $inscno=""; }else{ $inscsi=""; $inscno="checked='checked'"; }

if($row['active_piedras']==si){ $piedcsi="checked='checked'"; $piedno=""; }else{ $piedcsi=""; $piedno="checked='checked'"; }

extract($row); 

?>
<table width="100%" cellspacing="0" cellpadding="0" class="table tablearticulo" >
  <tr>
    <td width="20%" class="table td"><p style="color:#ffffff; visibility:hidden;" >Orden zk./Nº orden</p>
    <p><strong><?php echo $id_articulo_lote; ?></strong></p></td>
    <td colspan="2" width="80%" height="60" class="table td" style=" height:70px;"><p style="color:#ffffff; visibility:hidden;" >Mota/Clase</p>
    <p><strong><?php echo $descripcion_articulo; ?></strong></p></td>
  </tr>
  <tr>
    <td class="table td" style=" height:50px; padding-top:10px;">
		 <p>Peso neto (gr.) <strong><?php echo $peso_articulo; ?></strong></p>
		 <p>Peso bruto (gr.) <strong><?php echo $peso_bruto; ?></strong></p>
    </td>
	 <td class="table td" style=" height:50px; padding-top:10px;"><p style="color:#ffffff; visibility:hidden;" >Metalak/Metales </p>
    <p><strong><?php echo $tipo_de_articulo; ?></strong></p></td>
    <td class="table td" style=" height:50px; padding-top:10px;"><p style="color:#ffffff; visibility:hidden;" >Kilate-pisua/Peso quilates</p>
    <p><strong><?php echo $ley; ?></strong></p></td>
  </tr>
  <tr>
    <td class="table td" style=" height:50px; padding-top:10px;"><p style="color:#ffffff; visibility:hidden;" >Grabaketak/Grabaciones</p>
    <p><input type="checkbox" name="radio" <?php echo $inscsi; ?> > Bai/Si
	 <input type="checkbox" name="radio" <?php echo $inscno; ?> > Ez/No
	 </p>
	 </td>
    <td  colspan="2" class="table td" style=" height:70px;"><p style="color:#ffffff; visibility:hidden;" >Detaileak/Detalles  </p>
    <p><strong><?php echo $inscripciones; ?></strong></p></td>
  </tr>
  <tr>
    <td class="table td" style=" height:50px; padding-top:10px;"><p style="color:#ffffff; visibility:hidden;" >Harriak/Piedras</p>
	 <p><input type="checkbox" name="radio" <?php echo $piedcsi; ?> > Bai/Si
	 <input type="checkbox" name="radio" <?php echo $piedno; ?> > Ez/No
	 </p>
	 </td>
	 <td class="table td" style=" height:50px; padding-top:10px;"><p style="color:#ffffff; visibility:hidden;" >Kilate-pisua/Peso quilates</p>
    <p><strong><?php echo $kilate_piedras; ?></strong></p></td>
    <td class="table td" style=" height:70px;"><p>Mota/Clase</p>
    <p><strong><?php echo $descripcion_piedras; ?></strong></p></td>
  </tr>
  <tr>
    <td  colspan="2"  width="47%" class="table td" style=" height:50px; padding-top:10px;"><p style="color:#ffffff; visibility:hidden;" >Prezioa / Precio abonado</p>
    <p><strong><?php echo $precio_compra_articulo; ?> €</strong></p></td>
    <td width="53%" class="table td" style=" height:50px; padding-top:10px;"><p style="color:#ffffff; visibility:hidden;" >Objektu argazkia (beharrezkoa)/Fotografia detallada objeto</p>
    <p><input type="checkbox" name="radio" checked="checked"> Bai/Si</p></td>
  </tr>
  <tr>
    <td  colspan="3"  width="100%" class="table td" height="15" style=" height:50px; padding-top:10px;"><p style="color:#ffffff; visibility:hidden;" >(*)  Salmenta data / Fecha venta posterior: <strong></strong></22wwp>
	 </td>
  </tr>
</table>

<?php }
 
$query = "SELECT * FROM lotes_$sucursal WHERE id_lote=$idl ";
mysql_query ("SET NAMES 'utf8'");
$Item = mysql_query($query, $conexion);
$rsItem = mysql_fetch_assoc($Item);
$pesobruto = $rsItem['peso_bruto'];
?>
<table width="100%" cellspacing="0" cellpadding="0" class="table tablearticulo" style="margin-top:0px; color:#ffffff; visibility:hidden;"  >
  <tr>
    <td width="47%" class="table td"><p>Guztiko prezio / Precio Total</p>
    <p><strong><?php echo $preciototal; ?> €</strong></p></td>
	 <td class="table td"><p>Peso total Objetos</p>
    <p><strong><?php echo $pesototal; ?> grs</strong></p></td>
	 <td class="table td"><p>Pisua/ peso Bruto Objetos</p>
    <p><strong><?php echo $pesobruto; ?> grs</strong></p></td>
  </tr>
</table>
<div style="text-align: left; font-style: italic; margin:5px 0 0 0; color:#000000; font-size:5pt; color:#ffffff; visibility:hidden; ">(*) Objektuen salmenta datak ez du inola ere lotuko oraingo kontratuko interesatuari, eta kontratu honen sinadurako dataren ondoren egingo da / La fecha de venta de los objetos, por parte del establecimiento, no vincula en ningún caso al vendedor-interesado del presente contrato, y será siempre posterior a la fecha de firma del mismo.<br />
Datu Pertsonalak Babesteari buruzko abenduaren 13ko 15/1999 Lege Organikoaren 5.artikuluarekin bat etorriz eta  Herritarren Segurtasuna Babesteari buruzko otsailaren 21eko 1/1992 Lege Organikoaren 12.1 artikuluan xedatutakoaren itzalpean, jakinarazi zaizu, informazio hau kudeatzeko, bildutako datu pertsonalak Segurtasun sailburuordearen SIP JOYAS Y METALES PRECIOSOS fitxategian sartuko direla.  Fitxategi automatizatuan agertzen direnek eskubidea dute datuen aurka egiteko nahiz datuak ikusi, zuzendu edo deuseztatzeko, fitxategiaren arduradunaren aurrean, CEDSP Segurtasun Sailburuordearen Kabinetea (Larrauri-Mendotxe Bidea 18, 48950 Erandio / Bizkaia)./ En cumplimiento del artículo 5 de la Ley Orgánica 15/1999, de 13 de diciembre, de Protección de Datos de Carácter Personal y al amparo de lo dispuesto en el artículo 12 de la Ley Orgánica 1/1992, de 21 de febrero, sobre protección de la Seguridad Ciudadana, se informa que, con la finalidad de gestionar esta información, los datos de caracter personal se incluirán en el fichero-tratamiento denominado SIP JOYAS Y METALES PRECIOSOS de la Viceconsejería de Seguridad. La persona afectada podrá ejercitar los derechos de acceso, rectificación, oposición y cancelación ante la persona responsable del fichero, CEDSP Gabinete Viceconsejero de Seguridad, Larrauri-Mendotxe Bidea 18, 48950 Erandio / Bizkaia.
</div>
<br />
<table width="100%" cellspacing="0" cellpadding="0" class="table" style="color:#ffffff; visibility:hidden;"  >
  <tr>
    <td align="center"><h4>En <?php echo $poblaciontienda; ?>  a <?php echo $d; ?> de  <?php echo $m; ?> de <?php echo $a; ?></h4></td>
  </tr>
</table>


<table width="100%" border="0" cellspacing="0" cellpadding="0" class="table" style="color:#ffffff; visibility:hidden;" >
  <tr>
    <td><h6>Establezimenduaren zigilua eta sinadura /<br /> Sello y firma del establecimiento</h6></td>
    <td><h6>Interesatuaren sinadura /<br /> Firma del interesado</h6></td>
	 <td><h6>&nbsp;<br /> &nbsp;</h6></td>
	 <td align="right" style="text-align:right;" ><h5>&nbsp; <br />Folio Zk / Nº Pág.&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;</h5></td>
  </tr>
</table>

</body>
</html>