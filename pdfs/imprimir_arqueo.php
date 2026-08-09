<?php
require("session_file.php");
?>
<?php
require_once("conexion.php");

//$query = "SELECT * FROM movimientos_de_caja WHERE fecha_apunte BETWEEN '".$fecha1."' AND '".$fecha2."' ORDER BY fecha_apunte ASC ";
//LEFT JOIN sucursal ON empenos.sucursal=sucursal.nombre_sucursal 
//mysql_query ("SET NAMES 'utf8'");
//$Item = mysql_query($query, $conexion);
//$rsItem = mysql_fetch_assoc($Item);

//$dat= $fecha1;
//$date1=date('d-m-Y',strtotime($dat));
//
//$dat2= $fecha2;
//$date2=date('d-m-Y',strtotime($dat2));

$date2= $hasta;
$fecha2=date('Y-m-d',strtotime($date2));

$date1= $desde;
$fecha1=date('Y-m-d',strtotime($date1));

$cro = $compra;

if($desde==0 || $hasta==0){
	if($cro==mes){
		$query = "SELECT * FROM cierre_caja_$sucursal WHERE MONTH(fecha_cierre)='$mesactual' ";
	}elseif($cro==dia){
		$query = "SELECT * FROM cierre_caja_$sucursal WHERE fecha_cierre = curdate() ORDER BY fecha_cierre DESC ";
	}elseif($cro==todos){
		$query = "SELECT * FROM cierre_caja_$sucursal ORDER BY fecha_cierre DESC ";
	}elseif($cro==0){
		$query = "SELECT * FROM cierre_caja_$sucursal ORDER BY fecha_cierre DESC ";
	}
}else{
	if($cro==si){
		$query = "SELECT * FROM cierre_caja_$sucursal WHERE fecha_cierre BETWEEN '".$fecha1."' AND '".$fecha2."' ORDER BY fecha_cierre DESC ";
	}elseif($cro==mes){
		$query = "SELECT * FROM cierre_caja_$sucursal WHERE MONTH(fecha_cierre) IN(MONTH(CURDATE()), (MONTH(CURDATE()-1))
    AND YEAR(fecha_cierre) = YEAR(CURDATE())) ";
	}		
}
$date= $rsItem['fecha_cierre'];
$sqldate=date('d-m-Y',strtotime($date));


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
		 <td height="10"  style="vertical-align:middle; font-size:15px; font-weight: bold;">Reporte de arqueo '.$nombre_suc.'</td>
	    </tr>
	  <tr>
	    <td height="10" style="vertical-align:middle; ">Desde: <span style="font-weight: bold; font-size: 11pt;">'.$date1.'</span></td>
	    </tr>
	  <tr>
	    <td height="10" style="vertical-align:middle; ">Hasta: <span style="font-weight: bold; font-size: 11pt;">'.$date2.'</span></td>
	    </tr>
    </table>
    </td>
</tr>
</table>
</htmlpageheader>

<sethtmlpageheader name="myheader" value="on" show-this-page="1"  />

mpdf-->

';


$html .= '
<div style=" height:600px;">
<table class="items" width="100%" style=" font-size: 9pt; border-collapse: collapse;" cellpadding="8">
<thead>
<tr>
  <td width="8%">Nº</td>
  <td width="15%">Fecha de arqueo</td>
  <td width="30%">Caja</td>
  <td width="30%">Efectivo</td>
  <td align="right" width="15%">Diferencia</td>
</tr>
</thead>
<tbody>
';


//$querys = "SELECT * FROM movimientos_de_caja WHERE fecha_apunte BETWEEN '".$fecha1."' AND '".$fecha2."' ORDER BY fecha_apunte ASC ";

if($desde==0 || $hasta==0){
	if($cro==mes){
		$query = "SELECT * FROM cierre_caja_$sucursal WHERE MONTH(fecha_cierre)='$mesactual' ";
	}elseif($cro==dia){
		$query = "SELECT * FROM cierre_caja_$sucursal WHERE fecha_cierre = curdate() ORDER BY fecha_cierre DESC ";
	}elseif($cro==todos){
		$query = "SELECT * FROM cierre_caja_$sucursal ORDER BY fecha_cierre DESC ";
	}elseif($cro==0){
		$query = "SELECT * FROM cierre_caja_$sucursal ORDER BY fecha_cierre DESC ";
	}
}else{
	if($cro==si){
		$query = "SELECT * FROM cierre_caja_$sucursal WHERE fecha_cierre BETWEEN '".$fecha1."' AND '".$fecha2."' ORDER BY fecha_cierre DESC ";
	}elseif($cro==mes){
		$query = "SELECT * FROM cierre_caja_$sucursal WHERE MONTH(fecha_cierre) IN(MONTH(CURDATE()), (MONTH(CURDATE()-1))
    AND YEAR(fecha_cierre) = YEAR(CURDATE())) ";
	}		
}
mysql_query ("SET NAMES 'utf8'");
$Itemas = mysql_query($query, $conexion);
while($row = mysql_fetch_assoc($Itemas)){ 
extract($row); 
$date1= $fecha_cierre;
$fecha1=date('d-m-Y',strtotime($date1));

$html .= '<tr>
<td height="10" align="center" style="vertical-align:middle; ">'.$id_fecha_cierre.'</td>
<td align="center" style="vertical-align:middle; ">'.$fecha1.'</td>
<td align="center" style="vertical-align:middle; ">'.$caja.' €</td>
<td align="center" style="vertical-align:middle; ">'.$efectivo.' €</td>
<td align="right" style="vertical-align:middle; ">'.$diferencia.' €</td>
</tr>';
}

$html .= '
</tbody>
</table>
</div>
</body>
</html>
';
$mpdf->WriteHTML($html);
//$mpdf->Output('Reporte de caja.pdf','D');
$mpdf->Output();

exit;

?>