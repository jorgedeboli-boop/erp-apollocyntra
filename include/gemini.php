<?php
if (!defined('GEMINI_API_KEY')) {
    require_once __DIR__ . '/config.php';
}
if (!defined('GEMINI_API_KEY')) {
    $gemini_key_env = getenv('GEMINI_API_KEY');
    define('GEMINI_API_KEY', ($gemini_key_env !== false && $gemini_key_env !== '') ? $gemini_key_env : '');
}
if (!defined('GEMINI_MODEL')) {
    define('GEMINI_MODEL', 'gemini-2.5-flash');
}

$GLOBALS['gemini_ultimo_error'] = '';

function gemini_ultimo_error() {
    return isset($GLOBALS['gemini_ultimo_error']) ? (string) $GLOBALS['gemini_ultimo_error'] : '';
}

function gemini_establecer_error($mensaje) {
    $GLOBALS['gemini_ultimo_error'] = (string) $mensaje;
}

/**
 * Modelos de respaldo si no se puede consultar la API.
 */
function gemini_modelos_documento_fallback() {
    return array(
        'gemini-2.5-flash',
        'gemini-2.5-flash-lite',
        'gemini-2.0-flash-lite',
        'gemini-3-flash-preview',
    );
}

/**
 * Consulta modelos disponibles para generateContent en la API key actual.
 *
 * @return array<int, string>
 */
function gemini_listar_modelos_api() {
    static $cache = null;
    if (is_array($cache)) {
        return $cache;
    }

    $cache = array();

    if (!function_exists('curl_init') || !defined('GEMINI_API_KEY') || GEMINI_API_KEY === '') {
        return $cache;
    }

    $url = 'https://generativelanguage.googleapis.com/v1beta/models?key=' . GEMINI_API_KEY;
    $ch = curl_init($url);
    curl_setopt_array($ch, array(
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT => 20,
    ));

    $respuesta = curl_exec($ch);
    $httpCode = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
    if (PHP_VERSION_ID < 80000) {
        curl_close($ch);
    }

    if ($httpCode !== 200 || !$respuesta) {
        return $cache;
    }

    $data = json_decode($respuesta, true);
    if (!is_array($data) || empty($data['models'])) {
        return $cache;
    }

    foreach ($data['models'] as $modeloInfo) {
        $nombre = isset($modeloInfo['name']) ? str_replace('models/', '', $modeloInfo['name']) : '';
        $metodos = isset($modeloInfo['supportedGenerationMethods']) ? $modeloInfo['supportedGenerationMethods'] : array();

        if ($nombre === '' || !in_array('generateContent', $metodos, true)) {
            continue;
        }

        if (stripos($nombre, 'gemini') === false || stripos($nombre, 'embed') !== false) {
            continue;
        }

        $cache[] = $nombre;
    }

    return $cache;
}

/**
 * Modelos para OCR/documentos, en orden de preferencia.
 */
function gemini_modelos_documento() {
    $preferidos = array();
    $modeloConfigurado = defined('GEMINI_MODEL') ? trim((string) GEMINI_MODEL) : '';
    if ($modeloConfigurado !== '') {
        $preferidos[] = $modeloConfigurado;
    }

    $preferidos = array_merge($preferidos, gemini_modelos_documento_fallback());

    $disponiblesApi = gemini_listar_modelos_api();
    if (!empty($disponiblesApi)) {
        $ordenados = array();
        foreach ($preferidos as $modelo) {
            if (in_array($modelo, $disponiblesApi, true)) {
                $ordenados[] = $modelo;
            }
        }
        foreach ($disponiblesApi as $modelo) {
            if (stripos($modelo, 'flash') === false) {
                continue;
            }
            if (!in_array($modelo, $ordenados, true)) {
                $ordenados[] = $modelo;
            }
        }
        return $ordenados;
    }

    return array_values(array_unique(array_filter($preferidos)));
}

function gemini_es_error_modelo_no_disponible($httpCode, $detalle) {
    if (in_array($httpCode, array(403, 404), true)) {
        return true;
    }

    $texto = strtolower((string) $detalle);
    return strpos($texto, 'not found') !== false
        || strpos($texto, 'is not found') !== false
        || strpos($texto, 'not supported') !== false;
}

function gemini_mime_permitidos() {
    return array(
        'jpg' => 'image/jpeg',
        'jpeg' => 'image/jpeg',
        'png' => 'image/png',
        'webp' => 'image/webp',
        'pdf' => 'application/pdf',
    );
}

