<?php
require('session_file.php');
?>
<?php
$idl = $_GET['lote'];
if ($usuario == 'mariaarias') {
  if (isset($_GET['sucursal'])) {
    $sucursal = $_GET['sucursal'];  
  }
}

$query = "SELECT * FROM lotes_$sucursal 
LEFT JOIN clientes ON lotes_$sucursal.cliente=clientes.id_cliente 
LEFT JOIN sucursal ON lotes_$sucursal.sucursal=sucursal.id_sucursal 
LEFT JOIN articulos_$sucursal ON lotes_$sucursal.id_lote=articulos_$sucursal.id_lote_articulos
LEFT JOIN usuarios ON usuarios.id_usuario = lotes_$sucursal.comprado_por
WHERE id_lote=$idl 
";

mysql_query ("SET NAMES 'utf8'");
$Item = mysql_query($query, $conexion);
$rsItem = mysql_fetch_assoc($Item);

$cantidadarticulos = $rsItem['cantidad_articulos'];

$pesototal = $rsItem['peso'];

$preciototal = $rsItem['precio_compra'];

$id_cliente = $rsItem['id_cliente'];


$textSignature = $rsItem['nombre']." ".$rsItem['apellido'];
$typeItem = "lote";

if ($stmtgc = $mysqli_matermedia->prepare(" SELECT id_signature, signature_value, state_signature, cancel_signature, auth_no_signature FROM Signatures WHERE ItemId = ? AND sucursalSignature = ? AND  typeItem = ? ")) {
    $stmtgc->bind_param('iis', $idl, $sucursal, $typeItem );  
    $stmtgc->execute();    
    $stmtgc->store_result();
    $stmtgc->bind_result($id_signature, $signature_value, $state_signature_parset, $cancel_signature, $auth_no_signature);
    $stmtgc->fetch();
}
if(!empty($id_signature)){
    
}

if(empty($id_signature)){
    $state_signature = "empty";
    $signatureInsert_cliente = "";
}else{
    if( $state_signature_parset == "false" ){
        $signatureInsert_cliente = "";
    }else{
        $signatureInsert_cliente = generateSignatureContratoFinal( $signature_value, $textSignature );
    }
    
}

/*
$signature_value = $rsItem['firma_usuario'];
$textSignature = $rsItem['nombre_usuario']." ".$rsItem['apellido_usuario'];
$signatureInsert_empleado = generateSignatureContratoFinal( $signature_value, $textSignature );
*/
$date = $rsItem['fecha_compra'];
$sqldate=date('d-m-Y',strtotime($date));

$id_sello = $rsItem['sello_sucursal'];
$sello_image = $rsItem['sello_image'];

$sello_sucursal = generaSello($sucursal, $conexion);
// $sello_sucursal = '<div id="sello"><img src="'.$url.'/photos/'.$sello_image.'"></div>';

///////FUNCION FECHA

$dia = date('d',strtotime($date));
$mes = date('m',strtotime($date));
$anyo = date('Y',strtotime($date));
$meses = array('01' => 'enero','02' => 'febrero','03' => 'marzo','04' => 'abril','05' => 'mayo','06' => 'junio','07' => 'julio','08' => 'agosto','09' => 'septiembre','10' => 'octubre','11' => 'noviembre','12' => 'diciembre');

