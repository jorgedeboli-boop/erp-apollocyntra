<?php
/**
 * Helpers: mapeo de direcciones.c_provincia → provincias (directo + IA).
 */

require_once __DIR__ . '/../../../include/ia_claude.php';

function migracion_provincias_trim($valor)
{
    return trim((string) $valor);
}

/**
 * Formato como en provincias.nombreProvince: minúsculas con inicial en mayúscula por palabra.
 * Ej.: VIZCAYA → Vizcaya, LA RIOJA → La Rioja
 */
function migracion_provincias_formato_titulo($valor)
{
    $t = migracion_provincias_trim($valor);
    if ($t === '') {
        return '';
    }

    $t = preg_replace('/\s+/u', ' ', $t);

    if (class_exists('Normalizer')) {
        $n = Normalizer::normalize($t, Normalizer::FORM_C);
        if ($n !== false) {
            $t = $n;
        }
    }

    return mb_convert_case(mb_strtolower($t, 'UTF-8'), MB_CASE_TITLE, 'UTF-8');
}

function migracion_provincias_clave($texto)
{
    $t = migracion_provincias_trim($texto);
    if ($t === '') {
        return '';
    }
    if (class_exists('Normalizer')) {
        $n = Normalizer::normalize($t, Normalizer::FORM_C);
        if ($n !== false) {
            $t = $n;
        }
    }
    return mb_strtolower($t, 'UTF-8');
}

/**
 * @param mysqli $mysqli
 * @param int    $idPais
 * @return array clave normalizada => array{id:int,nombre:string}
 */
function migracion_cargar_mapa_provincias($mysqli, $idPais = 68)
{
    $mapa = array();
    $idPais = (int) $idPais;

    $sql = 'SELECT id_province, nombreProvince FROM provincias WHERE id_rel_country = ? ORDER BY nombreProvince ASC';
    $stmt = mysqli_prepare($mysqli, $sql);
    if (!$stmt) {
        return $mapa;
    }

    mysqli_stmt_bind_param($stmt, 'i', $idPais);
    mysqli_stmt_execute($stmt);
    $res = mysqli_stmt_get_result($stmt);

    while ($row = mysqli_fetch_assoc($res)) {
        $clave = migracion_provincias_clave($row['nombreProvince']);
        if ($clave === '') {
            continue;
        }
        $mapa[$clave] = array(
            'id'     => (int) $row['id_province'],
            'nombre' => $row['nombreProvince'],
        );
    }

    mysqli_stmt_close($stmt);

    if (!empty($mapa)) {
        return $mapa;
    }

    $res = mysqli_query($mysqli, 'SELECT id_province, nombreProvince FROM provincias ORDER BY nombreProvince ASC');
    if (!$res) {
        return $mapa;
    }

    while ($row = mysqli_fetch_assoc($res)) {
        $clave = migracion_provincias_clave($row['nombreProvince']);
        if ($clave === '') {
            continue;
        }
        $mapa[$clave] = array(
            'id'     => (int) $row['id_province'],
            'nombre' => $row['nombreProvince'],
        );
    }
    mysqli_free_result($res);

    return $mapa;
}

/**
 * @return array lista de nombres oficiales
 */
function migracion_lista_nombres_provincias($prov_map)
{
    $nombres = array();
    foreach ($prov_map as $item) {
        if (!empty($item['nombre'])) {
            $nombres[] = $item['nombre'];
        }
    }
    sort($nombres, SORT_NATURAL | SORT_FLAG_CASE);
    return $nombres;
}

/**
 * Convierte c_provincia en direcciones a formato título (ZARAGOZA → Zaragoza).
 * Debe ejecutarse al inicio, antes del match directo o la IA.
 *
 * @param mysqli $mysqli
 * @return array{filas: int, errores: array}
 */
