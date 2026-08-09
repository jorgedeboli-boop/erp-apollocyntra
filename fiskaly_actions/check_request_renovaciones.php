<?php
// RECIBIR Y COMPROBAR PARAMETROS

require_once __DIR__ . '/../include/config.php';

// Dominio permitido (desde config.php)
$dominio_permitido = rtrim($url, '/');

// Variables requeridas
$variables_requeridas = array(
    'id_renovaciones',
    'factura_id_fiskaly',
    'id_lote',
    'sucursal_renovacion',
    'id_empresa'
);

// Función para obtener el dominio origen
function obtener_dominio_origen() {
    // Intentar obtener desde HTTP_ORIGIN (más seguro para CORS)
    if (!empty($_SERVER['HTTP_ORIGIN'])) {
        return $_SERVER['HTTP_ORIGIN'];
    }
    
    // Si no está disponible, intentar desde HTTP_REFERER
    if (!empty($_SERVER['HTTP_REFERER'])) {
        $referer = parse_url($_SERVER['HTTP_REFERER'], PHP_URL_SCHEME) . '://' . parse_url($_SERVER['HTTP_REFERER'], PHP_URL_HOST);
        if (parse_url($_SERVER['HTTP_REFERER'], PHP_URL_PORT)) {
            $referer .= ':' . parse_url($_SERVER['HTTP_REFERER'], PHP_URL_PORT);
        }
        return $referer;
    }
    
    return null;
}

// Función para validar el dominio origen
function validar_dominio_origen($dominio_permitido) {
    $dominio_origen = obtener_dominio_origen();
    
    if ($dominio_origen === null) {
        return array(
            'valido' => false,
            'error' => 'No se pudo determinar el dominio origen de la petición. Verifique que HTTP_ORIGIN o HTTP_REFERER estén presentes.'
        );
    }
    
    // Normalizar dominios (sin trailing slash)
    $dominio_permitido = rtrim($dominio_permitido, '/');
    $dominio_origen = rtrim($dominio_origen, '/');
    
    if ($dominio_origen !== $dominio_permitido) {
        return array(
            'valido' => false,
            'error' => 'Dominio origen no autorizado. Dominio recibido: ' . htmlspecialchars($dominio_origen) . '. Dominio esperado: ' . htmlspecialchars($dominio_permitido)
        );
    }
    
    return array('valido' => true);
}

// Función para validar variables requeridas
function validar_variables($variables_requeridas) {
    $variables_faltantes = array();
    
    foreach ($variables_requeridas as $variable) {
        if (!isset($_GET[$variable]) || empty(trim($_GET[$variable]))) {
            $variables_faltantes[] = $variable;
        }
    }
    
    if (!empty($variables_faltantes)) {
        return array(
            'valido' => false,
            'error' => 'Variables requeridas faltantes: ' . implode(', ', $variables_faltantes)
        );
    }
    
    return array('valido' => true);
}

// Obtener URL completa desde donde viene la petición lote.php?categoria=lotes&page=lotes&lote=461
$url_completa_origen = $dominio_permitido."/lote.php?categoria=lotes&page=lotes&lote=".$_GET['id_lote'];

// Validar dominio origen
$validacion_dominio = validar_dominio_origen($dominio_permitido);
if (!$validacion_dominio['valido']) {
    if ($url_completa_origen) {
        header('Location: ' . $url_completa_origen.'&state=error&text_error='.urlencode($validacion_dominio['error']));
    } else {
        http_response_code(403);
        die('Error: ' . $validacion_dominio['error']);
    }
    exit;
}

// Validar variables requeridas (si se han definido)
if (!empty($variables_requeridas)) {
    $validacion_variables = validar_variables($variables_requeridas);
    if (!$validacion_variables['valido']) {
        if ($url_completa_origen) {
            header('Location: ' . $url_completa_origen.'&state=error&text_error='.urlencode($validacion_variables['error']));
        } else {
            http_response_code(400);
            die('Error: ' . $validacion_variables['error']);
        }
        exit;
    }
}

// Si llegamos aquí, todas las validaciones pasaron
// La variable $url_completa_origen contiene la URL completa desde donde viene la petición

?>
