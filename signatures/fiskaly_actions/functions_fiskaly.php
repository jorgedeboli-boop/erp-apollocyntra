<?php

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

?>
