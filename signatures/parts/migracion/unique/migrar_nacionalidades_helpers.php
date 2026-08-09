<?php
/**
 * Helpers compartidos: mapeo nacionalidades (manual + IA aprobada).
 */

require_once __DIR__ . '/../../../include/ia_claude.php';

function migrar_nacionalidades_trim($valor)
{
    $v = trim((string) $valor);
    if ($v === '') {
        return '';
    }
    $v = preg_replace('/\x{00A0}/u', ' ', $v);
    $v = trim($v);
    if (class_exists('Normalizer')) {
        $n = Normalizer::normalize($v, Normalizer::FORM_C);
        if ($n !== false) {
            $v = $n;
        }
    }
    return $v;
}

function migracion_normalizar_clave_nacionalidad($texto)
{
    $t = migrar_nacionalidades_trim($texto);
    if ($t === '') {
        return '';
    }
    return mb_strtolower($t, 'UTF-8');
}

function migrar_nacionalidades_repr($valor)
{
    return json_encode($valor, JSON_UNESCAPED_UNICODE);
}

function migrar_nacionalidades_abort($mensaje, $mysqli = null)
{
    if ($mysqli instanceof mysqli) {
        $mysqli->close();
    }
    echo "ERROR: " . $mensaje . "\n";
    exit(1);
}

function migracion_nacionalidades_tabla_existe($mysqli)
{
    $res = mysqli_query($mysqli, "SHOW TABLES LIKE 'migraciones_nacionalidades_mapeo'");
    if (!$res) {
        return false;
    }
    $ok = mysqli_num_rows($res) > 0;
    mysqli_free_result($res);
    return $ok;
}

function migracion_cargar_mapa_manual()
{
    require __DIR__ . '/migrar_nacionalidades_mapa.php';
    return isset($MAPA) && is_array($MAPA) ? $MAPA : array();
}

/**
 * Mapeos aprobados en BD: valor_original => nombre_nacionalidad (string|null).
 *
 * @param mysqli $mysqli
 * @return array
 */
function migracion_cargar_mapa_aprobado($mysqli)
{
    if (!migracion_nacionalidades_tabla_existe($mysqli)) {
        return array();
    }

    $mapa = array();
    $sql = "SELECT valor_original, nombre_nacionalidad
            FROM migraciones_nacionalidades_mapeo
            WHERE estado = 'aprobado'";

    $res = mysqli_query($mysqli, $sql);
    if (!$res) {
        return array();
    }

    while ($row = mysqli_fetch_assoc($res)) {
        $clave = migrar_nacionalidades_trim($row['valor_original']);
        if ($clave === '') {
            continue;
        }
        $nombre = $row['nombre_nacionalidad'];
        $mapa[$clave] = ($nombre === null || $nombre === '') ? null : $nombre;
    }
    mysqli_free_result($res);

    return $mapa;
}

/**
 * @param mysqli $mysqli
 * @return array
 */
function migracion_cargar_nac_map($mysqli)
{
    $nac_map = array();
    $res = mysqli_query($mysqli, 'SELECT id, nombre_nacionalidad FROM nacionalidades');
    if (!$res) {
        return $nac_map;
    }

    while ($row = mysqli_fetch_assoc($res)) {
        $clave = migracion_normalizar_clave_nacionalidad($row['nombre_nacionalidad']);
        if ($clave === '') {
            continue;
        }
        $nac_map[$clave] = array(
            'id'     => (int) $row['id'],
            'nombre' => $row['nombre_nacionalidad'],
        );
    }
    mysqli_free_result($res);

    return $nac_map;
}

/**
 * @return array lista de nombres oficiales
 */
function migracion_lista_nombres_nacionalidades($nac_map)
{
    $nombres = array();
    foreach ($nac_map as $item) {
        if (!empty($item['nombre'])) {
            $nombres[] = $item['nombre'];
        }
    }
    sort($nombres, SORT_NATURAL | SORT_FLAG_CASE);
    return $nombres;
}

function migracion_resolver($valor_cliente, $MAPA, $nac_map)
{
    $v = migrar_nacionalidades_trim($valor_cliente);

    if (array_key_exists($v, $MAPA)) {
        $nombre_oficial = $MAPA[$v];
        if ($nombre_oficial === null) {
            return array(null, 0);
        }
        $key = migracion_normalizar_clave_nacionalidad($nombre_oficial);
        if (isset($nac_map[$key])) {
            return array($nac_map[$key]['nombre'], $nac_map[$key]['id']);
        }
    }

    $key = migracion_normalizar_clave_nacionalidad($v);
    if (isset($nac_map[$key])) {
        return array($nac_map[$key]['nombre'], $nac_map[$key]['id']);
    }

    return array(null, 0);
}

