<?php
require_once __DIR__ . '/../../../include/gemini.php';

header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(array('error' => 'Método no permitido'));
    exit;
}

$archivos = array();
$principal = gemini_validar_archivo_factura_subido('factura_principal');
$adicional = gemini_validar_archivo_factura_subido('factura_adicional');
$legacy = gemini_validar_archivo_factura_subido('factura');

if ($principal) {
    $archivos[] = array(
        'ruta' => $principal['tmp_name'],
        'mime' => $principal['mime'],
        'etiqueta' => 'Documento principal',
    );
}

if ($adicional) {
    $archivos[] = array(
        'ruta' => $adicional['tmp_name'],
        'mime' => $adicional['mime'],
        'etiqueta' => 'Documento adicional',
    );
}

if (empty($archivos) && $legacy) {
    $archivos[] = array(
        'ruta' => $legacy['tmp_name'],
        'mime' => $legacy['mime'],
        'etiqueta' => 'Factura',
    );
}

if (empty($archivos)) {
    $hayArchivos = !empty($_FILES['factura_principal']['tmp_name'])
        || !empty($_FILES['factura_adicional']['tmp_name'])
        || !empty($_FILES['factura']['tmp_name']);

    http_response_code(400);
    echo json_encode(array(
        'error' => $hayArchivos
            ? 'Formato no válido. Usa PDF, Excel (XLS/XLSX), JPG o PNG.'
            : 'Sube al menos un archivo de factura.',
    ));
    exit;
}

$datos = leer_factura_proveedor_archivos($archivos);

if ($datos === false) {
    http_response_code(500);
    $mensaje = gemini_ultimo_error();
    if ($mensaje === '') {
        $mensaje = 'No se pudo leer la factura';
    }
    echo json_encode(array('error' => $mensaje));
    exit;
}

echo json_encode(array('success' => true, 'datos' => $datos), JSON_UNESCAPED_UNICODE);