if($meses[$mes])
{
$d = $dia;
$m = $meses[$mes];
$a = $anyo;
}
$empresa_id = $rsItem['empresa_id'];
$nombreempresa = $rsItem['empresa'];
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
  @page { margin-top: 1mm; margin-bottom: 1mm; }
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
	margin:0 0 3px 0;
}
.table .td{
	padding:2px 5px;
	border: 1px solid #333333;
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
<style>
    #sello {
        width: 180px;
        height: 180px;
        border: none;
        border-radius: 50%;
        text-align: center;
        display: block;
        font-family: "Helvetica Neue",Helvetica,Arial,sans-serif;
        transform: rotate(-15deg);
        margin-top: -30px;
        opacity: 1;
        margin-left: 0px;
        margin-bottom: 0px;
    }
   #ordago {
        width: 100%;
        height: 65px;
        margin: 2px auto 19px;
        display: block;
        position: relative;
        float: none;
        image-rendering: optimizequality;
    }
    #ordago_sinlogo {
        width: 165px;
        height: 32px;
        margin: 2px auto 19px;
        display: block;
        position: relative;
        float: none;
    }
    #ordago img{
        width: 100%;
        image-rendering: optimizequality;
    }
    .spans_sellos {
        display: block;
        font-size: 10px;
        line-height: 13px;
        min-height: auto !important;
    }
    .spans_sellos_sinlogo {
        display: block;
        font-size: 13px;
        line-height: 18px;
        min-height: auto !important;
    }
    #nombre_empresa {
        letter-spacing: -1px;
        font-size: 14.5px;
        margin: 5px 0px auto;
        line-height: normal;
        min-height: auto;
    }
    #nombre_empresa.spans_sellos_sinlogo {
        font-weight: bold;
        letter-spacing: -1px;
        font-size: 15px;
        margin: 5px 0px auto;
        line-height: 22px;
        min-height: auto;
    }
    #cif_empresa{
    }
    #direccion_tienda{
    }
    #direccion_tienda{
    }
    #datos_varios{
    }
    #codigo_postal_tienda{
    }
    #poblacion_tienda{
    }
    #provincia_tienda{
    }
</style>
<style type="text/css" media="print">
    @media print{
			/* indicamos el salto de pagina */
			.saltoDePagina{
				display:block;
				page-break-before:always;
			}
		}
    