/**
 * @param mysqli $mysqli
 * @return array
 */
function migracion_obtener_valores_distintos_nacionalidad($mysqli)
{
    $valores = array();
    $res = mysqli_query($mysqli, "SELECT DISTINCT nacionalidad FROM clientes WHERE delete_state='false'");
    if (!$res) {
        return $valores;
    }

    while ($row = mysqli_fetch_assoc($res)) {
        $valores[] = $row['nacionalidad'];
    }
    mysqli_free_result($res);

    return $valores;
}

/**
 * @return array{resueltos: array, sin_mapear: array}
 */
function migracion_clasificar_nacionalidades($valores, $MAPA, $nac_map)
{
    $sin_mapear = array();
    $resueltos = array();

    foreach ($valores as $v) {
        list($nombre, $id) = migracion_resolver($v, $MAPA, $nac_map);
        if ($nombre === null) {
            $sin_mapear[] = $v;
        } else {
            $resueltos[$v] = array($nombre, $id);
        }
    }

    return array(
        'resueltos'   => $resueltos,
        'sin_mapear'  => $sin_mapear,
    );
}

/**
 * @param array $sin_mapear
 * @param array $lista_oficial
 * @return array|false
 */
function migracion_ia_sugerir_lote($sin_mapear, $lista_oficial)
{
    if (empty($sin_mapear)) {
        return array();
    }

    $valoresJson = json_encode(array_values($sin_mapear), JSON_UNESCAPED_UNICODE);
    $oficialJson = json_encode(array_values($lista_oficial), JSON_UNESCAPED_UNICODE);

    $prompt = "Eres un asistente de limpieza de datos para un TPV en España.\n"
        . "Debes mapear valores libres encontrados en clientes.nacionalidad al nombre EXACTO de la tabla nacionalidades.\n\n"
        . "NACIONALIDADES VÁLIDAS (usa solo estos nombres exactos):\n"
        . $oficialJson . "\n\n"
        . "VALORES A MAPEAR:\n"
        . $valoresJson . "\n\n"
        . "Reglas:\n"
        . "- Si es typo, ciudad mal escrita o variante, elige la nacionalidad correcta de la lista.\n"
        . "- Si es apátrida, fecha, número, basura o imposible de saber, usa null.\n"
        . "- Responde SOLO JSON válido: array de objetos {\"valor_original\":\"...\",\"nombre_nacionalidad\":\"...\"|null,\"motivo\":\"...\"}\n"
        . "- valor_original debe coincidir EXACTAMENTE con el texto recibido (incluidos espacios internos).\n"
        . "- nombre_nacionalidad debe ser null o uno de la lista oficial, carácter por carácter.";

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
 * @param mysqli $mysqli
 * @param array  $sugerencias
 * @param array  $nac_map
 * @param int    $usuario_id
 * @return array{insertados: int, omitidos: int, invalidos: int}
 */
function migracion_ia_guardar_sugerencias($mysqli, $sugerencias, $nac_map, $usuario_id = 0)
{
    $stats = array('insertados' => 0, 'omitidos' => 0, 'invalidos' => 0);

    if (!migracion_nacionalidades_tabla_existe($mysqli) || !is_array($sugerencias)) {
        return $stats;
    }

    $stmt = mysqli_prepare(
        $mysqli,
        "INSERT INTO migraciones_nacionalidades_mapeo
            (valor_original, nombre_nacionalidad, id_nacionalidad, origen, estado, motivo_ia, revisado_por)
         VALUES (?, ?, ?, 'ia', 'pendiente', ?, ?)
         ON DUPLICATE KEY UPDATE
            nombre_nacionalidad = VALUES(nombre_nacionalidad),
            id_nacionalidad = VALUES(id_nacionalidad),
            motivo_ia = VALUES(motivo_ia),
            estado = 'pendiente',
            fecha_revision = NULL"
    );

    if (!$stmt) {
        return $stats;
    }

    foreach ($sugerencias as $item) {
        if (!is_array($item) || !isset($item['valor_original'])) {
            $stats['invalidos']++;
            continue;
        }

        $valorOriginal = (string) $item['valor_original'];
        if ($valorOriginal === '') {
            $stats['invalidos']++;
            continue;
        }

        $nombreSugerido = null;
        $idNacionalidad = 0;

        if (array_key_exists('nombre_nacionalidad', $item) && $item['nombre_nacionalidad'] !== null && $item['nombre_nacionalidad'] !== '') {
            $key = migracion_normalizar_clave_nacionalidad((string) $item['nombre_nacionalidad']);
            if (!isset($nac_map[$key])) {
                $stats['invalidos']++;
                continue;
            }
            $nombreSugerido = $nac_map[$key]['nombre'];
            $idNacionalidad = (int) $nac_map[$key]['id'];
        }

        $motivo = isset($item['motivo']) ? migrar_nacionalidades_trim($item['motivo']) : '';

        mysqli_stmt_bind_param(
            $stmt,
            'ssisi',
            $valorOriginal,
            $nombreSugerido,
            $idNacionalidad,
            $motivo,
            $usuario_id
        );

        if (mysqli_stmt_execute($stmt)) {
            $stats['insertados']++;
        } else {
            $stats['omitidos']++;
        }
    }

    mysqli_stmt_close($stmt);

    return $stats;
}

/**
 * @param mysqli $mysqli
 * @param string $estado
 * @return array
 */
function migracion_ia_listar($mysqli, $estado = 'pendiente')
{
    if (!migracion_nacionalidades_tabla_existe($mysqli)) {
        return array();
    }

    $estados = array('pendiente', 'aprobado', 'rechazado');
    if (!in_array($estado, $estados, true)) {
        $estado = 'pendiente';
    }

    $items = array();
    $sql = "SELECT id_mapeo, valor_original, nombre_nacionalidad, id_nacionalidad, origen, estado, motivo_ia, fecha_creacion, fecha_revision
            FROM migraciones_nacionalidades_mapeo
            WHERE estado = ?
            ORDER BY fecha_creacion DESC, id_mapeo DESC";

    $stmt = mysqli_prepare($mysqli, $sql);
    if (!$stmt) {
        return $items;
    }

    mysqli_stmt_bind_param($stmt, 's', $estado);
    mysqli_stmt_execute($stmt);
    $res = mysqli_stmt_get_result($stmt);

    while ($row = mysqli_fetch_assoc($res)) {
        $items[] = array(
            'id_mapeo'            => (int) $row['id_mapeo'],
            'valor_original'      => $row['valor_original'],
            'nombre_nacionalidad' => $row['nombre_nacionalidad'],
            'id_nacionalidad'     => (int) $row['id_nacionalidad'],
            'origen'              => $row['origen'],
            'estado'              => $row['estado'],
            'motivo_ia'           => $row['motivo_ia'],
            'fecha_creacion'      => $row['fecha_creacion'],
            'fecha_revision'      => $row['fecha_revision'],
        );
    }

    mysqli_stmt_close($stmt);

    return $items;
}

/**
 * @return array{success: bool, message: string}
 */
function migracion_ia_cambiar_estado($mysqli, $id_mapeo, $nuevo_estado, $usuario_id = 0)
{
    if (!migracion_nacionalidades_tabla_existe($mysqli)) {
        return array('success' => false, 'message' => 'La tabla migraciones_nacionalidades_mapeo no existe.');
    }

    $estados = array('aprobado', 'rechazado');
    if ($id_mapeo <= 0 || !in_array($nuevo_estado, $estados, true)) {
        return array('success' => false, 'message' => 'Datos inválidos.');
    }

    $sql = "UPDATE migraciones_nacionalidades_mapeo
            SET estado = ?, revisado_por = ?, fecha_revision = NOW()
            WHERE id_mapeo = ? AND estado = 'pendiente'";

    $stmt = mysqli_prepare($mysqli, $sql);
    if (!$stmt) {
        return array('success' => false, 'message' => 'Error al preparar la consulta.');
    }

    mysqli_stmt_bind_param($stmt, 'sii', $nuevo_estado, $usuario_id, $id_mapeo);
    $ok = mysqli_stmt_execute($stmt);
    $afect = mysqli_stmt_affected_rows($stmt);
    mysqli_stmt_close($stmt);

    if (!$ok || $afect <= 0) {
        return array('success' => false, 'message' => 'No se encontró la sugerencia pendiente.');
    }

    return array('success' => true, 'message' => $nuevo_estado === 'aprobado' ? 'Sugerencia aprobada.' : 'Sugerencia rechazada.');
}

/**
 * Ejecuta sugerencias IA para valores aún sin mapear.
 *
 * @return array
 */
function migracion_ia_procesar_sin_mapear($mysqli, $MAPA, $nac_map, $usuario_id = 0, $tamano_lote = 25)
{
    $resultado = array(
        'sin_mapear_inicial' => 0,
        'lotes'              => 0,
        'insertados'         => 0,
        'invalidos'          => 0,
        'errores'            => array(),
    );

    if (!migracion_nacionalidades_tabla_existe($mysqli)) {
        $resultado['errores'][] = 'Crear tabla migraciones_nacionalidades_mapeo antes de usar IA.';
        return $resultado;
    }

    $valores = migracion_obtener_valores_distintos_nacionalidad($mysqli);
    $clasificacion = migracion_clasificar_nacionalidades($valores, $MAPA, $nac_map);
    $sin_mapear = $clasificacion['sin_mapear'];
    $resultado['sin_mapear_inicial'] = count($sin_mapear);

    if (empty($sin_mapear)) {
        return $resultado;
    }

    $listaOficial = migracion_lista_nombres_nacionalidades($nac_map);
    $lotes = array_chunk($sin_mapear, max(1, (int) $tamano_lote));

    foreach ($lotes as $lote) {
        $resultado['lotes']++;
        $sugerencias = migracion_ia_sugerir_lote($lote, $listaOficial);

        if ($sugerencias === false) {
            $resultado['errores'][] = 'Claude no devolvió JSON válido para un lote.';
            continue;
        }

        $stats = migracion_ia_guardar_sugerencias($mysqli, $sugerencias, $nac_map, $usuario_id);
        $resultado['insertados'] += $stats['insertados'];
        $resultado['invalidos'] += $stats['invalidos'];
    }

    return $resultado;
}

/**
 * Tras normalizar clientes.nacionalidad, recorre TODOS los clientes activos
 * y asigna nacionalidad_id según el texto actual en BD (lookup en nacionalidades).
 * Independiente del bucle de updates de texto.
 *
 * @param mysqli $mysqli
 * @param array  $nac_map
 * @param array  $MAPA
 * @return array{ok: bool, filas: int, error: string|null, sin_id: array, total: int}
 */
function migracion_sincronizar_nacionalidad_id($mysqli, $nac_map = null, $MAPA = array())
{
    if (!is_array($nac_map) || empty($nac_map)) {
        $nac_map = migracion_cargar_nac_map($mysqli);
    }
    if (!is_array($MAPA)) {
        $MAPA = array();
    }

    $res = mysqli_query(
        $mysqli,
        'SELECT id_cliente, nacionalidad, nacionalidad_id FROM clientes'
    );

    if (!$res) {
        return array(
            'ok'     => false,
            'filas'  => 0,
            'error'  => mysqli_error($mysqli),
            'sin_id' => array(),
            'total'  => 0,
        );
    }

    $stmt = mysqli_prepare(
        $mysqli,
        'UPDATE clientes SET nacionalidad_id = ? WHERE id_cliente = ?'
    );

    if (!$stmt) {
        mysqli_free_result($res);
        return array(
            'ok'     => false,
            'filas'  => 0,
            'error'  => mysqli_error($mysqli),
            'sin_id' => array(),
            'total'  => 0,
        );
    }

    $filas = 0;
    $total = 0;
    $sin_id = array();

    while ($row = mysqli_fetch_assoc($res)) {
        $total++;
        $id_cliente = (int) $row['id_cliente'];
        $nacionalidad = (string) $row['nacionalidad'];
        $id_actual = (int) $row['nacionalidad_id'];

        if (migrar_nacionalidades_trim($nacionalidad) === '') {
            continue;
        }

        list(, $id_nuevo) = migracion_resolver($nacionalidad, $MAPA, $nac_map);

        if ($id_nuevo <= 0) {
            if (!isset($sin_id[$nacionalidad])) {
                $sin_id[$nacionalidad] = 0;
            }
            $sin_id[$nacionalidad]++;
            continue;
        }

        if ($id_nuevo === $id_actual) {
            continue;
        }

        mysqli_stmt_bind_param($stmt, 'ii', $id_nuevo, $id_cliente);
        if (mysqli_stmt_execute($stmt)) {
            $filas += max(0, mysqli_stmt_affected_rows($stmt));
        }
    }

    mysqli_free_result($res);
    mysqli_stmt_close($stmt);

    return array(
        'ok'     => true,
        'filas'  => $filas,
        'error'  => null,
        'sin_id' => $sin_id,
        'total'  => $total,
    );
}