/**
 * Formatos admitidos para facturas de proveedor (PDF, Excel, imágenes).
 *
 * @return array<string, string>
 */
function gemini_mime_permitidos_factura() {
    return array_merge(gemini_mime_permitidos(), array(
        'xlsx' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        'xls' => 'application/vnd.ms-excel',
    ));
}

function gemini_detectar_mime_por_lista($rutaImagen, $mapaExtensionMime, $mimeType = null) {
    $mimeTypes = array_values($mapaExtensionMime);

    if (!empty($mimeType) && in_array($mimeType, $mimeTypes, true)) {
        return $mimeType;
    }

    $extension = strtolower((string) pathinfo($rutaImagen, PATHINFO_EXTENSION));
    if ($extension !== '' && isset($mapaExtensionMime[$extension])) {
        return $mapaExtensionMime[$extension];
    }

    if (function_exists('mime_content_type')) {
        $mimeDetectado = @mime_content_type($rutaImagen);
        if ($mimeDetectado && in_array($mimeDetectado, $mimeTypes, true)) {
            return $mimeDetectado;
        }
    }

    if (class_exists('finfo')) {
        $finfo = new finfo(FILEINFO_MIME_TYPE);
        $mimeDetectado = $finfo->file($rutaImagen);
        if ($mimeDetectado && in_array($mimeDetectado, $mimeTypes, true)) {
            return $mimeDetectado;
        }
    }

    return null;
}

function gemini_detectar_mime($rutaImagen, $mimeType = null) {
    return gemini_detectar_mime_por_lista($rutaImagen, gemini_mime_permitidos(), $mimeType);
}

function gemini_detectar_mime_factura($rutaArchivo, $mimeType = null) {
    return gemini_detectar_mime_por_lista($rutaArchivo, gemini_mime_permitidos_factura(), $mimeType);
}

/**
 * Valida un archivo subido por $_FILES y devuelve ruta temporal y mime.
 *
 * @return array{tmp_name:string,mime:string}|false
 */
function gemini_validar_archivo_subido($campo) {
    if (empty($_FILES[$campo]['tmp_name']) || !is_uploaded_file($_FILES[$campo]['tmp_name'])) {
        return false;
    }

    $mime = gemini_detectar_mime($_FILES[$campo]['tmp_name']);
    if (function_exists('mime_content_type')) {
        $mimeSubido = @mime_content_type($_FILES[$campo]['tmp_name']);
        $permitidos = array_values(gemini_mime_permitidos());
        if ($mimeSubido && in_array($mimeSubido, $permitidos, true)) {
            $mime = $mimeSubido;
        }
    }

    $permitidos = array_values(gemini_mime_permitidos());
    if (!$mime || !in_array($mime, $permitidos, true)) {
        return false;
    }

    return array(
        'tmp_name' => $_FILES[$campo]['tmp_name'],
        'mime' => $mime,
    );
}

/**
 * Valida un archivo de factura subido (PDF, Excel, JPG, PNG).
 *
 * @return array{tmp_name:string,mime:string}|false
 */
function gemini_validar_archivo_factura_subido($campo) {
    if (empty($_FILES[$campo]['tmp_name']) || !is_uploaded_file($_FILES[$campo]['tmp_name'])) {
        return false;
    }

    $mime = gemini_detectar_mime_factura($_FILES[$campo]['tmp_name']);
    if (function_exists('mime_content_type')) {
        $mimeSubido = @mime_content_type($_FILES[$campo]['tmp_name']);
        $permitidos = array_values(gemini_mime_permitidos_factura());
        if ($mimeSubido && in_array($mimeSubido, $permitidos, true)) {
            $mime = $mimeSubido;
        }
    }

    $permitidos = array_values(gemini_mime_permitidos_factura());
    if (!$mime || !in_array($mime, $permitidos, true)) {
        return false;
    }

    return array(
        'tmp_name' => $_FILES[$campo]['tmp_name'],
        'mime' => $mime,
    );
}

function gemini_es_error_cuota($httpCode, $detalle) {
    if ($httpCode === 429) {
        return true;
    }

    $texto = strtolower((string) $detalle);
    return strpos($texto, 'quota') !== false
        || strpos($texto, 'resource_exhausted') !== false
        || strpos($texto, 'limit: 0') !== false;
}

/**
 * Indica si un pasaporte es claramente de un país distinto de España.
 *
 * @param array<string, mixed> $documento
 * @return bool
 */