@page {
    size: auto;   /* auto is the initial value */
    margin-top: 0;  /* this affects the margin in the printer settings */
    margin-bottom:0;
}
body{
  margin-top: 20px;
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
<table width="100%" cellspacing="0" cellpadding="0" class="table" >
  <tr>
    <td align="center"><h2>HOJA - CONTRATO COMPRAVENTA OBJETOS METALES PRECIOSOS</h2></td>
  </tr>
  <tr>
    <td><h3>Datos del establecimiento</h3></td>
  </tr>
</table>

<table width="100%"  cellspacing="0" cellpadding="0" class="table" >
  <tr>
    <td width="63%" class="table td"><p>Nombre</p>
    <p><strong><?php echo $nombreempresa; ?></strong></p></td>
    <td width="37%" class="table td"><p>NIF</p>
    <p><strong><?php echo $numeroidentificaciontienda; ?></strong></p></td>
  </tr>
  <tr>
    <td class="table td"><p>Calle</p>
    <p><strong><?php echo $calletienda; ?></strong></p></td>
    <td class="table td"><p>Nº</p>
    <p><strong><?php echo $numerocalle; ?></strong></p></td>
  </tr>
  <tr>
    <td class="table td"><p>Localidad</p>
    <p><strong><?php echo $poblaciontienda; ?></strong></p></td>
    <td class="table td"><p>Provincia</p>
    <p><strong><?php echo $provinciatienda; ?></strong></p></td>
  </tr>
</table>

<table width="100%" cellspacing="0" cellpadding="0" class="table" >
  <tr>
    <td><h3>Datos de la operación</h3></td>
  </tr>
</table>

<table width="100%" cellspacing="0" cellpadding="0" class="table" >
  <tr>
    <td width="47%" class="table td"><p>Nº de contrato</p>
    <p><strong><?php echo $rsItem['id_lote']; ?></strong></p></td>
    <td width="53%" class="table td"><p>Fecha</p>
    <p><strong><?php echo $sqldate; ?></strong></p></td>
  </tr>
  <tr>
   <td class="table td"><p>Tipo de contrato</p>
	
	<p><input type="checkbox" name="radio" <?php echo $comprado; ?> > Compraventa
	
	<input type="checkbox" name="radio"  <?php echo $empenado; ?> > Empeño
	
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
</table>

<table width="100%" cellspacing="0" cellpadding="0" class="table" >
  <tr>
    <td width="39%" class="table td"><p>Nombre</p>
    <p><strong><?php echo $rsItem['nombre']; ?></strong></p></td>
    <td width="61%" class="table td"><p>Apellidos</p>
    <p><strong><?php echo $rsItem['apellido']; ?></strong></p></td>
  </tr>
  
  <tr>
    <td class="table td">
	 <p>Nº documento</p>
    <p>
	 <strong><?php echo $rsItem['identificacion']; ?></strong></p>
	 </td>
    <td class="table td">
	 <p>Tipo</p>
    <p>
	 <input type="checkbox" name="radio" <?php echo $documento; ?> > DNI
	 <input type="checkbox" name="radio" <?php echo $otrodocumento; ?> > Otro (indicar):<strong> <?php echo $otrod; ?></strong>
	 </p>
	 </td>
  </tr>
  
  <tr>
   <td class="table td">
	<p>Sexo</p>
	<p>
	<input type="checkbox" name="radio" <?php echo $sexhombre; ?> >Varón
	<input type="checkbox" name="radio" <?php echo $sexmujer; ?> >Mujer
	</p>
	</td>
   <td class="table td">
	<p>Fecha de nacimiento</p>
   <p><strong><?php echo $sqldatne; ?></strong></p>
	</td>
  </tr>
  
  <tr>
    <td class="table td">
    <p>País de Nacionalidad</p>
    <p><input type="checkbox" name="radio" <?php echo $nacion; ?> >España
	 <input type="checkbox" name="radio" <?php echo $otronacion; ?> >Otro (indicar):<strong><?php echo $otron; ?></strong>
	 </p>
	 </td>
    <td class="table td"><p>Teléfono</p>
    <p><strong><?php echo $rsItem['telefono']; ?></strong></p></td>
  </tr>
  <tr>
    <td colspan="2" class="table td"><p>Domicilio actual: /calle y nº</p>
    <p><strong><?php echo $rsItem['direccion']; ?></strong></p></td>
  </tr>
  <tr>
    <td class="table td"><p>Localidad</p>
    <p><strong><?php echo $rsItem['c_poblacion']; ?></strong></p></td>
    <td class="table td"><p>Provincia</p>
    <p><strong><?php echo $rsItem['c_provincia']; ?></strong></p></td>
  </tr>
</table>

    
<table width="100%" cellspacing="0" cellpadding="0" class="table" >
  <tr>
    <td style=" height:50px; padding-top:10px;"><h3>Objeto/s del presente contrato (individualizados)</h3></td>
  </tr>
</table>

<?
$querys = "SELECT * FROM articulos_$sucursal WHERE id_lote_articulos =$idl ORDER BY id_articulo_lote  ";
mysql_query ("SET NAMES 'utf8'");
$Itemas = mysql_query($querys, $conexion);
$count = 0;
while($row = mysql_fetch_assoc($Itemas)){ 

if($row['active_inscripciones']==si){ $inscsi="checked='checked'"; $inscno=""; }else{ $inscsi=""; $inscno="checked='checked'"; }

if($row['active_piedras']==si){ $piedcsi="checked='checked'"; $piedno=""; }else{ $piedcsi=""; $piedno="checked='checked'"; }

extract($row); 

$count += 1;
if( $count == 3 ){
    echo "<div class='saltoDePagina'></div>";
}else if( $count == 7 ){
    echo "<div class='saltoDePagina'></div>";
}else if( $count == 11 ){
    echo "<div class='saltoDePagina'></div>";
}else if( $count == 15 ){
    echo "<div class='saltoDePagina'></div>";
}else if( $count == 19 ){
    echo "<div class='saltoDePagina'></div>";
}else if( $count == 23 ){
    echo "<div class='saltoDePagina'></div>";
}else if( $count == 27 ){
    echo "<div class='saltoDePagina'></div>";
}else if( $count == 31 ){
    echo "<div class='saltoDePagina'></div>";
}else if( $count == 35 ){
    echo "<div class='saltoDePagina'></div>";
}
?>
<table width="100%" cellspacing="0" cellpadding="0" class="table tablearticulo" style="margin: 10px 0;" >
  <tr>
    <td width="20%" class="table td" style=" height:50px; padding-top:10px;"><p>Nº orden </p>
    <p><strong><?php echo $id_articulo_lote; ?></strong></p></td>
    <td colspan="2" width="80%" height="60" class="table td" style=" height:50px; padding-top:10px;"><p>Clase</p>
    <p><strong><?php echo $descripcion_articulo; ?></strong></p></td>
  </tr>
  <tr>
    <td class="table td" style=" height:50px; padding-top:10px;">
		 <p>Peso neto (gr.) <strong><?php echo $peso_articulo; ?></strong></p>
		 <p>Peso bruto (gr.) <strong><?php echo $peso_bruto; ?></strong></p>
    </td>
	 <td class="table td" style=" height:50px; padding-top:10px;"><p>Metales </p>
    <p><strong><?php echo $tipo_de_articulo; ?></strong></p></td>
    <td class="table td" style=" height:50px; padding-top:10px;"><p>Peso quilates</p>
    <p><strong><?php echo $ley; ?></strong></p></td>
  </tr>
  <tr>
    <td class="table td" style=" height:50px; padding-top:10px;"><p>Grabaciones</p>
    <p><input type="checkbox" name="radio" <?php echo $inscsi; ?> > Si
	 <input type="checkbox" name="radio" <?php echo $inscno; ?> > No
	 </p>
	 </td>
    <td  colspan="2" class="table td" style=" height:50px; padding-top:10px;"><p>Detalles  </p>
    <p><strong><?php echo $inscripciones; ?></strong></p></td>
  </tr>
  <tr>
    <td class="table td" style=" height:50px; padding-top:10px;"><p>Piedras</p>
	 <p><input type="checkbox" name="radio" <?php echo $piedcsi; ?> > Si
	 <input type="checkbox" name="radio" <?php echo $piedno; ?> > No
	 </p>
	 </td>
	 <td class="table td" style=" height:50px; padding-top:10px;"><p>Peso quilates</p>
    <p><strong><?php echo $kilate_piedras; ?></strong></p></td>
    <td class="table td" style=" height:50px; padding-top:10px;"><p>Clase</p>
    <p><strong><?php echo $descripcion_piedras; ?></strong></p></td>
  </tr>
  <tr>
    <td  colspan="2"  width="47%" class="table td" style=" height:50px; padding-top:10px;"><p>Precio abonado</p>
    <p><strong><?php echo $precio_compra_articulo; ?> €</strong></p></td>
    <td width="53%" class="table td" style=" height:50px; padding-top:10px;"><p>Fotografia detallada objeto</p>
    <p><input type="checkbox" name="radio" checked="checked"> Si</p></td>
  </tr>
  <tr>
    <td  colspan="3"  width="100%" class="table td" height="15" style=" height:50px; padding-top:10px;"><p>(*) Fecha venta posterior: <strong></strong></22wwp>
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
<table width="100%" cellspacing="0" cellpadding="0" class="table tablearticulo" style="margin-top:0px;" >
  <tr>
    <td width="47%" class="table td"><p>Precio Total</p>
    <p><strong><?php echo $preciototal; ?> €</strong></p></td>
	 <td class="table td"><p>Peso total Objetos</p>
    <p><strong><?php echo $pesototal; ?> grs</strong></p></td>
	 <td class="table td"><p>Pisua/ peso Bruto Objetos</p>
    <p><strong><?php echo $pesobruto; ?> grs</strong></p></td>
  </tr>
</table>

<div class="textos_docs" style="margin:20px 0px;">
    <style>
        .titulo_texto_doc{
            font-size: 8px;
            color: #696969;
            font-style: italic;
        }
        .texto_doc {
            font-size: 8px;
            color: #696969;
            text-transform: initial;
            font-style: italic;
            text-align: justify;
        }
    </style>
<?
    $tipo_documento = "compra";
    
    $query_suc = "
    SELECT
    EMP.nombre_empresa,
    EMP.cif_empresa,
    EMP.direccion_empresa,
    EMP.poblacion_empresa,
    EMP.provincia_empresa,
    EMP.telefono_empresa,
    EMP.codigo_postal_empresa,
    EMP.email_empresa
    FROM sucursal AS SUC
    LEFT JOIN empresas AS EMP ON EMP.id_empresa = SUC.empresa_id
    WHERE SUC.id_sucursal = $sucursal
    ";
    $Item_suc = mysql_query($query_suc, $conexion);
    mysql_query ("SET NAMES 'utf8'");
    $rsItem_suc = mysql_fetch_assoc($Item_suc);
    $nombre_empresa = $rsItem_suc['nombre_empresa']; 
    $cif_empresa = $rsItem_suc['cif_empresa'];
    $direccion_empresa = $rsItem_suc['direccion_empresa'];
    $poblacion_empresa = $rsItem_suc['poblacion_empresa'];
    $provincia_empresa = $rsItem_suc['provincia_empresa'];
    $telefono_empresa = $rsItem_suc['telefono_empresa'];
    $codigo_postal_empresa = $rsItem_suc['codigo_postal_empresa'];
    $correo_electronico_empresa = $rsItem_suc['email_empresa'];
    
    $direccion_empresa = $direccion_empresa." ".$poblacion_empresa." ".$provincia_empresa." ".$codigo_postal_empresa;
?>
    <?
    $query = "SELECT titulo_text, content_text FROM textos_documentos WHERE tipo_documento = '$tipo_documento' OR tipo_documento = 'texto_legal_datos' AND state_texto_doc = 'true' ";
    $Item = mysql_query($query, $conexion);
    mysql_query ("SET NAMES 'utf8'");
    while ($rsItem = mysql_fetch_assoc($Item))
        {
            $titulo_text = $rsItem['titulo_text'];
            $content_text = $rsItem['content_text'];
            
            $buscar = array('{direccion_empresa}', '{correo_electronico_empresa}', '{nombre_empresa}', '{telefono_empresa}', '{cif_empresa}');
            $reemplazar = array($direccion_empresa, $correo_electronico_empresa, $nombre_empresa, $telefono_empresa, $cif_empresa);
            $content_text_final = str_replace($buscar, $reemplazar, $content_text);
    ?>
    <strong class="titulo_texto_doc"><? echo $titulo_text; ?></strong>
    <p class="texto_doc"><? echo $content_text_final; ?></p>
    <br>
    <? } ?>
</div>

<table width="100%" cellspacing="0" cellpadding="0" class="table" >
  <tr>
    <td align="center"><h4>En <?php echo $poblaciontienda; ?>  a <?php echo $d; ?> de  <?php echo $m; ?> de <?php echo $a; ?></h4></td>
  </tr>
</table>

<table width="100%" border="0" cellspacing="0" cellpadding="0" class="table">
  <tr>
    <td align="center">
        <h6 style="line-height: 20px;margin: 0;">Sello y firma del establecimiento</h6>
        <? echo $sello_sucursal;  ?>
    </td>
    <td align="center"><h6 style="line-height: 20px;margin: 0;">Firma del interesado</h6><? echo $signatureInsert_cliente; ?></td>
	 <td><h6>&nbsp;<br /> &nbsp;</h6></td>
	 <td align="right" style="text-align:right;" ><h5>&nbsp; <br />Nº Pág.&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;</h5></td>
  </tr>
</table>

<table width="100%" border="0" cellspacing="0" cellpadding="0" class="table" style=" margin-top: 100px; margin-bottom: 0px;">
    <tr>
    <?php
      $sqlCliente = "SELECT sucursal FROM clientes WHERE id_cliente = ".$id_cliente;
      $queryCliente = mysql_query($sqlCliente, $conexion);
      $rowCliente = mysql_fetch_array($queryCliente);
      if(empty($rowCliente)) die();
      $sucursalCliente = $rowCliente['sucursal'];
    $querys = "SELECT * FROM fotos_app_$sucursalCliente WHERE id_cliente = ".$id_cliente." ORDER BY id_foto DESC ";
    mysql_query ("SET NAMES 'utf8'");
    $Itemas = mysql_query($querys, $conexion);
    while($row = mysql_fetch_assoc($Itemas)){
        echo  '<td><img src="../../photos/'.$row['nombre_foto'].'" width="400" height="auto" alt=""/></td>';
    }
      ?>
    </tr>
</table>

</body>
</html>