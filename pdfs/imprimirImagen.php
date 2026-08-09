<?php

require("session_file.php");
?>
<?php
require_once("../conexion.php");


//Obtenemos variables y verificamos seguridad
$err = false;

//Verificamos sucursal

if (empty($_GET['urlImagen'])) {
  echo "Error";
  die();
}

include("../API/MPDF54/mpdf.php");

//Create pdf object
// $mpdf = new mPDF('c', 'A4', '', '', 13, 13, 13, 13, 16, 13);
$mpdf = new mPDF('c', 'A4-L', '', '', 13, 13, 13, 13, 16, 13);
$mpdf->useOnlyCoreFonts = true;    // false is default
$mpdf->SetProtection(array('print'));
// $mpdf->SetTitle("Contrato de compra de oro");
// $mpdf->SetAuthor("Silver Gold");
// $mpdf->SetWatermarkText("Enviada");
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
img{
  width: auto;
  height: auto;
  max-width: 100%;
  max-height: 100%;
}
</style>
</head>
<body>
<!--mpdf
<htmlpagefooter name="footer">

</htmlpagefooter>
<sethtmlpagefooter name="footer" value="on" />
mpdf-->
';

$urlBase = $_SERVER['SERVER_PORT'] == '443' ? 'https://' : 'http://' . $_SERVER['HTTP_HOST'] .  str_replace('/pdfs/imprimirImagen.php', '', $_SERVER['PHP_SELF']);


$html .= '<img src="'. $urlBase . '/' . $_GET['urlImagen'].'" />';
$html .= '
</body>
</html>
';

// echo $html;
$mpdf->WriteHTML($html);
//$mpdf->Output('Contrato N� '.$rsItem['id_lote'].'.pdf','D');

/* Solo para la sucursal de eibar */
if ($sucursal === 25) {
  $mpdf->SetJS('this.print();');
}
// echo $html;
$mpdf->Output();
exit;
?>