function gemini_documento_es_pasaporte_extranjero(array $documento) {
    $paisEmisor = strtoupper(trim((string) ($documento['pais_emisor'] ?? '')));
    $textosEspana = array('ESP', 'ESPAÑA', 'ESPANA', 'SPAIN', 'REINO DE ESPAÑA', 'KINGDOM OF SPAIN');

    if ($paisEmisor !== '') {
        foreach ($textosEspana as $textoEspana) {
            if (strpos($paisEmisor, $textoEspana) !== false) {
                return false;
            }
        }
        return true;
    }

    $mrz = strtoupper((string) ($documento['mrz'] ?? ''));
    if ($mrz !== '' && preg_match('/P<([A-Z]{3})/', $mrz, $coincidencia)) {
        return $coincidencia[1] !== 'ESP';
    }

    return false;
}

/**
 * Corrige clasificaciones erróneas del modelo (p. ej. permiso de residencia como OTRO).
 *
 * @param array<string, mixed> $documento
 * @return array<string, mixed>
 */
function gemini_normalizar_documento_identidad(array $documento) {
    $tipo = strtoupper(trim((string) ($documento['tipo_documento'] ?? '')));
    $numero = strtoupper(preg_replace('/[\s.\-]/', '', (string) ($documento['numero_documento'] ?? '')));

    $esNiePorNumero = (bool) preg_match('/^[XYZ]\d{7}[A-Z]$/', $numero);
    $esDniPorNumero = (bool) preg_match('/^\d{8}[A-Z]$/', $numero);

    $textosNie = array(
        'NIE',
        'TIE',
        'TARJETA DE IDENTIDAD DE EXTRANJERO',
        'TARJETA DE RESIDENCIA',
        'PERMISO DE RESIDENCIA',
        'PERMISO RESIDENCIA',
        'TARJETA EXTRANJERO',
        'IDENTIDAD DE EXTRANJERO',
        'DOCUMENTO DE EXTRANJERO',
        'EXTRANJERIA',
        'EXTRANJERÍA',
    );

    foreach ($textosNie as $textoNie) {
        if ($tipo !== '' && strpos($tipo, $textoNie) !== false) {
            $tipo = 'NIE';
            break;
        }
    }

    if ($tipo === 'OTRO' || $tipo === '') {
        if ($esNiePorNumero) {
            $tipo = 'NIE';
        } elseif ($esDniPorNumero) {
            $tipo = 'DNI';
        }
    }

    if ($tipo === 'NIE' && $numero !== '' && !$esNiePorNumero && $esDniPorNumero) {
        $tipo = 'DNI';
    }

    if ($tipo === 'PASAPORTE' && gemini_documento_es_pasaporte_extranjero($documento)) {
        $tipo = 'OTRO';
    }

    if ($tipo !== '') {
        $documento['tipo_documento'] = $tipo;
    }

    $fechaExpedicion = trim((string) ($documento['fecha_expedicion'] ?? ''));
    if ($fechaExpedicion === '') {
        foreach (array('fecha_emision', 'fecha_de_emision', 'fecha_de_expedicion') as $campoFecha) {
            $fechaAlternativa = trim((string) ($documento[$campoFecha] ?? ''));
            if ($fechaAlternativa !== '') {
                $documento['fecha_expedicion'] = $fechaAlternativa;
                break;
            }
        }
    }

    foreach (array('direccion', 'poblacion', 'pais_residencia', 'provincia', 'codigo_postal') as $campo) {
        if (!isset($documento[$campo])) {
            $documento[$campo] = '';
        }
    }

    $sexo = strtoupper(trim((string) ($documento['sexo'] ?? '')));
    $sexoTexto = strtoupper(trim((string) ($documento['sexo_texto'] ?? '')));

    if ($sexoTexto === '' && preg_match('/MASCULINO|FEMENINO/', $sexo)) {
        $sexoTexto = $sexo;
        $sexo = '';
    }

    if (in_array($sexo, array('M', 'H', 'MALE', 'V'), true) || strpos($sexoTexto, 'MASCUL') !== false) {
        $sexo = 'M';
        $sexoTexto = 'MASCULINO';
    } elseif (in_array($sexo, array('F', 'FEMALE'), true) || strpos($sexoTexto, 'FEMEN') !== false) {
        $sexo = 'F';
        $sexoTexto = 'FEMENINO';
    } elseif ($sexoTexto === 'MASCULINO') {
        $sexo = 'M';
    } elseif ($sexoTexto === 'FEMENINO') {
        $sexo = 'F';
    } else {
        $sexo = '';
        $sexoTexto = '';
    }

    $documento['sexo'] = $sexo;
    $documento['sexo_texto'] = $sexoTexto;

    $paisResidencia = trim((string) $documento['pais_residencia']);
    if ($paisResidencia === '' && trim((string) $documento['provincia']) !== '') {
        $paisResidencia = 'España';
    }
    $documento['pais_residencia'] = $paisResidencia;

    $codigoNacionalidad = strtoupper(trim((string) ($documento['nacionalidad_codigo'] ?? '')));
    $nombreNacionalidad = trim((string) ($documento['nacionalidad'] ?? ''));
    $nombreAlternativo = trim((string) ($documento['nacionalidad_nombre'] ?? ''));

    if ($nombreNacionalidad === '' && $nombreAlternativo !== '') {
        $nombreNacionalidad = $nombreAlternativo;
    }

    if ($codigoNacionalidad === '' && $nombreNacionalidad !== '') {
        if (preg_match('/^([A-Z]{3})\s*[-–—\/]\s*(.+)$/u', strtoupper($nombreNacionalidad), $coincidencia)) {
            $codigoNacionalidad = $coincidencia[1];
            $nombreNacionalidad = trim($coincidencia[2]);
        } elseif (preg_match('/^[A-Z]{3}$/', strtoupper($nombreNacionalidad))) {
            $codigoNacionalidad = strtoupper($nombreNacionalidad);
            $nombreNacionalidad = '';
        }
    }

    if ($codigoNacionalidad !== '' && !preg_match('/^[A-Z]{3}$/', $codigoNacionalidad)) {
        $codigoNacionalidad = '';
    }

    $tipoDocumento = strtoupper(trim((string) ($documento['tipo_documento'] ?? '')));
    if ($tipoDocumento === 'DNI') {
        if ($codigoNacionalidad === '') {
            $codigoNacionalidad = 'ESP';
        }
        if ($nombreNacionalidad === '') {
            $nombreNacionalidad = 'Española';
        }
    }

    $documento['nacionalidad_codigo'] = $codigoNacionalidad;
    $documento['nacionalidad'] = $nombreNacionalidad;
    unset($documento['nacionalidad_nombre']);

    return $documento;
}

