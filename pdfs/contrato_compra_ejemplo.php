<?php
/**
 * PDF de prueba del contrato de compra.
 * Usa datos ficticios de cliente/lote y el logotipo/sello reales de la sucursal.
 *
 * Uso: pdfs/contrato_compra_ejemplo.php?id_sucursal=22
 */
require_once '../include/session.php';
require_once '../include/functions.php';
require_once '../vendor/autoload.php';
require_once __DIR__ . '/contrato_compra_datos.php';
require_once __DIR__ . '/contrato_compra_plantilla.php';

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    header('Location: ../dashboard.php');
    exit;
}

$id_sucursal = isset($_GET['id_sucursal']) ? (int) $_GET['id_sucursal'] : 0;

if ($id_sucursal <= 0) {
    header('HTTP/1.0 400 Bad Request');
    header('Content-Type: text/html; charset=UTF-8');
    echo '<!DOCTYPE html><html lang="es"><body style="font-family:arial;padding:20px;">';
    echo '<h1>Parámetro requerido</h1>';
    echo '<p>Indica la sucursal en la URL, por ejemplo:</p>';
    echo '<p><code>contrato_compra_ejemplo.php?id_sucursal=22</code></p>';
    echo '</body></html>';
    exit;
}

$conexion = conectar_bd();
$datos = contrato_compra_cargar_datos_ejemplo($conexion, $id_sucursal);
mysqli_close($conexion);

if ($datos === null) {
    header('HTTP/1.0 404 Not Found');
    header('Content-Type: text/html; charset=UTF-8');
    echo '<!DOCTYPE html><html lang="es"><body style="font-family:arial;padding:20px;">';
    echo '<h1>Sucursal no encontrada</h1>';
    echo '<p>No se encontró la sucursal <strong>' . htmlspecialchars((string) $id_sucursal, ENT_QUOTES, 'UTF-8') . '</strong>.</p>';
    echo '</body></html>';
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

$mpdf->SetTitle('Contrato de compra (prueba) - Sucursal ' . $id_sucursal);
$mpdf->SetDisplayMode('fullpage');

$mpdf->SetHTMLHeader(contrato_compra_plantilla_header($datos));
$mpdf->SetHTMLFooter(contrato_compra_plantilla_footer($datos));
$mpdf->WriteHTML(contrato_compra_plantilla_body_pdf_pagina1($datos));

$mpdf->SetHTMLHeader('');
$mpdf->AddPage('', '', '', '', '', 5, 5, 5, 20, 0, 5);
$mpdf->WriteHTML(contrato_compra_plantilla_body_pdf_pagina2($datos));
$mpdf->OutputHttpDownload('contrato_compra_ejemplo_sucursal_' . $id_sucursal . '.pdf');
exit;
