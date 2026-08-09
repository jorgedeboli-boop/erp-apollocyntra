<?php
/**
 * Corregir poblaciones en direcciones (match directo + IA Claude por provincia).
 * Código: corregir_poblaciones
 *
 * Ejecución por pasos (evita HTTP 504):
 *   ?paso=1           normalizar c_poblacion
 *   ?paso=2           match directo
 *   ?paso=ia_plan     listar lotes IA pendientes (json=1)
 *   ?paso=ia&provincia=N&idx=M   un lote IA
 *   ?paso=3           asignar rel_id_poblacion y codigo_postal
 */
require_once __DIR__ . '/../../../include/session.php';
require_once __DIR__ . '/../../../include/functions.php';
require_once __DIR__ . '/migrar_poblaciones_helpers.php';
require_once __DIR__ . '/migracion_runtime_helpers.php';

header('Content-Type: text/plain; charset=utf-8');

migracion_preparar_ejecucion_larga();
migracion_iniciar_respuesta_http_larga();

const MIGRACION_POBLACIONES_LOTE_IA = 10;
const MIGRACION_POBLACIONES_LOTE_NORMALIZAR = 200;

$paso = isset($_GET['paso']) ? trim((string) $_GET['paso']) : '';
$json = isset($_GET['json']) && $_GET['json'] === '1';

$mysqli = conectar_bd();
if (!$mysqli) {
    migracion_responder_paso($json, false, array('message' => 'No se pudo conectar a la base de datos.'));
}

if (!migracion_poblaciones_verificar_tablas($mysqli)) {
    $mysqli->close();
    migracion_responder_paso($json, false, array('message' => 'Faltan tablas direcciones o poblacion.'));
}

if ($paso === 'estado') {
    $estado = migracion_poblaciones_estado($mysqli);
    $mysqli->close();
    migracion_responder_paso($json, true, array_merge(array(
        'paso'    => 'estado',
        'message' => 'Estado: con_rel_poblacion=' . $estado['con_rel_poblacion']
            . ' sin_rel=' . $estado['sin_rel_poblacion']
            . ' pendientes_ia=' . $estado['pendientes_ia'],
    ), $estado));
}

if ($paso === 'ia_plan') {
    $pob_map = migracion_cargar_mapa_poblaciones($mysqli);
    $plan = migracion_poblaciones_obtener_pendientes_ia($mysqli, $pob_map, MIGRACION_POBLACIONES_LOTE_IA);
    $mysqli->close();
    migracion_responder_paso($json, true, array(
        'paso'       => 'ia_plan',
        'pendientes' => $plan['pendientes'],
        'lotes'      => $plan['lotes'],
        'message'    => 'Lotes IA: ' . count($plan['lotes']),
    ));
}

if ($paso === '1') {
    migracion_refrescar_sesion();
    $desde_id = isset($_GET['desde_id']) ? (int) $_GET['desde_id'] : 0;
    migracion_flush_salida('Paso 1 lote desde_id=' . $desde_id . '...');
    $normalizado = migracion_normalizar_c_poblacion_direcciones(
        $mysqli,
        $desde_id,
        MIGRACION_POBLACIONES_LOTE_NORMALIZAR
    );
    if (!empty($normalizado['errores'])) {
        $mysqli->close();
        migracion_responder_paso($json, false, array(
            'message' => implode('; ', $normalizado['errores']),
        ));
    }
    $mysqli->close();
    migracion_responder_paso($json, true, array(
        'paso'      => '1',
        'filas'     => $normalizado['filas'],
        'desde_id'  => $desde_id,
        'ultimo_id' => $normalizado['ultimo_id'],
        'hay_mas'   => $normalizado['hay_mas'],
        'leidas'    => $normalizado['leidas'],
        'message'   => 'Paso 1 lote OK. Normalizadas: ' . $normalizado['filas']
            . ' | leidas: ' . $normalizado['leidas']
            . ($normalizado['hay_mas'] ? ' | hay más' : ' | fin paso 1'),
    ));
}