function gemini_mensaje_error_amigable($httpCode, $detalle, $modelo) {
    if (gemini_es_error_cuota($httpCode, $detalle)) {
        return 'Sin cuota disponible en Gemini para el modelo ' . $modelo . '. '
            . 'En Google AI Studio revisa la API key, activa facturación si hace falta, o usa un modelo con cuota gratuita (p. ej. gemini-2.5-flash).';
    }

    if ($httpCode === 403) {
        return 'La API key de Gemini no tiene permiso para usar el modelo ' . $modelo . '.';
    }

    if ($httpCode === 404) {
        return 'El modelo ' . $modelo . ' no está disponible para tu API key.';
    }

    $detalle = trim(preg_replace('/\s+/', ' ', (string) $detalle));
    if (strlen($detalle) > 220) {
        $detalle = substr($detalle, 0, 220) . '…';
    }

    return 'Gemini respondió con HTTP ' . $httpCode . ($detalle ? ': ' . $detalle : '');
}

/**
 * @param string $modelo
 * @param array $payload
 * @return array{ok:bool,http_code:int,detalle:string,data:array|null}
 */
function gemini_llamar_api($modelo, array $payload) {
    if (!function_exists('curl_init')) {
        return array(
            'ok' => false,
            'http_code' => 0,
            'detalle' => 'La extensión cURL no está disponible en el servidor',
            'data' => null,
        );
    }

    $url = 'https://generativelanguage.googleapis.com/v1beta/models/' . rawurlencode($modelo) . ':generateContent?key=' . GEMINI_API_KEY;

    $ch = curl_init($url);
    curl_setopt_array($ch, array(
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST => true,
        CURLOPT_HTTPHEADER => array('Content-Type: application/json'),
        CURLOPT_POSTFIELDS => json_encode($payload),
        CURLOPT_TIMEOUT => 90,
    ));

    $respuesta = curl_exec($ch);
    $httpCode = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curlError = curl_error($ch);
    if (PHP_VERSION_ID < 80000) {
        curl_close($ch);
    }

    if ($respuesta === false) {
        return array(
            'ok' => false,
            'http_code' => 0,
            'detalle' => 'Error de conexión con Gemini: ' . $curlError,
            'data' => null,
        );
    }

    if ($httpCode !== 200) {
        $detalle = $respuesta;
        $jsonError = json_decode($respuesta, true);
        if (is_array($jsonError) && !empty($jsonError['error']['message'])) {
            $detalle = $jsonError['error']['message'];
        }

        return array(
            'ok' => false,
            'http_code' => $httpCode,
            'detalle' => $detalle,
            'data' => null,
        );
    }

    $data = json_decode($respuesta, true);
    if (!is_array($data)) {
        return array(
            'ok' => false,
            'http_code' => $httpCode,
            'detalle' => 'Respuesta inválida de Gemini',
            'data' => null,
        );
    }

    return array(
        'ok' => true,
        'http_code' => $httpCode,
        'detalle' => '',
        'data' => $data,
    );
}

