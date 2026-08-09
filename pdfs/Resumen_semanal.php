<?php
require("session_file.php");

require("../conexion.php");

$query = "SELECT * FROM envios WHERE id_envio = $id_envio ";
$Item = mysql_query($query, $conexion);
mysql_query ("SET NAMES 'utf8'");
$rsItem = mysql_fetch_assoc($Item);

$f1= $rsItem['desde_fecha'];
$d_f=date('d-m-Y',strtotime($f1));

$f2= $rsItem['hasta_fecha'];
$h_f=date('d-m-Y',strtotime($f2));

$f3= $rsItem['fecha_envio'];
$f_e=date('d-m-Y',strtotime($f3));
   

?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
<style>
body{
	font-family:Arial, Helvetica, sans-serif;
}
table{
	text-align:center;
	font-size:14px;
	margin-bottom:25px;
}
th{
	background:rgb(102,102,102);
	color:#FFFFFF;
	
}
.punteado{
  border-style: dotted;
   border-width: 1px;
   border-color: #ffffff;
}
.punteado2{
  border-style: dotted;
   border-width: 2px;
   border-color: rgb(102,102,102);
	height:120px;
	text-align:left;
	margin:0 auto;
}
.punteado h2{
	line-height:0px;
}
.titulo{
	background:rgb(102,102,102);
	color:#FFFFFF;
	padding:px 0;
	width:100%;
	text-align:center;
}
.titulo2{
	background:rgb(102,102,102);
	color:#FFFFFF;
	padding: 0;
	margin: 0;
	line-height:22px;
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

<table  style="background-color:#ffffff" width="100%" cellpadding="10" cellspacing="0" >

	<tr>
	  <th><h3 class="titulo">Fecha de envío <?php if($d_f=='30-11--0001'){ echo ""; }else{ echo $f_e; } ?></h3></th>
     <th style="background:#FFFFFF;"></th>
     <th><h3 class="titulo">Resumen semanal Nº <?php echo $id_envio; ?></h3></th>
     <th style="background:#FFFFFF;"></th>
     <th><h3 class="titulo"><?php echo $nombre_suc; ?></h3></th>
  </tr>
  <tr style="background:#FFFFFF;">
  	<th  style="background:#FFFFFF;" colspan="5"></th>
  </tr>
  
  <tr>
	  <th><h3 class="titulo">Desde <?php if($d_f=='30-11--0001'){ echo ""; }else{ echo $d_f; } ?></h3></th>
     <th style="background:#FFFFFF;"></th>
     <th><h3 class="titulo">Hasta <?php if($d_f=='30-11--0001'){ echo ""; }else{ echo $h_f; } ?></h3></th>
     <th style="background:#FFFFFF;"></th>
     <th><h3class="titulo">Cantidad de lotes: <?php echo $rsItem['cantidad_lotes']; ?></h3></th>
  </tr>
  
</table>

<table border="1" bordercolor="#666666" style="background-color:#ffffff" width="100%" cellpadding="1" cellspacing="0" >

	<tr>
	  <th colspan="5"><h3 class="titulo2">Resumen oro</h3></th>
   </tr>
  
	<tr>
		<th height="25" class="punteado">Total 24kl</th>
		<th class="punteado">Total 22kl</th>
      <th class="punteado">Total 18kl</th>
      <th class="punteado">Total 14kl</th>
      <th class="punteado">Total 8,9 y 10kl</th>
	</tr>
   
   <tr>
		<td><?php echo $rsItem['kl_24']; ?> grs</td>
		<td><?php echo $rsItem['kl_22']; ?> grs</td>
      <td><?php echo $rsItem['kl_18']; ?> grs</td>
      <td><?php echo $rsItem['kl_14']; ?> grs</td>
      <td><?php echo $rsItem['kl_8910']; ?> grs</td>
	</tr>

</table>

<table border="1" bordercolor="#666666" style="background-color:#ffffff" width="100%" cellpadding="1" cellspacing="0" >
   
   <tr>
		<th height="25" class="punteado">Peso Neto solo oro</th>
		<th class="punteado">Total abonado</th>
      <th class="punteado">Media</th>
      <th class="punteado">Merma oro</th>
      <th class="punteado">Peso Bruto solo oro</th>
	</tr>
   
   <tr>
		<td><?php echo $rsItem['peso_neto_oro_lotes']; ?> grs</td>
		<td><?php echo $rsItem['total_abonado_oro']; ?> €</td>
      <td><?php echo $rsItem['media_oro']; ?> €/gr</td>
      <td><?php echo $rsItem['merma_oro']; ?> grs</td>
      <td><?php echo $rsItem['peso_bruto_oro_lotes']; ?> grs</td>
	</tr>
	
</table>

<table border="1" bordercolor="#666666" style="background-color:#ffffff" width="100%" cellpadding="1" cellspacing="0" >
   
   <tr>
		<th height="25" class="punteado">Cantidad de sobres oro</th>
		<th class="punteado">Peso bruto con sobres oro</th>
	</tr>
   
   <tr>
		<td><?php echo $rsItem['cantidad_sobres_oro']; ?> unidades</td>
      <td><?php echo $rsItem['peso_bruto_con_sobres_oro']; ?> grs</td>
	</tr>
	
</table>

<table border="1" bordercolor="#666666" style="background-color:#ffffff" width="100%" cellpadding="1" cellspacing="0" >
   
   <tr>
	  <th colspan="5"><h3 class="titulo2">Resumen plata</h3></th>
  </tr>
  
  
   <tr>
		<th height="25" class="punteado">Peso Neto solo plata</th>
		<th class="punteado">Total abonado</th>
      <th class="punteado">Media</th>
      <th class="punteado">Merma plata</th>
      <th class="punteado">Peso Bruto solo plata</th>
	</tr>
   
   <tr>
		<td><?php echo $rsItem['peso_neto_plata_lotes']; ?> grs</td>
		<td><?php echo $rsItem['total_abonado_plata']; ?> €</td>
      <td><?php echo $rsItem['media_plata']; ?> €/gr</td>
      <td><?php echo $rsItem['merma_plata']; ?> grs</td>
      <td><?php echo $rsItem['peso_bruto_plata_lotes']; ?> grs</td>
	</tr>
	
</table>

<table border="1" bordercolor="#666666" style="background-color:#ffffff" width="100%" cellpadding="1" cellspacing="0" >
   
   <tr>
		<th height="25" class="punteado">Cantidad de sobres plata</th>
		<th class="punteado">Peso bruto con sobres plata</th>
	</tr>
   
   <tr>
		<td><?php echo $rsItem['cantidad_sobres_plata']; ?> unidades</td>
      <td><?php echo $rsItem['peso_bruto_con_sobres_plata']; ?> grs</td>
	</tr>
	
</table>

<table border="1" bordercolor="#666666" style="background-color:#ffffff" width="100%" cellpadding="1" cellspacing="0" >
   
   <tr>
		<th height="25" class="punteado">Lotes en este envío</th>
	</tr>
   
   <tr>
		<td>
      <?php 
$query = "SELECT * FROM lotes_$sucursal WHERE envio_numero = $id_envio ";
$Item = mysql_query($query, $conexion);
mysql_query ("SET NAMES 'utf8'");
$numero_lotes = mysql_num_rows($Item);

?><?php while ($raItem = mysql_fetch_assoc($Item)){ echo "Nº"; echo $raItem['id_lote']; echo ", "; } ?></td>
	</tr>
	
</table>

<table border="1" bordercolor="#666666" style="background-color:#ffffff" width="100%" cellpadding="1" cellspacing="0" >
   
   <tr>
		<th height="25" class="punteado">Observaciones</th>
	</tr>
   
   <tr>
		<td><?php if(!$rsItem['observaciones_envio']){ echo "Envío sin observaciones";; }else{ echo $rsItem['observaciones_envio']; } ?></td>
	</tr>
	
</table>

<table   width="50%" cellpadding="1" cellspacing="0" >
   
   <tr>
		<td>Firma y sello de la tienda:</td>
	</tr>
	
</table>
<table  class="punteado2"   width="75%" cellpadding="1" cellspacing="0" >
   
   <tr>
		<td></td>
	</tr>
	
</table>

</body>
</html>