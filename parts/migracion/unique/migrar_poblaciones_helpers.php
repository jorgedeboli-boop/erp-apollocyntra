<?php
/**
 * Helpers: mapeo de direcciones.c_poblacion → poblacion (directo + IA por provincia).
 */

require_once __DIR__ . '/migrar_provincias_helpers.php';
require_once __DIR__ . '/../../../include/ia_claude.php';

function migracion_poblaciones_formato_titulo($valor)
{
    return migracion_provincias_formato_titulo($valor);
}

function migracion_poblaciones_clave($texto)
{
    return migracion_provincias_clave($texto);
}

/**
 * Normaliza c_poblacion en lotes pequeños (evita HTTP 504).
 *
 * @param mysqli $mysqli
 * @param int    $desde_id id_direcciones > este valor
 * @param int    $limit    filas a leer por lote
 * @return array{filas: int, ultimo_id: int, hay_mas: bool, leidas: int, errores: array}
 */
function migracion_normalizar_c_poblacion_direcciones($mysqli, $desde_id = 0, $limit = 200)
{
    $resultado = array(
        'filas'     => 0,
        'ultimo_id' => (int) $desde_id,
        'hay_mas'   => false,
        'leidas'    => 0,
        'errores'   => array(),
    );

    $desde_id = max(0, (int) $desde_id);
    $limit = max(50, min(300, (int) $limit));

    $stmtSel = mysqli_prepare(
        $mysqli,
        'SELECT id_direcciones, c_poblacion
           FROM direcciones
          WHERE TRIM(c_poblacion) <> \'\'
            AND id_direcciones > ?
          ORDER BY id_direcciones ASC
          LIMIT ?'
    );
    if (!$stmtSel) {
        $resultado['errores'][] = mysqli_error($mysqli);
        return $resultado;
    }

    mysqli_stmt_bind_param($stmtSel, 'ii', $desde_id, $limit);
    mysqli_stmt_execute($stmtSel);
    $res = mysqli_stmt_get_result($stmtSel);
    if (!$res) {
        mysqli_stmt_close($stmtSel);
        $resultado['errores'][] = mysqli_error($mysqli);
        return $resultado;
    }

    $stmtUpd = mysqli_prepare(
        $mysqli,
        'UPDATE direcciones SET c_poblacion = ? WHERE id_direcciones = ?'
    );
    if (!$stmtUpd) {
        mysqli_free_result($res);
        mysqli_stmt_close($stmtSel);
        $resultado['errores'][] = mysqli_error($mysqli);
        return $resultado;
    }

    $ultimoId = $desde_id;
    $leidas = 0;

    while ($row = mysqli_fetch_assoc($res)) {
        $leidas++;
        $id = (int) $row['id_direcciones'];
        $ultimoId = $id;
        $original = (string) ($row['c_poblacion'] ?? '');
        $titulo = migracion_poblaciones_formato_titulo($original);
        if ($titulo === '' || $titulo === $original) {
            continue;
        }

        mysqli_stmt_bind_param($stmtUpd, 'si', $titulo, $id);
        if (mysqli_stmt_execute($stmtUpd)) {
            $resultado['filas'] += max(0, mysqli_stmt_affected_rows($stmtUpd));
        } else {
            $resultado['errores'][] = mysqli_stmt_error($stmtUpd) . ' (id ' . $id . ')';
        }

        if ($leidas % 50 === 0) {
            migracion_flush_salida('Lote normalización: ' . $leidas . ' filas...');
        }
    }

    mysqli_stmt_close($stmtUpd);
    mysqli_free_result($res);
    mysqli_stmt_close($stmtSel);

    $resultado['ultimo_id'] = $ultimoId;
    $resultado['leidas'] = $leidas;
    $resultado['hay_mas'] = $leidas >= $limit;

    return $resultado;
}

/**
 * Estado rápido de la migración de poblaciones.
 *
 * @param mysqli $mysqli
 * @return array
 */