if ($paso === '2') {
    migracion_refrescar_sesion();
    migracion_flush_salida('Paso 2: match directo...');

    $pob_map = migracion_cargar_mapa_poblaciones($mysqli);
    if (empty($pob_map)) {
        $mysqli->close();
        migracion_responder_paso($json, false, array('message' => 'No se pudo cargar la tabla poblacion.'));
    }

    $items = migracion_obtener_poblaciones_distintas_direcciones($mysqli);
    $clasificacion = migracion_clasificar_poblaciones($items, $pob_map);
    $filasDirecto = 0;
    $err = 0;

    if (!empty($clasificacion['resueltos'])) {
        $aplicado = migracion_aplicar_poblaciones_direcciones($mysqli, $clasificacion['resueltos']);
        $filasDirecto = $aplicado['filas'];
        if (!empty($aplicado['errores'])) {
            $err++;
        }
    }

    $mysqli->close();

    if ($err > 0) {
        migracion_responder_paso($json, false, array(
            'message' => 'Errores al aplicar match directo.',
            'filas'   => $filasDirecto,
            'pendientes_ia' => count($clasificacion['sin_mapear']),
        ));
    }

    migracion_responder_paso($json, true, array(
        'paso'          => '2',
        'filas'         => $filasDirecto,
        'match_directo' => count($clasificacion['resueltos']),
        'pendientes_ia' => count($clasificacion['sin_mapear']),
        'message'       => 'Paso 2 OK. Filas actualizadas: ' . $filasDirecto
            . ' | Pendientes IA: ' . count($clasificacion['sin_mapear']),
    ));
}

if ($paso === 'ia') {
    $idProvincia = isset($_GET['provincia']) ? (int) $_GET['provincia'] : 0;
    $idx = isset($_GET['idx']) ? (int) $_GET['idx'] : -1;

    if ($idProvincia <= 0 || $idx < 0) {
        $mysqli->close();
        migracion_responder_paso($json, false, array('message' => 'Parámetros ia inválidos (provincia, idx).'));
    }

    migracion_refrescar_sesion();
    migracion_flush_salida("IA provincia {$idProvincia} lote {$idx}...");

    $pob_map = migracion_cargar_mapa_poblaciones($mysqli);
    $datosLote = migracion_poblaciones_obtener_lote_ia(
        $mysqli,
        $pob_map,
        $idProvincia,
        $idx,
        MIGRACION_POBLACIONES_LOTE_IA
    );
    $lote = $datosLote['lote'];

    if (empty($lote)) {
        $mysqli->close();
        migracion_responder_paso($json, true, array(
            'paso'     => 'ia',
            'provincia'=> $idProvincia,
            'idx'      => $idx,
            'filas'    => 0,
            'resueltos'=> 0,
            'message'  => 'Lote vacío (ya resuelto o no existe).',
        ));
    }

    $sugerencias = migracion_ia_mapear_poblaciones_lote($lote, $pob_map);
    if ($sugerencias === false) {
        $mysqli->close();
        migracion_responder_paso($json, false, array(
            'message' => 'Claude no devolvió JSON válido (provincia ' . $idProvincia . ').',
        ));
    }

    $resueltosIa = migracion_ia_validar_poblaciones($sugerencias, $pob_map, $lote);
    $filasIa = 0;
    if (!empty($resueltosIa)) {
        $aplicado = migracion_aplicar_poblaciones_direcciones($mysqli, $resueltosIa);
        $filasIa = $aplicado['filas'];
    }

    $mysqli->close();
    migracion_responder_paso($json, true, array(
        'paso'      => 'ia',
        'provincia' => $idProvincia,
        'idx'       => $idx,
        'filas'     => $filasIa,
        'resueltos' => count($resueltosIa),
        'message'   => 'IA OK provincia ' . $idProvincia . ' lote ' . $idx
            . '. Filas: ' . $filasIa,
    ));
}

if ($paso === '3') {
    migracion_refrescar_sesion();
    migracion_flush_salida('Paso 3: asignar rel_id_poblacion y codigo_postal...');

    $asignacion = migracion_asignar_poblaciones_restantes($mysqli);
    if (!empty($asignacion['errores'])) {
        $mysqli->close();
        migracion_responder_paso($json, false, array(
            'message' => implode('; ', $asignacion['errores']),
        ));
    }

    $mysqli->close();
    migracion_responder_paso($json, true, array(
        'paso'       => '3',
        'filas'      => $asignacion['filas'],
        'sin_mapear' => $asignacion['sin_mapear'],
        'message'    => 'Paso 3 OK. Filas asignadas: ' . $asignacion['filas']
            . ' | Sin mapear: ' . $asignacion['sin_mapear'],
    ));
}

if ($paso !== '') {
    $mysqli->close();
    migracion_responder_paso($json, false, array('message' => 'Paso desconocido: ' . $paso));
}

// Sin ?paso= : instrucciones (no ejecutar todo de una vez desde el navegador)
$mysqli->close();
echo "Use ejecución por pasos desde el panel de migraciones.\n";
echo "Pasos: 1 (normalizar), 2 (directo), ia_plan + ia, 3 (asignar ids).\n";