function migracion_normalizar_c_provincia_direcciones($mysqli)
{
    $resultado = array('filas' => 0, 'errores' => array());

    $res = mysqli_query(
        $mysqli,
        "SELECT id_direcciones, c_provincia FROM direcciones WHERE TRIM(c_provincia) <> ''"
    );
    if (!$res) {
        $resultado['errores'][] = mysqli_error($mysqli);
        return $resultado;
    }

    $stmt = mysqli_prepare(
        $mysqli,
        'UPDATE direcciones SET c_provincia = ? WHERE id_direcciones = ?'
    );
    if (!$stmt) {
        mysqli_free_result($res);
        $resultado['errores'][] = mysqli_error($mysqli);
        return $resultado;
    }

    while ($row = mysqli_fetch_assoc($res)) {
        $original = (string) ($row['c_provincia'] ?? '');
        $titulo = migracion_provincias_formato_titulo($original);
        if ($titulo === '' || $titulo === $original) {
            continue;
        }

        $id = (int) $row['id_direcciones'];
        mysqli_stmt_bind_param($stmt, 'si', $titulo, $id);
        if (mysqli_stmt_execute($stmt)) {
            $resultado['filas'] += max(0, mysqli_stmt_affected_rows($stmt));
        } else {
            $resultado['errores'][] = mysqli_stmt_error($stmt) . ' (id ' . $id . ')';
        }
    }

    mysqli_stmt_close($stmt);
    mysqli_free_result($res);

    return $resultado;
}

/**
 * @param mysqli $mysqli
 * @return array
 */
function migracion_obtener_provincias_distintas_direcciones($mysqli)
{
    $valores = array();
    $res = mysqli_query(
        $mysqli,
        "SELECT DISTINCT c_provincia FROM direcciones WHERE TRIM(c_provincia) <> ''"
    );
    if (!$res) {
        return $valores;
    }

    while ($row = mysqli_fetch_assoc($res)) {
        $valores[] = $row['c_provincia'];
    }
    mysqli_free_result($res);

    return $valores;
}

function migracion_resolver_provincia($valor, $prov_map)
{
    $valorTitulo = migracion_provincias_formato_titulo($valor);
    if ($valorTitulo === '') {
        return array(null, 0);
    }

    foreach ($prov_map as $item) {
        if ($item['nombre'] === $valorTitulo) {
            return array($item['nombre'], $item['id']);
        }
    }

    $clave = migracion_provincias_clave($valorTitulo);
    if ($clave === '' || !isset($prov_map[$clave])) {
        return array(null, 0);
    }

    return array($prov_map[$clave]['nombre'], $prov_map[$clave]['id']);
}

/**
 * @return array{resueltos: array, sin_mapear: array}
 */
function migracion_clasificar_provincias($valores, $prov_map)
{
    $sin_mapear = array();
    $resueltos = array();

    foreach ($valores as $v) {
        list($nombre, $id) = migracion_resolver_provincia($v, $prov_map);
        if ($nombre === null) {
            $sin_mapear[] = $v;
        } else {
            $resueltos[$v] = array($nombre, $id);
        }
    }

    return array(
        'resueltos'  => $resueltos,
        'sin_mapear' => $sin_mapear,
    );
}

/**
 * @param array $sin_mapear
 * @param array $lista_oficial
 * @return array|false
 */
