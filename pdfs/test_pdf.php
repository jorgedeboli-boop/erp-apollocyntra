<?php
require_once '../vendor/autoload.php';

// Crear instancia de mPDF
$mpdf = new \Mpdf\Mpdf([
    'mode' => 'utf-8',
    'format' => 'A4',
]);


$html = '
<html>
<head>
<meta charset="UTF-8" />
<style>
body {font-family: arial;
    font-size: 12pt;
	 color:#000000;
}
</style>
</head>
<body>
<p><strong>¡Funciona perfectamente! ✓</strong></p>
</body>
</html>
';

// Escribir HTML en el PDF
$mpdf->WriteHTML($html);

// Mostrar en el navegador
$mpdf->Output('prueba.pdf', 'I'); // 'I' = inline en navegador
?>