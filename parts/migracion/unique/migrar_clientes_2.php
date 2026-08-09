<?php
/**
 * Normaliza clientes.nacionalidad y rellena clientes.nacionalidad_id.
 * Paso 2 tras migrar_clientes.php.
 * Orden: MAPA manual → mapeos IA aprobados → lookup directo en nacionalidades.
 */
require_once __DIR__ . '/../../../include/session.php';
require_once __DIR__ . '/../../../include/functions.php';
require_once __DIR__ . '/migrar_nacionalidades_helpers.php';

    header('Content-Type: text/plain; charset=utf-8');

set_time_limit(0);
ini_set('max_execution_time', '0');
ini_set('memory_limit', '512M');

if (function_exists('apache_setenv')) {
    @apache_setenv('no-gzip', '1');
}
@ini_set('zlib.output_compression', '0');

$MAPA = migracion_cargar_mapa_manual();

$mysqli = conectar_bd();
if (!$mysqli) {
    migrar_nacionalidades_abort('No se pudo conectar a la base de datos.');
}

$mapaAprobado = migracion_cargar_mapa_aprobado($mysqli);
if (!empty($mapaAprobado)) {
    $MAPA = array_merge($MAPA, $mapaAprobado);
}

echo "=== ARREGLAR NACIONALIDADES ===\n";
echo 'Mapeos manuales: ' . count(migracion_cargar_mapa_manual()) . "\n";
echo 'Mapeos IA aprobados: ' . count($mapaAprobado) . "\n";

$nac_map = migracion_cargar_nac_map($mysqli);
if (empty($nac_map)) {
    migrar_nacionalidades_abort('No se pudo cargar la tabla nacionalidades.', $mysqli);
}

$valores = migracion_obtener_valores_distintos_nacionalidad($mysqli);
if ($valores === array() && mysqli_errno($mysqli)) {
    migrar_nacionalidades_abort('Error al leer clientes: ' . mysqli_error($mysqli), $mysqli);
}

$clasificacion = migracion_clasificar_nacionalidades($valores, $MAPA, $nac_map);
$resueltos = $clasificacion['resueltos'];
$sin_mapear = $clasificacion['sin_mapear'];

echo "Valores distintos: " . count($valores) . "\n";
echo 'Resueltos: ' . count($resueltos) . ' | Sin mapear: ' . count($sin_mapear) . "\n";
if (!empty($sin_mapear)) {
    echo "Puede solicitar sugerencias IA desde la pantalla de migración y aprobarlas antes de repetir.\n";
}
echo "\n";

echo "=== EJECUTANDO UPDATES (nacionalidad) ===\n";
$ok = 0;
$err = 0;
$filasAfectadas = 0;

$casos_hex = array(
    '45737061c3a3c2b16f6c61' => 'Española',
    '52756d616ec3a3c2ad61'    => 'Rumana',
    '556372616ec3a3c2ad61'    => 'Ucraniana',
);

foreach ($casos_hex as $hex => $nombre_correcto) {
    $n_esc = $mysqli->real_escape_string($nombre_correcto);
    $sql = "UPDATE clientes
               SET nacionalidad = '$n_esc'
             WHERE BINARY nacionalidad = UNHEX('$hex')
               AND delete_state = 'false'";
    if ($mysqli->query($sql)) {
        $filasAfectadas += max(0, $mysqli->affected_rows);
        $ok++;
    } else {
        echo "ERROR HEX: 0x{$hex} → " . $mysqli->error . "\n";
        $err++;
    }
}

foreach ($resueltos as $valor_original => $datos) {
    $nombre_correcto = $datos[0];
    $v_esc = $mysqli->real_escape_string($valor_original);
    $n_esc = $mysqli->real_escape_string($nombre_correcto);
    if ($valor_original === $nombre_correcto) {
        continue;
    }
    $sql = "UPDATE clientes
               SET nacionalidad = '$n_esc'
             WHERE BINARY nacionalidad = '$v_esc'
               AND delete_state = 'false'";
    if ($mysqli->query($sql)) {
        $filasAfectadas += max(0, $mysqli->affected_rows);
        $ok++;
    } else {
        echo "ERROR: '" . $valor_original . "' → " . $mysqli->error . "\n";
        $err++;
    }
}

echo "\n=== ASIGNAR nacionalidad_id (SELECT todos los clientes) ===\n";
$sync = migracion_sincronizar_nacionalidad_id($mysqli, $nac_map, $MAPA);
if (!$sync['ok']) {
    echo 'ERROR al asignar nacionalidad_id: ' . $sync['error'] . "\n";
    $err++;
} else {
    echo 'Clientes revisados: ' . $sync['total'] . "\n";
    echo 'Clientes con nacionalidad_id actualizado: ' . $sync['filas'] . "\n";
    if (!empty($sync['sin_id'])) {
        echo 'Nacionalidades sin id en tabla nacionalidades (' . count($sync['sin_id']) . "):\n";
        foreach ($sync['sin_id'] as $texto => $num) {
            echo '  ' . $num . ' clientes → ' . migrar_nacionalidades_repr($texto) . "\n";
        }
    }
}

echo "\n=== RESUMEN ===\n";
echo "Updates nacionalidad OK:   {$ok}\n";
echo "Updates nacionalidad ERR:  {$err}\n";
echo "Filas texto actualizadas: {$filasAfectadas}\n";
echo 'Clientes nacionalidad_id OK: ' . ($sync['ok'] ? $sync['filas'] : 0) . "\n";
echo 'Sin mapear:   ' . count($sin_mapear) . "\n";

if ($err > 0) {
    echo "ERROR: hubo errores en la migración\n";
}

if (!empty($sin_mapear)) {
    echo "\n=== VALORES SIN MAPEAR (revisar / solicitar IA) ===\n";
    foreach ($sin_mapear as $v) {
        echo '  ' . migrar_nacionalidades_repr($v) . "\n";
    }
}

$mysqli->close();

if ($err > 0) {
    exit(1);
}

echo "\nHecho.\n";
