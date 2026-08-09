<?php
require_once '../include/session.php';
require_once '../include/functions.php';
require_once '../vendor/autoload.php';
require_once __DIR__ . '/contrato_compra_datos.php';
require_once __DIR__ . '/contrato_compra_plantilla.php';

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    header('Location: ../dashboard.php');
    exit;
}

$id_lote = isset($_GET['id_lote']) ? (int) $_GET['id_lote'] : 0;
$id_sucursal = isset($_GET['id_sucursal']) ? (int) $_GET['id_sucursal'] : 0;

if ($id_lote <= 0 || $id_sucursal <= 0) {
    header('Location: ../dashboard.php');
    exit;
}

$conexion = conectar_bd();
$datos = contrato_compra_cargar_datos($conexion, $id_lote, $id_sucursal);
mysqli_close($conexion);

if ($datos === null) {
    header('Location: ../dashboard.php');
    exit;
}

$mpdf = new \Mpdf\Mpdf([
    'mode' => 'utf-8',
    'format' => 'A4',
    'margin_left' => 5,
    'margin_right' => 5,
    'margin_top' => 42,
    'margin_bottom' => 38,
    'margin_header' => 4,
    'margin_footer' => 5,
    'default_font' => 'freesans',
    'default_font_size' => 9,
]);

$mpdf->SetTitle('Contrato de compra - Lote ' . $datos['id_lote']);
$mpdf->SetDisplayMode('fullpage');

// Página 1: con cabecera y pie
$mpdf->SetHTMLHeader(contrato_compra_plantilla_header($datos));
$mpdf->SetHTMLFooter(contrato_compra_plantilla_footer($datos));
$mpdf->WriteHTML(contrato_compra_plantilla_body_pdf_pagina1($datos));

// Página 2: sin cabecera, margen superior reducido
$mpdf->SetHTMLHeader('');
$mpdf->AddPage('', '', '', '', '', 5, 5, 5, 20, 0, 5);
$mpdf->WriteHTML(contrato_compra_plantilla_body_pdf_pagina2($datos));
$mpdf->OutputHttpDownload('contrato_compra_' . $datos['id_lote'] . '.pdf');
exit;
