<?php
/**
 * Descarga PDF del presupuesto (mismo contenido que documents/presupuesto_invoice.php).
 */
require_once __DIR__ . '/../include/session.php';
require_once __DIR__ . '/../include/functions.php';
require_once __DIR__ . '/../include/presupuesto_documento.php';
require_once __DIR__ . '/../vendor/autoload.php';

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($id <= 0) {
    header('HTTP/1.0 400 Bad Request');
    exit('ID no válido');
}

$conexion = conectar_bd();
$data = presupuesto_obtener_datos_documento($conexion, $id);
mysqli_close($conexion);

if (!$data) {
    header('HTTP/1.0 404 Not Found');
    exit('Presupuesto no encontrado');
}

$html = presupuesto_invoice_html_mpdf($data);
$numero = preg_replace('/[^a-zA-Z0-9._-]+/', '_', (string)($data['presupuesto']['numero'] ?? 'presupuesto'));

$mpdf = new \Mpdf\Mpdf(presupuesto_mpdf_config());

$mpdf->WriteHTML($html);
$mpdf->OutputHttpDownload('presupuesto_' . $numero . '.pdf');