/**
 * Prompt de extracción según número de caras del documento.
 *
 * @param int $numCaras
 * @return string
 */
function gemini_prompt_documento_identidad($numCaras = 1) {
    $intro = 'Analiza este documento de identidad español (DNI, NIE o pasaporte español) u otro documento de identidad.';
    if ($numCaras > 1) {
        $intro = 'Recibirás dos imágenes del mismo documento de identidad: anverso (frente) y reverso (dorso). '
            . 'Combina toda la información visible de ambas caras en un único JSON. '
            . 'La dirección, el MRZ y otros datos suelen estar en el dorso del DNI o NIE/TIE.';
    }

    return $intro . '
Extrae los datos y responde SOLO con un JSON válido, sin markdown, sin backticks, sin explicaciones, con esta estructura exacta:
{
  "tipo_documento": "DNI|NIE|PASAPORTE|OTRO",
  "numero_documento": "",
  "nombre": "",
  "apellido1": "",
  "apellido2": "",
  "fecha_nacimiento": "YYYY-MM-DD",
  "nacionalidad_codigo": "",
  "nacionalidad": "",
  "sexo": "M|F",
  "sexo_texto": "MASCULINO|FEMENINO",
  "fecha_expedicion": "YYYY-MM-DD",
  "fecha_caducidad": "YYYY-MM-DD",
  "direccion": "",
  "poblacion": "",
  "pais_residencia": "",
  "provincia": "",
  "codigo_postal": "",
  "pais_emisor": "",
  "mrz": ""
}
Reglas para tipo_documento:
- DNI: documento nacional de identidad español (8 dígitos + letra).
- NIE: número de identidad de extranjero en España. Incluye tarjeta de identidad de extranjero (TIE), permiso de residencia, tarjeta de residencia y cualquier documento español de extranjería cuyo número empiece por X, Y o Z seguido de 7 dígitos y una letra (ej. X1234567L). Aunque el documento diga "permiso de residencia" o "TIE", clasifícalo siempre como NIE, nunca como OTRO.
- PASAPORTE: solo pasaporte español emitido por España (país emisor España/ESP). En el MRZ suele aparecer P<ESP.
- OTRO: cualquier otro documento, incluidos pasaportes de otros países, carnets de conducir, permisos no españoles, etc.
Si un campo no es visible o no existe, déjalo como cadena vacía.
En nacionalidad_codigo pon el código ISO 3166-1 alfa-3 de 3 letras que figure en el documento o en el MRZ (ej. ESP, COL, FRA, MAR).
En nacionalidad pon el nombre completo de la nacionalidad en español (ej. Española, Colombiana, Francesa, Marroquí).
Si el documento solo muestra el código, deduce el nombre completo en español. Si solo muestra el nombre, deduce el código ISO de 3 letras.
En sexo pon la letra del documento o MRZ (M o F). En sexo_texto pon MASCULINO o FEMENINO según corresponda.
En fecha_expedicion usa la fecha de expedición o de emisión del documento si aparece con cualquiera de esos nombres.
En direccion, poblacion, pais_residencia, provincia y codigo_postal extrae el domicilio o dirección si figura en el documento (p. ej. en NIE/TIE o reverso del DNI).
En pais_residencia pon el país donde reside la persona según el domicilio del documento (ej. España). Si el domicilio es español y no se indica el país, usa España.
En documentos clasificados como OTRO (p. ej. pasaporte extranjero) pon todos los apellidos en apellido1 si aplica.
En numero_documento incluye el NIE/DNI completo con letra de control si se ve.
En mrz incluye las líneas de la zona de lectura mecánica tal cual, separadas por \n, si existen.';
}