function migracion_ia_mapear_provincias_lote($sin_mapear, $lista_oficial)
{
    if (empty($sin_mapear)) {
        return array();
    }

    $valoresIa = array();
    foreach (array_values($sin_mapear) as $valor) {
        $valoresIa[] = migracion_provincias_formato_titulo($valor);
    }

    $valoresJson = json_encode($valoresIa, JSON_UNESCAPED_UNICODE);
    $oficialJson = json_encode(array_values($lista_oficial), JSON_UNESCAPED_UNICODE);

    $prompt = "Eres un asistente de limpieza de datos para un TPV en España.\n"
        . "Debes mapear valores libres de direcciones.c_provincia al nombre EXACTO de la tabla provincias (nombreProvince).\n\n"
        . "PROVINCIAS VÁLIDAS DE ESPAÑA (usa solo estos nombres exactos):\n"
        . $oficialJson . "\n\n"
        . "VALORES A MAPEAR:\n"
        . $valoresJson . "\n\n"
        . "Reglas:\n"
        . "- Si es typo, abreviatura, nombre antiguo o variante (ej. La Coruña / A Coruña), elige la provincia correcta de la lista.\n"
        . "- Si es un país, ciudad que no es provincia, basura o imposible de saber, usa null.\n"
        . "- Responde SOLO JSON válido: array de objetos {\"valor_original\":\"...\",\"nombre_provincia\":\"...\"|null,\"motivo\":\"...\"}\n"
        . "- valor_original debe coincidir EXACTAMENTE con el texto recibido.\n"
        . "- nombre_provincia debe ser null o uno de la lista oficial, carácter por carácter.";

    $respuesta = ia_llamar_claude(
        array(array('role' => 'user', 'content' => $prompt)),
        4096,
        120
    );

    if ($respuesta === false || $respuesta === '') {
        return false;
    }

    $jsonText = $respuesta;
    if (preg_match('/\[[\s\S]*\]/', $respuesta, $m)) {
        $jsonText = $m[0];
    }

    $parsed = json_decode($jsonText, true);
    if (!is_array($parsed)) {
        return false;
    }

    return $parsed;
}

/**
 * @param array      $sugerencias
 * @param array      $prov_map
 * @param array|null $valores_bd_originales valores de c_provincia en BD (mismo orden que el lote enviado a IA)
 * @return array mapeo valor_original_bd => array(nombre, id)
 */
function migracion_ia_validar_provincias($sugerencias, $prov_map, $valores_bd_originales = null)
{
    $resueltos = array();

    if (!is_array($sugerencias)) {
        return $resueltos;
    }

    $mapaBdPorTitulo = array();
    if (is_array($valores_bd_originales)) {
        foreach ($valores_bd_originales as $valorBd) {
            $titulo = migracion_provincias_formato_titulo($valorBd);
            if ($titulo !== '') {
                $mapaBdPorTitulo[$titulo] = (string) $valorBd;
            }
        }
    }

    foreach ($sugerencias as $item) {
        if (!is_array($item) || !isset($item['valor_original'])) {
            continue;
        }

        $valorIa = (string) $item['valor_original'];
        if ($valorIa === '') {
            continue;
        }

        $valorOriginal = isset($mapaBdPorTitulo[$valorIa])
            ? $mapaBdPorTitulo[$valorIa]
            : $valorIa;

        if (!array_key_exists('nombre_provincia', $item) || $item['nombre_provincia'] === null || $item['nombre_provincia'] === '') {
            continue;
        }

        list($nombre, $id) = migracion_resolver_provincia((string) $item['nombre_provincia'], $prov_map);
        if ($nombre === null) {
            continue;
        }

        $resueltos[$valorOriginal] = array($nombre, $id);
    }

    return $resueltos;
}

/**
 * @param mysqli $mysqli
 * @param array  $resueltos valor_original => array(nombre, id)
 * @return array{filas: int, errores: array}
 */
function migracion_aplicar_provincias_direcciones($mysqli, $resueltos)
{
    $resultado = array('filas' => 0, 'errores' => array());

    if (empty($resueltos)) {
        return $resultado;
    }

    $stmt = mysqli_prepare(
        $mysqli,
        'UPDATE direcciones
            SET c_provincia = ?, rel_id_provincia = ?
          WHERE BINARY c_provincia = ?'
    );

    if (!$stmt) {
        $resultado['errores'][] = mysqli_error($mysqli);
        return $resultado;
    }

    foreach ($resueltos as $valorOriginal => $datos) {
        $nombre = $datos[0];
        $id = (int) $datos[1];
        mysqli_stmt_bind_param($stmt, 'sis', $nombre, $id, $valorOriginal);
        if (mysqli_stmt_execute($stmt)) {
            $resultado['filas'] += max(0, mysqli_stmt_affected_rows($stmt));
        } else {
            $resultado['errores'][] = mysqli_stmt_error($stmt) . ' (' . $valorOriginal . ')';
        }
    }

    mysqli_stmt_close($stmt);

    return $resultado;
}
