<?php

include("../MPDF54/mpdf.php");

$mpdf=new mPDF('win-1252','A4','','',13,13,30,5,5,10); 
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
    background-color: #FFFFFF;
    border: 0mm none #000000;
}
.items td.totals {
    text-align: right;
}
</style>
</head>
<body>

<!--mpdf
<htmlpageheader name="myheader" >
<table width="100%"><tr>
<td width="55%">
<img src="fotos/logotipo.svg" width="333" height="85">
</td>
<td width="45%" style="text-align: right;">
   <table width="100%%" border="0" cellspacing="0" cellpadding="0">
	  <tr>
	    <td height="30" style="vertical-align:middle;">Contrato de compra de oro</td>
	    </tr>
	  <tr>
	    <td height="30" style="vertical-align:middle;">Fecha: <span style="font-weight: bold; font-size: 12pt;">11/12/2012</span></td>
	    </tr>
	  <tr>
	    <td height="30" style="vertical-align:middle;">Operación Nº <span style="font-weight: bold; font-size: 12pt;">0012345</span></td>
	    </tr>
    </table>
    </td>
</tr>
</table>
</htmlpageheader>

<sethtmlpageheader name="" value="off" show-this-page="1"  />

mpdf-->


<table width="100%" style="font-family: Arial; ma" cellpadding="10">

<tr>
<td width="45%" style=""><span style="font-size: 9pt;  font-family: arial; background-color: #EEEEEE; padding:5px;">&nbsp;&nbsp;</span><br /><br />&nbsp;<br />&nbsp;<br />&nbsp;<br />&nbsp;<br />&nbsp; &nbsp;</td>
<td width="10%">&nbsp;</td>
<td width="45%" style=""><span style="font-size: 9pt;  font-family: arial; background-color: #EEEEEE; padding:5px;">&nbsp;&nbsp;</span><br /><br />
&nbsp;<br />&nbsp;<br />&nbsp;<br />&nbsp;<br />&nbsp;</td>
</tr>
</table>

<table width="100%" style="font-family: Arial;" cellpadding="10">
<tr>
<td style=""><span style="font-size: 9pt;  font-family: arial;">&nbsp;</span></td>
</tr>
<tr>
<td style=""><span style="font-size: 9pt;  font-family: arial;">&nbsp;</span></td>
</tr>
</table>

<table class="items" width="100%" style=" font-size: 9pt; border-collapse: collapse;" cellpadding="8">
<thead>
<tr>
  <td width="8%">&nbsp;</td>
  <td width="8%">&nbsp;</td>
  <td width="55%">&nbsp;</td>
  <td width="45%">&nbsp;</td>
  </tr>
</thead>
<tbody>
<!-- ITEMS HERE -->

<tr>
<td height="38" align="center" style="vertical-align:middle;">&nbsp;</td>
<td align="center" style="vertical-align:middle;">&nbsp;</td>
<td style="vertical-align:middle;">&nbsp;</td>
<td style="vertical-align:middle;">&nbsp;</td>
</tr>

<tr>
<td height="38" align="center" style="vertical-align:middle;">&nbsp;</td>
<td align="center" style="vertical-align:middle;">&nbsp;</td>
<td style="vertical-align:middle;">&nbsp;</td>
<td style="vertical-align:middle;">&nbsp;</td>
</tr>

<tr>
<td height="38" align="center" style="vertical-align:middle;">&nbsp;</td>
<td align="center" style="vertical-align:middle;">&nbsp;</td>
<td style="vertical-align:middle;">&nbsp;</td>
<td style="vertical-align:middle;">&nbsp;</td>
</tr>

<tr>
<td height="38" align="center" style="vertical-align:middle;">&nbsp;</td>
<td align="center" style="vertical-align:middle;">&nbsp;</td>
<td style="vertical-align:middle;">&nbsp;</td>
<td style="vertical-align:middle;">&nbsp;</td>
</tr>

<tr>
<td height="38" align="center" style="vertical-align:middle;">&nbsp;</td>
<td align="center" style="vertical-align:middle;">&nbsp;</td>
<td style="vertical-align:middle;">&nbsp;</td>
<td style="vertical-align:middle;">&nbsp;</td>
</tr>

<tr>
<td height="38" align="center" style="vertical-align:middle;">&nbsp;</td>
<td align="center" style="vertical-align:middle;">&nbsp;</td>
<td style="vertical-align:middle;">&nbsp;</td>
<td style="vertical-align:middle;">&nbsp;</td>
</tr>

<tr>
<td height="38" align="center" style="vertical-align:middle;">&nbsp;</td>
<td align="center" style="vertical-align:middle;">&nbsp;</td>
<td style="vertical-align:middle;">&nbsp;</td>
<td style="vertical-align:middle;">&nbsp;</td>
</tr>

<tr>
<td height="38" align="center" style="vertical-align:middle;">&nbsp;</td>
<td align="center" style="vertical-align:middle;">&nbsp;</td>
<td style="vertical-align:middle;">&nbsp;</td>
<td style="vertical-align:middle;">&nbsp;</td>
</tr>

<tr>
<td height="38" align="center" style="vertical-align:middle;">&nbsp;</td>
<td align="center" style="vertical-align:middle;">&nbsp;</td>
<td style="vertical-align:middle;">&nbsp;</td>
<td style="vertical-align:middle;">&nbsp;</td>
</tr>

<tr>
<td height="38" align="center" style="vertical-align:middle;">&nbsp;</td>
<td align="center" style="vertical-align:middle;">&nbsp;</td>
<td style="vertical-align:middle;">&nbsp;</td>
<td style="vertical-align:middle;">&nbsp;</td>
</tr>

<tr>
<td height="38" align="center" style="vertical-align:middle;">&nbsp;</td>
<td align="center" style="vertical-align:middle;">&nbsp;</td>
<td style="vertical-align:middle;">&nbsp;</td>
<td style="vertical-align:middle;">&nbsp;</td>
</tr>

<tr>
<td height="38" align="center" style="vertical-align:middle;">&nbsp;</td>
<td align="center" style="vertical-align:middle;">&nbsp;</td>
<td style="vertical-align:middle;">&nbsp;</td>
<td style="vertical-align:middle;">&nbsp;</td>
</tr>

<!-- END ITEMS HERE -->
<tr>
<td class="blanktotal" colspan="5"></td>
</tr>
</tbody>
</table>

<table class="items" width="100%" style="font-size: 9pt; border-collapse: collapse;" cellpadding="5">
<thead>
<tr>
  <td width="33%">&nbsp;</td>
  <td width="33%">Total a pagar</td>
  <td width="33%">&nbsp;</td>
  </tr>
</thead>
<tbody>
<!-- ITEMS HERE -->
<tr>
<td align="center">&nbsp;</td>
<td align="center">1.500 €</td>
<td align="center">&nbsp;</td>
</tr>
<!-- END ITEMS HERE -->
<tr>
<td class="blanktotal" colspan="3"></td>
</tr>
</tbody>
</table>


</body>
</html>
';

$mpdf->WriteHTML($html);

$mpdf->Output('Ficha-Contrato-.pdf','D');
//$mpdf->Output(); exit;

exit;

?>