<?php
require("session_file.php");
?>
<?php
require_once("conexion.php");

$date2= $hasta;
$fecha2=date('d-m-Y',strtotime($date2));

$date1= $desde;
$fecha1=date('d-m-Y',strtotime($date1));

$hoy=date('d-m-Y');

$cro = $compra;

if($desde==0 || $hasta==0){
	if($cro==mes){
		$query = "SELECT * FROM lotes_$lotessucursal WHERE MONTH(fecha_compra) IN(MONTH(CURDATE()), (MONTH(CURDATE()-1))
    AND YEAR(fecha_compra) = YEAR(CURDATE())) ";
	}elseif($cro==dia){
		$query = "SELECT * FROM lotes_$sucursal WHERE fecha_compra = curdate()  ORDER BY fecha_compra ASC ";
	}elseif($cro==todos){
		$query = "SELECT * FROM lotes_$sucursal ORDER BY fecha_compra ASC ";
	}elseif($cro==0){
		$query = "SELECT * FROM lotes_$sucursal ORDER BY fecha_compra ASC  ";
	}
}else{
	if($cro==si){
		$query = "SELECT * FROM lotes_$lotessucursal WHERE fecha_compra BETWEEN '".$fecha1."' AND '".$fecha2."' ORDER BY fecha_compra DESC  ";
	}elseif($cro==no){
	}elseif($cro==mes){
		$query = "SELECT * FROM lotes_$lotessucursal WHERE MONTH(fecha_compra) IN(MONTH(CURDATE()), (MONTH(CURDATE()-1))
    AND YEAR(fecha_compra) = YEAR(CURDATE())) ";		
	}		
}
$date= $rsItem['fecha_compra'];
$sqldate=date('d-m-Y',strtotime($date));


//final de consulta////
///////////////////////

//Consulto sucursal
$querys = "SELECT * FROM sucursal WHERE id_sucursal= '$lotessucursal' ";
mysql_query ("SET NAMES 'utf8'");
$Ietem = mysql_query($querys, $conexion);
$rseItem = mysql_fetch_assoc($Ietem);

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
$mpdf->SetDisplayMode('fullpage');
$mpdf->hyphenate = true;
$mpdf->SHYlang = 'es';

$html = '
<html>
<head>
<meta charset="UTF-8" />
<style>
body {font-family: arial;
    font-size: 8pt;
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
		 <td height="10"  style="vertical-align:middle; font-size:15px; font-weight: bold;">Listado de lotes comprados</td>
	    </tr>
	  <tr>
	    <td height="10" style="vertical-align:middle; ">Fecha de presentación: <span style="font-weight: bold; font-size: 11pt;">'.$hoy.' </span></td>
	    </tr>
	  <tr>
	    <td height="10" style="vertical-align:middle; ">Período: desde: <span style="font-weight: bold; font-size: 11pt;">'.$fecha1.' </span> hasta: <span style="font-weight: bold; font-size: 11pt;">'.$fecha2.'</span></td>
	    </tr>
    </table>
    </td>
</tr>
</table>
</htmlpageheader>

<sethtmlpageheader name="myheader" value="on" show-this-page="1"  />

mpdf-->
<table width="100%" style="font-family: Arial; font-size:12px; " cellpadding="5">
<tr>
<td width="20%" style="">Sucursal: '.$rseItem['nombre_sucursal'].'<br />'.$rseItem['identificacion_tienda'].'  '.$rseItem['numero_identificacion_tienda'].'<br />'.$rseItem['direccion_tienda'].'<br /></td>
<td width="80%" >'.$rseItem['poblacion_tienda'].'<br />'.$rseItem['codigo_postal_tienda'].'<br />Tel.: '.$rseItem['telefono_tienda'].'</td>
</tr>
</table>

<table class="items" width="100%" style=" font-size: 9pt; border-collapse: collapse;" cellpadding="8" border="1">
<thead>
<tr>
  <td width="4%">Lote Nº</td>
  <td width="10%">Fecha compra</td>
  <td width="5%">Peso</td>
  <td width="5%">Unidades</td>
  <td width="5%">Tipo</td>
  <td width="10%">Liberado</td>
</tr>
</thead>
<tbody>
';

$html .= '<div style=" height:600px;">';

$date2= $hasta;
$fecha2=date('Y-m-d',strtotime($date2));

$date1= $desde;
$fecha1=date('Y-m-d',strtotime($date1));

$query = "SELECT * FROM lotes_$lotessucursal WHERE fecha_compra BETWEEN '".$desde."' AND '".$hasta."' ORDER BY id_lote ASC ";

mysql_query ("SET NAMES 'utf8'");
$Itemas = mysql_query($query, $conexion);
while($row = mysql_fetch_assoc($Itemas)){ 
$pesototal+=$row['peso'];
extract($row); 
$date1= $fecha_compra;
$fecha1=date('d-m-Y',strtotime($date1));


$html .= '
<tr>
<td height="10" align="center" style="vertical-align:middle; ">'.$id_lote.'</td>
<td align="center" style="vertical-align:middle; ">'.$fecha1.'</td>
<td align="center" style="vertical-align:middle; ">'.$peso.' grs.</td>
<td align="center" style="vertical-align:middle; ">'.$cantidad_articulos.'</td>
<td align="center" style="vertical-align:middle; ">'.$tipo_de_lote.'</td>
<td align="center" style="vertical-align:middle; ">'.$liberado.'</td>
</tr>
';
}

echo $pesototal;

$html .= '
</tbody>
</table>
';

$html .= '
</div>
</body>
</html>
';
$mpdf->WriteHTML($html);
//$mpdf->Output('Listado de compras 2º mano Silver Gold '.$tienda.' '.$fecha1.' / '.$fecha2.'.pdf','D');
$mpdf->Output();
exit;
?>