/**
 * Lee un documento de identidad con una o dos caras (frente y/o dorso).
 *
 * Cada cara: array con claves "ruta", "mime" (opcional) y "cara" (frente|dorso|unica, opcional).
 *
 * @param array<int, array<string, mixed>> $caras
 * @return array|false
 */
function leer_documento_identidad_caras(array $caras) {
    gemini_establecer_error('');

    $archivos = array();
    foreach ($caras as $cara) {
        $ruta = isset($cara['ruta']) ? (string) $cara['ruta'] : '';
        if ($ruta === '' || !file_exists($ruta)) {
            continue;
        }

        $mime = gemini_detectar_mime($ruta, isset($cara['mime']) ? $cara['mime'] : null);
        if (!$mime) {
            gemini_establecer_error('Formato de archivo no soportado');
            return false;
        }

        $contenido = file_get_contents($ruta);
        if ($contenido === false || $contenido === '') {
            gemini_establecer_error('No se pudo leer el contenido del archivo');
            return false;
        }

        $archivos[] = array(
            'mime' => $mime,
            'data' => base64_encode($contenido),
            'cara' => isset($cara['cara']) ? (string) $cara['cara'] : 'unica',
        );
    }

    if (empty($archivos)) {
        gemini_establecer_error('No se recibió ninguna imagen del documento');
        return false;
    }

    $etiquetasCara = array(
        'frente' => 'Anverso (frente)',
        'dorso' => 'Reverso (dorso)',
    );

    $parts = array(
        array('text' => gemini_prompt_documento_identidad(count($archivos))),
    );

    foreach ($archivos as $archivo) {
        if (count($archivos) > 1) {
            $etiqueta = isset($etiquetasCara[$archivo['cara']]) ? $etiquetasCara[$archivo['cara']] : 'Cara del documento';
            $parts[] = array('text' => $etiqueta . ':');
        }
        $parts[] = array(
            'inline_data' => array(
                'mime_type' => $archivo['mime'],
                'data' => $archivo['data'],
            ),
        );
    }

    $payload = array(
        'contents' => array(
            array(
                'parts' => $parts,
            ),
        ),
        'generationConfig' => array(
            'temperature' => 0,
            'responseMimeType' => 'application/json',
        ),
    );

    $ultimoError = '';
    $erroresModelos = array();

    foreach (gemini_modelos_documento() as $modelo) {
        $resultado = gemini_llamar_api($modelo, $payload);

        if (!$resultado['ok']) {
            $mensaje = gemini_mensaje_error_amigable($resultado['http_code'], $resultado['detalle'], $modelo);
            $ultimoError = $mensaje;
            $erroresModelos[] = $modelo . ': ' . $mensaje;
            error_log('Gemini OCR fallo con ' . $modelo . ' HTTP ' . $resultado['http_code'] . ': ' . substr($resultado['detalle'], 0, 300));

            if (gemini_es_error_cuota($resultado['http_code'], $resultado['detalle'])
                || gemini_es_error_modelo_no_disponible($resultado['http_code'], $resultado['detalle'])) {
                continue;
            }

            gemini_establecer_error($ultimoError);
            return false;
        }

        $data = $resultado['data'];
        if (empty($data['candidates'][0]['content']['parts'][0]['text'])) {
            $motivo = '';
            if (!empty($data['promptFeedback']['blockReason'])) {
                $motivo = $data['promptFeedback']['blockReason'];
            }
            $ultimoError = 'Gemini (' . $modelo . ') no devolvió contenido' . ($motivo ? ': ' . $motivo : '');
            continue;
        }

        $textoJson = $data['candidates'][0]['content']['parts'][0]['text'];
        $textoJson = preg_replace('/```json|```/', '', $textoJson);

        $documento = json_decode(trim($textoJson), true);
        if (!is_array($documento)) {
            $ultimoError = 'No se pudo interpretar el JSON devuelto por Gemini (' . $modelo . ')';
            continue;
        }

        return gemini_normalizar_documento_identidad($documento);
    }

    if ($ultimoError === '') {
        $ultimoError = 'No se pudo leer el documento con ningún modelo de Gemini disponible.';
    }

    if (count($erroresModelos) > 1) {
        $ultimoError = 'Ningún modelo de Gemini pudo procesar el documento. '
            . 'Revisa la API key y la cuota en Google AI Studio. '
            . 'Detalle: ' . $erroresModelos[count($erroresModelos) - 1];
    }

    gemini_establecer_error($ultimoError);
    return false;
}

