<?php
require_once __DIR__ . '/include/gemini.php';

header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(array('error' => 'Método no permitido'));
    exit;
}

$caras = array();
$frente = gemini_validar_archivo_subido('documento_frente');
$dorso = gemini_validar_archivo_subido('documento_dorso');
$legacy = gemini_validar_archivo_subido('documento');

if ($frente) {
    $caras[] = array(
        'ruta' => $frente['tmp_name'],
        'mime' => $frente['mime'],
        'cara' => 'frente',
    );
}

if ($dorso) {
    $caras[] = array(
        'ruta' => $dorso['tmp_name'],
        'mime' => $dorso['mime'],
        'cara' => 'dorso',
    );
}

if (empty($caras) && $legacy) {
    $caras[] = array(
        'ruta' => $legacy['tmp_name'],
        'mime' => $legacy['mime'],
        'cara' => 'unica',
    );
}

if (empty($caras)) {
    $hayArchivos = !empty($_FILES['documento_frente']['tmp_name'])
        || !empty($_FILES['documento_dorso']['tmp_name'])
        || !empty($_FILES['documento']['tmp_name']);

    http_response_code(400);
    echo json_encode(array(
        'error' => $hayArchivos
            ? 'Formato no válido. Usa JPG, PNG, WEBP o PDF.'
            : 'Sube al menos una cara del documento (frente o dorso).',
    ));
    exit;
}

$datos = leer_documento_identidad_caras($caras);

if ($datos === false) {
    http_response_code(500);
    $mensaje = gemini_ultimo_error();
    if ($mensaje === '') {
        $mensaje = 'No se pudo leer el documento';
    }
    echo json_encode(array('error' => $mensaje));
    exit;
}

echo json_encode(array('success' => true, 'datos' => $datos), JSON_UNESCAPED_UNICODE);