function migracion_poblaciones_estado($mysqli)
{
    $estado = array(
        'con_poblacion'      => 0,
        'con_rel_poblacion'  => 0,
        'sin_rel_poblacion'  => 0,
        'pendientes_ia'      => 0,
    );

    $res = mysqli_query(
        $mysqli,
        "SELECT
            SUM(CASE WHEN TRIM(c_poblacion) <> '' THEN 1 ELSE 0 END) AS con_poblacion,
            SUM(CASE WHEN TRIM(c_poblacion) <> '' AND rel_id_poblacion > 0 THEN 1 ELSE 0 END) AS con_rel,
            SUM(CASE WHEN TRIM(c_poblacion) <> '' AND rel_id_provincia > 0
                      AND (rel_id_poblacion IS NULL OR rel_id_poblacion = 0) THEN 1 ELSE 0 END) AS sin_rel
         FROM direcciones"
    );
    if ($res) {
        $row = mysqli_fetch_assoc($res);
        $estado['con_poblacion'] = (int) ($row['con_poblacion'] ?? 0);
        $estado['con_rel_poblacion'] = (int) ($row['con_rel'] ?? 0);
        $estado['sin_rel_poblacion'] = (int) ($row['sin_rel'] ?? 0);
        mysqli_free_result($res);
    }

    $pob_map = migracion_cargar_mapa_poblaciones($mysqli);
    if (!empty($pob_map)) {
        $plan = migracion_poblaciones_obtener_pendientes_ia($mysqli, $pob_map, 10);
        $estado['pendientes_ia'] = (int) $plan['pendientes'];
    }

    return $estado;
}

/**
 * @param mysqli $mysqli
 * @return array<int, array<string, array{id:int,nombre:string,postal:string}>>
 */
function migracion_cargar_mapa_poblaciones($mysqli)
{
    $mapa = array();

    $res = mysqli_query(
        $mysqli,
        'SELECT idpoblacion, idprovincia, poblacion, postal FROM poblacion ORDER BY poblacion ASC'
    );
    if (!$res) {
        return $mapa;
    }

    while ($row = mysqli_fetch_assoc($res)) {
        $idProvincia = (int) $row['idprovincia'];
        $clave = migracion_poblaciones_clave($row['poblacion']);
        if ($idProvincia <= 0 || $clave === '') {
            continue;
        }

        if (!isset($mapa[$idProvincia])) {
            $mapa[$idProvincia] = array();
        }

        $mapa[$idProvincia][$clave] = array(
            'id'     => (int) $row['idpoblacion'],
            'nombre' => $row['poblacion'],
            'postal' => (string) ($row['postal'] ?? ''),
        );
    }

    mysqli_free_result($res);

    return $mapa;
}

/**
 * @param mysqli $mysqli
 * @return array<int, array<int, array{rel_id_provincia:int,c_poblacion:string}>>
 */
function migracion_obtener_poblaciones_distintas_direcciones($mysqli)
{
    $items = array();
    $res = mysqli_query(
        $mysqli,
        "SELECT DISTINCT rel_id_provincia, c_poblacion
           FROM direcciones
          WHERE TRIM(c_poblacion) <> ''
            AND rel_id_provincia > 0"
    );
    if (!$res) {
        return $items;
    }

    while ($row = mysqli_fetch_assoc($res)) {
        $items[] = array(
            'rel_id_provincia' => (int) $row['rel_id_provincia'],
            'c_poblacion'      => (string) $row['c_poblacion'],
        );
    }
    mysqli_free_result($res);

    return $items;
}

/**
 * @param array<int, array<string, array{id:int,nombre:string,postal:string}>> $pob_map
 * @return array|null array(nombre, id, postal)
 */
function migracion_resolver_poblacion($c_poblacion, $id_provincia, $pob_map)
{
    $id_provincia = (int) $id_provincia;
    if ($id_provincia <= 0 || !isset($pob_map[$id_provincia])) {
        return null;
    }

    $valorTitulo = migracion_poblaciones_formato_titulo($c_poblacion);
    if ($valorTitulo === '') {
        return null;
    }

    $mapaProv = $pob_map[$id_provincia];

    foreach ($mapaProv as $item) {
        if ($item['nombre'] === $valorTitulo) {
            return array($item['nombre'], $item['id'], $item['postal']);
        }
    }

    $clave = migracion_poblaciones_clave($valorTitulo);
    if ($clave === '' || !isset($mapaProv[$clave])) {
        return null;
    }

    $item = $mapaProv[$clave];
    return array($item['nombre'], $item['id'], $item['postal']);
}

/**
 * @param array $items array de {rel_id_provincia, c_poblacion}
 * @return array{resueltos: array, sin_mapear: array}
 */