/**
 * Lee un documento de identidad (DNI, NIE, pasaporte) y devuelve los datos en array
 *
 * @param string $rutaImagen Ruta al archivo de imagen (jpg, png) o PDF
 * @param string|null $mimeType Mime type opcional (necesario con archivos temporales sin extensión)
 * @return array|false
 */
function leer_documento_identidad($rutaImagen, $mimeType = null) {
    return leer_documento_identidad_caras(array(
        array(
            'ruta' => $rutaImagen,
            'mime' => $mimeType,
            'cara' => 'unica',
        ),
    ));
}

/**
 * Prompt genérico para facturas de proveedor.
 *
 * @param int $numArchivos
 * @return string
 */
function gemini_prompt_factura_proveedor($numArchivos = 1) {
    $intro = 'Analiza este documento de factura o albarán de proveedor (puede ser PDF, Excel o imagen).';
    if ($numArchivos > 1) {
        $intro = 'Recibirás varios archivos o páginas del mismo documento de factura de proveedor. '
            . 'Combina toda la información visible en un único JSON.';
    }

    return $intro . '
Extrae los datos y responde SOLO con un JSON válido, sin markdown, sin backticks, sin explicaciones, con esta estructura exacta:
{
  "tipo_documento": "FACTURA|ALBARAN|PROFORMA|OTRO",
  "numero_factura": "",
  "fecha_factura": "YYYY-MM-DD",
  "fecha_vencimiento": "YYYY-MM-DD",
  "proveedor_nombre": "",
  "proveedor_cif": "",
  "proveedor_direccion": "",
  "proveedor_poblacion": "",
  "proveedor_provincia": "",
  "proveedor_codigo_postal": "",
  "proveedor_pais": "",
  "cliente_nombre": "",
  "cliente_cif": "",
  "base_imponible": "",
  "tipo_iva": "",
  "importe_iva": "",
  "total": "",
  "moneda": "EUR",
  "forma_pago": "",
  "concepto": "",
  "observaciones": "",
  "lineas": []
}
Reglas:
- tipo_documento FACTURA si es factura, ALBARAN si es albarán de entrega, PROFORMA si es proforma, OTRO si no encaja.
- En lineas incluye un array de objetos con: descripcion, cantidad, precio_unitario, importe, iva_porcentaje (cadena vacía o número si no consta).
- Importes numéricos como cadena con punto decimal (ej. "1210.50"), sin símbolo de moneda.
- Si un campo no es visible, déjalo como cadena vacía. lineas puede ser [].
- proveedor_* corresponde al emisor de la factura; cliente_* al receptor.';
}

/**
 * @param array<string, mixed> $factura
 * @return array<string, mixed>
 */
function gemini_normalizar_factura_proveedor(array $factura) {
    $tipo = strtoupper(trim((string) ($factura['tipo_documento'] ?? '')));
    $tiposValidos = array('FACTURA', 'ALBARAN', 'PROFORMA', 'OTRO');
    if (!in_array($tipo, $tiposValidos, true)) {
        $tipo = 'OTRO';
    }
    $factura['tipo_documento'] = $tipo;

    foreach (array(
        'numero_factura', 'fecha_factura', 'fecha_vencimiento',
        'proveedor_nombre', 'proveedor_cif', 'proveedor_direccion',
        'proveedor_poblacion', 'proveedor_provincia', 'proveedor_codigo_postal', 'proveedor_pais',
        'cliente_nombre', 'cliente_cif',
        'base_imponible', 'tipo_iva', 'importe_iva', 'total',
        'moneda', 'forma_pago', 'concepto', 'observaciones',
    ) as $campo) {
        if (!isset($factura[$campo])) {
            $factura[$campo] = '';
        }
    }

    if (trim((string) $factura['moneda']) === '') {
        $factura['moneda'] = 'EUR';
    }

    if (!isset($factura['lineas']) || !is_array($factura['lineas'])) {
        $factura['lineas'] = array();
    }

    $fechaFactura = trim((string) $factura['fecha_factura']);
    if ($fechaFactura === '') {
        foreach (array('fecha_emision', 'fecha', 'fecha_documento') as $campoFecha) {
            $alternativa = trim((string) ($factura[$campoFecha] ?? ''));
            if ($alternativa !== '') {
                $factura['fecha_factura'] = $alternativa;
                break;
            }
        }
    }

    return $factura;
}

/**
 * Lee factura(s) de proveedor desde uno o más archivos (PDF, Excel, imagen).
 *
 * @param array<int, array<string, mixed>> $archivos
 * @return array|false
 */
