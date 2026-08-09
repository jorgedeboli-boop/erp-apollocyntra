<?php
/**
 * Solicita sugerencias Claude para nacionalidades sin mapear y las guarda como pendientes.
 */
require_once __DIR__ . '/../../../include/session.php';
require_once __DIR__ . '/../../../include/functions.php';
require_once __DIR__ . '/migrar_nacionalidades_helpers.php';

header('Content-Type: application/json; charset=utf-8');

session_write_close();
set_time_limit(0);
ini_set('max_execution_time', '0');

$mysqli = conectar_bd();
if (!$mysqli) {
    echo json_encode(array('success' => false, 'message' => 'No se pudo conectar a la base de datos.'));
    exit;
}

if (!migracion_nacionalidades_tabla_existe($mysqli)) {
    mysqli_close($mysqli);
    echo json_encode(array(
        'success' => false,
        'message' => 'Crea la tabla migraciones_nacionalidades_mapeo (SQL en parts/migracion/unique/migraciones_nacionalidades_mapeo.sql).'
    ));
    exit;
}

$MAPA = migracion_cargar_mapa_manual();
$mapaAprobado = migracion_cargar_mapa_aprobado($mysqli);
if (!empty($mapaAprobado)) {
    $MAPA = array_merge($MAPA, $mapaAprobado);
}

$nac_map = migracion_cargar_nac_map($mysqli);
$usuarioId = isset($usuario_id) ? (int) $usuario_id : 0;

$resultado = migracion_ia_procesar_sin_mapear($mysqli, $MAPA, $nac_map, $usuarioId, 25);
$pendientes = migracion_ia_listar($mysqli, 'pendiente');

mysqli_close($mysqli);

$msg = 'Sugerencias generadas: ' . (int) $resultado['insertados']
    . '. Pendientes en revisión: ' . count($pendientes) . '.';

if (!empty($resultado['errores'])) {
    $msg .= ' Avisos: ' . implode(' ', $resultado['errores']);
}

echo json_encode(array(
    'success' => empty($resultado['errores']) || $resultado['insertados'] > 0,
    'message' => $msg,
    'resultado' => $resultado,
    'pendientes' => count($pendientes),
));