function migracion_clasificar_poblaciones($items, $pob_map)
{
    $sin_mapear = array();
    $resueltos = array();

    foreach ($items as $item) {
        $idProv = (int) $item['rel_id_provincia'];
        $valor = (string) $item['c_poblacion'];
        $resuelto = migracion_resolver_poblacion($valor, $idProv, $pob_map);

        if ($resuelto === null) {
            $sin_mapear[] = array(
                'rel_id_provincia' => $idProv,
                'c_poblacion'      => $valor,
            );
        } else {
            $resueltos[migracion_poblaciones_item_clave($idProv, $valor)] = array(
                'rel_id_provincia' => $idProv,
                'valor_original'   => $valor,
                'nombre'           => $resuelto[0],
                'id'               => $resuelto[1],
                'postal'           => $resuelto[2],
            );
        }
    }

    return array(
        'resueltos'  => $resueltos,
        'sin_mapear' => $sin_mapear,
    );
}

function migracion_poblaciones_item_clave($idProvincia, $cPoblacion)
{
    return (int) $idProvincia . '|' . (string) $cPoblacion;
}

/**
 * @param array $pob_map_prov mapa de una provincia
 * @return array
 */
function migracion_lista_nombres_poblaciones_provincia($pob_map_prov)
{
    $nombres = array();
    if (!is_array($pob_map_prov)) {
        return $nombres;
    }

    foreach ($pob_map_prov as $item) {
        if (!empty($item['nombre'])) {
            $nombres[] = $item['nombre'];
        }
    }

    sort($nombres, SORT_NATURAL | SORT_FLAG_CASE);
    return $nombres;
}

/**
 * @param array $sin_mapear array de {rel_id_provincia, c_poblacion}
 * @param array $pob_map
 * @return array|false
 */