function leer_factura_proveedor_archivos(array $archivos) {
    gemini_establecer_error('');

    $partes = array();
    foreach ($archivos as $archivoInfo) {
        $ruta = isset($archivoInfo['ruta']) ? (string) $archivoInfo['ruta'] : '';
        if ($ruta === '' || !file_exists($ruta)) {
            continue;
        }

        $mime = gemini_detectar_mime_factura($ruta, isset($archivoInfo['mime']) ? $archivoInfo['mime'] : null);
        if (!$mime) {
            gemini_establecer_error('Formato de archivo no soportado. Usa PDF, Excel, JPG o PNG.');
            return false;
        }

        $contenido = file_get_contents($ruta);
        if ($contenido === false || $contenido === '') {
            gemini_establecer_error('No se pudo leer el contenido del archivo');
            return false;
        }

        $partes[] = array(
            'mime' => $mime,
            'data' => base64_encode($contenido),
            'etiqueta' => isset($archivoInfo['etiqueta']) ? (string) $archivoInfo['etiqueta'] : 'Archivo',
        );
    }

    if (empty($partes)) {
        gemini_establecer_error('No se recibió ningún archivo de factura');
        return false;
    }

    $payloadParts = array(
        array('text' => gemini_prompt_factura_proveedor(count($partes))),
    );

    foreach ($partes as $parte) {
        if (count($partes) > 1) {
            $payloadParts[] = array('text' => $parte['etiqueta'] . ':');
        }
        $payloadParts[] = array(
            'inline_data' => array(
                'mime_type' => $parte['mime'],
                'data' => $parte['data'],
            ),
        );
    }

    $payload = array(
        'contents' => array(
            array(
                'parts' => $payloadParts,
            ),
        ),
        'generationConfig' => array(
            'temperature' => 0,
            'responseMimeType' => 'application/json',
        ),
    );

    $ultimoError = '';
    $erroresModelos = array();

    foreach (gemini_modelos_documento() as $modelo) {
        $resultado = gemini_llamar_api($modelo, $payload);

        if (!$resultado['ok']) {
            $mensaje = gemini_mensaje_error_amigable($resultado['http_code'], $resultado['detalle'], $modelo);
            $ultimoError = $mensaje;
            $erroresModelos[] = $modelo . ': ' . $mensaje;
            error_log('Gemini factura fallo con ' . $modelo . ' HTTP ' . $resultado['http_code'] . ': ' . substr($resultado['detalle'], 0, 300));

            if (gemini_es_error_cuota($resultado['http_code'], $resultado['detalle'])
                || gemini_es_error_modelo_no_disponible($resultado['http_code'], $resultado['detalle'])) {
                continue;
            }

            gemini_establecer_error($ultimoError);
            return false;
        }

        $data = $resultado['data'];
        if (empty($data['candidates'][0]['content']['parts'][0]['text'])) {
            $motivo = '';
            if (!empty($data['promptFeedback']['blockReason'])) {
                $motivo = $data['promptFeedback']['blockReason'];
            }
            $ultimoError = 'Gemini (' . $modelo . ') no devolvió contenido' . ($motivo ? ': ' . $motivo : '');
            continue;
        }

        $textoJson = $data['candidates'][0]['content']['parts'][0]['text'];
        $textoJson = preg_replace('/```json|```/', '', $textoJson);

        $factura = json_decode(trim($textoJson), true);
        if (!is_array($factura)) {
            $ultimoError = 'No se pudo interpretar el JSON devuelto por Gemini (' . $modelo . ')';
            continue;
        }

        return gemini_normalizar_factura_proveedor($factura);
    }

    if ($ultimoError === '') {
        $ultimoError = 'No se pudo leer la factura con ningún modelo de Gemini disponible.';
    }

    if (count($erroresModelos) > 1) {
        $ultimoError = 'Ningún modelo de Gemini pudo procesar la factura. '
            . 'Revisa la API key y la cuota en Google AI Studio. '
            . 'Detalle: ' . $erroresModelos[count($erroresModelos) - 1];
    }

    gemini_establecer_error($ultimoError);
    return false;
}

/**
 * @param string $rutaArchivo
 * @param string|null $mimeType
 * @return array|false
 */
function leer_factura_proveedor($rutaArchivo, $mimeType = null) {
    return leer_factura_proveedor_archivos(array(
        array(
            'ruta' => $rutaArchivo,
            'mime' => $mimeType,
            'etiqueta' => 'Factura',
        ),
    ));
}