function migracion_ia_mapear_poblaciones_lote($sin_mapear, $pob_map)
{
    if (empty($sin_mapear)) {
        return array();
    }

    $idProvincia = (int) $sin_mapear[0]['rel_id_provincia'];
    $listaOficial = migracion_lista_nombres_poblaciones_provincia(
        isset($pob_map[$idProvincia]) ? $pob_map[$idProvincia] : array()
    );

    if (empty($listaOficial)) {
        return array();
    }

    $valoresIa = array();
    foreach ($sin_mapear as $item) {
        $valoresIa[] = array(
            'rel_id_provincia' => (int) $item['rel_id_provincia'],
            'c_poblacion'      => migracion_poblaciones_formato_titulo($item['c_poblacion']),
        );
    }

    $valoresJson = json_encode($valoresIa, JSON_UNESCAPED_UNICODE);
    $oficialJson = json_encode(array_values($listaOficial), JSON_UNESCAPED_UNICODE);

    $prompt = "Eres un asistente de limpieza de datos para un TPV en España.\n"
        . "Debes mapear valores libres de direcciones.c_poblacion al nombre EXACTO de la tabla poblacion (campo poblacion).\n\n"
        . "PROVINCIA (rel_id_provincia / poblacion.idprovincia): {$idProvincia}\n"
        . "Solo puedes elegir poblaciones de esta provincia. direcciones.rel_id_provincia debe coincidir con poblacion.idprovincia.\n\n"
        . "POBLACIONES VÁLIDAS DE ESA PROVINCIA (usa solo estos nombres exactos):\n"
        . $oficialJson . "\n\n"
        . "VALORES A MAPEAR (JSON con rel_id_provincia y c_poblacion):\n"
        . $valoresJson . "\n\n"
        . "Reglas:\n"
        . "- Compara solo dentro de la provincia indicada.\n"
        . "- Si es typo, abreviatura o variante local, elige la población correcta de la lista.\n"
        . "- Si es basura, otra provincia, país o imposible de saber, usa null.\n"
        . "- Responde SOLO JSON válido: array de objetos {\"rel_id_provincia\":N,\"valor_original\":\"...\",\"nombre_poblacion\":\"...\"|null,\"motivo\":\"...\"}\n"
        . "- valor_original debe coincidir EXACTAMENTE con c_poblacion recibido (ya en formato título).\n"
        . "- nombre_poblacion debe ser null o uno de la lista oficial, carácter por carácter.";

    $respuesta = ia_llamar_claude(
        array(array('role' => 'user', 'content' => $prompt)),
        4096,
        50
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
 * @param array      $pob_map
 * @param array|null $items_bd_originales
 * @return array clave compuesta => datos resueltos
 */
function migracion_ia_validar_poblaciones($sugerencias, $pob_map, $items_bd_originales = null)
{
    $resueltos = array();

    if (!is_array($sugerencias)) {
        return $resueltos;
    }

    $mapaBd = array();
    if (is_array($items_bd_originales)) {
        foreach ($items_bd_originales as $item) {
            $idProv = (int) ($item['rel_id_provincia'] ?? 0);
            $titulo = migracion_poblaciones_formato_titulo($item['c_poblacion'] ?? '');
            if ($idProv > 0 && $titulo !== '') {
                $mapaBd[$idProv . '|' . $titulo] = $item;
            }
        }
    }

    foreach ($sugerencias as $item) {
        if (!is_array($item) || !isset($item['valor_original'])) {
            continue;
        }

        $idProv = (int) ($item['rel_id_provincia'] ?? 0);
        $valorIa = (string) $item['valor_original'];
        if ($idProv <= 0 || $valorIa === '') {
            continue;
        }

        $claveIa = $idProv . '|' . $valorIa;
        $itemBd = isset($mapaBd[$claveIa]) ? $mapaBd[$claveIa] : array(
            'rel_id_provincia' => $idProv,
            'c_poblacion'      => $valorIa,
        );

        if (!array_key_exists('nombre_poblacion', $item) || $item['nombre_poblacion'] === null || $item['nombre_poblacion'] === '') {
            continue;
        }

        $resuelto = migracion_resolver_poblacion((string) $item['nombre_poblacion'], $idProv, $pob_map);
        if ($resuelto === null) {
            continue;
        }

        $valorOriginal = (string) $itemBd['c_poblacion'];
        $resueltos[migracion_poblaciones_item_clave($idProv, $valorOriginal)] = array(
            'rel_id_provincia' => $idProv,
            'valor_original'   => $valorOriginal,
            'nombre'           => $resuelto[0],
            'id'               => $resuelto[1],
            'postal'           => $resuelto[2],
        );
    }

    return $resueltos;
}

/**
 * @param mysqli $mysqli
 * @param array  $resueltos
 * @return array{filas: int, errores: array}
 */
function migracion_aplicar_poblaciones_direcciones($mysqli, $resueltos)
{
    $resultado = array('filas' => 0, 'errores' => array());

    if (empty($resueltos)) {
        return $resultado;
    }

    $stmt = mysqli_prepare(
        $mysqli,
        'UPDATE direcciones
            SET c_poblacion = ?, rel_id_poblacion = ?, codigo_postal = ?
          WHERE rel_id_provincia = ?
            AND BINARY c_poblacion = ?'
    );

    if (!$stmt) {
        $resultado['errores'][] = mysqli_error($mysqli);
        return $resultado;
    }

    foreach ($resueltos as $datos) {
        $nombre = $datos['nombre'];
        $id = (int) $datos['id'];
        $postal = (string) $datos['postal'];
        $idProv = (int) $datos['rel_id_provincia'];
        $valorOriginal = (string) $datos['valor_original'];

        mysqli_stmt_bind_param($stmt, 'sisis', $nombre, $id, $postal, $idProv, $valorOriginal);
        if (mysqli_stmt_execute($stmt)) {
            $resultado['filas'] += max(0, mysqli_stmt_affected_rows($stmt));
        } else {
            $resultado['errores'][] = mysqli_stmt_error($stmt) . ' (' . $idProv . '|' . $valorOriginal . ')';
        }
    }

    mysqli_stmt_close($stmt);

    return $resultado;
}

/**
 * Asigna rel_id_poblacion y codigo_postal con UPDATE masivo (JOIN).
 *
 * @param mysqli $mysqli
 * @return array{filas: int, errores: array, sin_mapear: int}
 */
function migracion_asignar_poblaciones_restantes($mysqli)
{
    $resultado = array('filas' => 0, 'errores' => array(), 'sin_mapear' => 0);

    $sql = "UPDATE direcciones d
            INNER JOIN poblacion p
                    ON p.idprovincia = d.rel_id_provincia
                   AND p.poblacion = d.c_poblacion
               SET d.rel_id_poblacion = p.idpoblacion,
                   d.codigo_postal = IFNULL(p.postal, '')
             WHERE TRIM(d.c_poblacion) <> ''
               AND d.rel_id_provincia > 0";

    if (!mysqli_query($mysqli, $sql)) {
        $resultado['errores'][] = mysqli_error($mysqli);
        return $resultado;
    }

    $resultado['filas'] = max(0, (int) mysqli_affected_rows($mysqli));

    $res = mysqli_query(
        $mysqli,
        "SELECT COUNT(*) AS n
           FROM direcciones d
           LEFT JOIN poblacion p
                  ON p.idprovincia = d.rel_id_provincia
                 AND p.poblacion = d.c_poblacion
          WHERE TRIM(d.c_poblacion) <> ''
            AND d.rel_id_provincia > 0
            AND p.idpoblacion IS NULL"
    );
    if ($res) {
        $row = mysqli_fetch_assoc($res);
        $resultado['sin_mapear'] = (int) ($row['n'] ?? 0);
        mysqli_free_result($res);
    }

    return $resultado;
}

/**
 * @param mysqli $mysqli
 * @param array  $pob_map
 * @param int    $tamano_lote
 * @return array
 */
function migracion_poblaciones_obtener_pendientes_ia($mysqli, $pob_map, $tamano_lote = 25)
{
    $items = migracion_obtener_poblaciones_distintas_direcciones($mysqli);
    $clasificacion = migracion_clasificar_poblaciones($items, $pob_map);
    $grupos = migracion_agrupar_poblaciones_por_provincia($clasificacion['sin_mapear']);
    $lotes = array();

    foreach ($grupos as $idProvincia => $itemsProvincia) {
        $chunks = array_chunk($itemsProvincia, $tamano_lote);
        foreach ($chunks as $idx => $chunk) {
            $lotes[] = array(
                'provincia' => (int) $idProvincia,
                'idx'       => (int) $idx,
                'total'     => count($chunk),
            );
        }
    }

    return array(
        'pendientes' => count($clasificacion['sin_mapear']),
        'lotes'      => $lotes,
    );
}

/**
 * @param mysqli $mysqli
 * @param array  $pob_map
 * @param int    $idProvincia
 * @param int    $idx
 * @param int    $tamano_lote
 * @return array{lote: array, resueltos: array, sin_mapear: array}
 */
function migracion_poblaciones_obtener_lote_ia($mysqli, $pob_map, $idProvincia, $idx, $tamano_lote = 25)
{
    $pendientes = migracion_poblaciones_obtener_pendientes_ia($mysqli, $pob_map, $tamano_lote);
    $lote = array();

    foreach ($pendientes['lotes'] as $item) {
        if ((int) $item['provincia'] === (int) $idProvincia && (int) $item['idx'] === (int) $idx) {
            $items = migracion_obtener_poblaciones_distintas_direcciones($mysqli);
            $clasificacion = migracion_clasificar_poblaciones($items, $pob_map);
            $grupos = migracion_agrupar_poblaciones_por_provincia($clasificacion['sin_mapear']);
            if (isset($grupos[$idProvincia])) {
                $chunks = array_chunk($grupos[$idProvincia], $tamano_lote);
                if (isset($chunks[$idx])) {
                    $lote = $chunks[$idx];
                }
            }
            break;
        }
    }

    return array('lote' => $lote);
}

/**
 * @param mysqli $mysqli
 * @return bool
 */
function migracion_poblaciones_verificar_tablas($mysqli)
{
    foreach (array('direcciones', 'poblacion') as $tabla) {
        $chk = mysqli_query($mysqli, "SHOW TABLES LIKE '{$tabla}'");
        if (!$chk || mysqli_num_rows($chk) === 0) {
            if ($chk) {
                mysqli_free_result($chk);
            }
            return false;
        }
        mysqli_free_result($chk);
    }

    return true;
}

/**
 * @param array $sin_mapear
 * @return array<int, array>
 */
function migracion_agrupar_poblaciones_por_provincia($sin_mapear)
{
    $grupos = array();

    foreach ($sin_mapear as $item) {
        $idProv = (int) ($item['rel_id_provincia'] ?? 0);
        if ($idProv <= 0) {
            continue;
        }
        if (!isset($grupos[$idProv])) {
            $grupos[$idProv] = array();
        }
        $grupos[$idProv][] = $item;
    }

    return $grupos;
}
