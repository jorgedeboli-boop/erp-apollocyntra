<?php
/**
 * Corregir nombres de provincia en direcciones (match directo + IA Claude).
 * Código: corregir_nombres_provincias_poblacion
 */
require_once __DIR__ . '/../../../include/session.php';
require_once __DIR__ . '/../../../include/functions.php';
require_once __DIR__ . '/migrar_provincias_helpers.php';
require_once __DIR__ . '/migracion_runtime_helpers.php';

header('Content-Type: text/plain; charset=utf-8');

migracion_preparar_ejecucion_larga();

const MIGRACION_PAIS_ESPANIA_ID = 68;
const MIGRACION_PROVINCIAS_LOTE_IA = 25;

$mysqli = conectar_bd();
if (!$mysqli) {
    echo "ERROR: No se pudo conectar a la base de datos.\n";
    exit(1);
}

foreach (array('direcciones', 'provincias') as $tabla) {
    $chk = mysqli_query($mysqli, "SHOW TABLES LIKE '{$tabla}'");
    if (!$chk || mysqli_num_rows($chk) === 0) {
        if ($chk) {
            mysqli_free_result($chk);
        }
        $mysqli->close();
        echo "ERROR: La tabla {$tabla} no existe.\n";
        exit(1);
    }
    mysqli_free_result($chk);
}

echo "=== CORREGIR PROVINCIAS (direcciones) ===\n\n";
echo "--- Paso 1: normalizar c_provincia (ZARAGOZA → Zaragoza, VIZCAYA → Vizcaya) ---\n";
$normalizado = migracion_normalizar_c_provincia_direcciones($mysqli);
if (!empty($normalizado['errores'])) {
    foreach ($normalizado['errores'] as $e) {
        echo 'ERROR: ' . $e . "\n";
    }
    $mysqli->close();
    exit(1);
}
echo 'Filas normalizadas: ' . $normalizado['filas'] . "\n\n";

$prov_map = migracion_cargar_mapa_provincias($mysqli, MIGRACION_PAIS_ESPANIA_ID);
if (empty($prov_map)) {
    $mysqli->close();
    echo "ERROR: No se pudo cargar la tabla provincias.\n";
    exit(1);
}

$valores = migracion_obtener_provincias_distintas_direcciones($mysqli);
if ($valores === array() && mysqli_errno($mysqli)) {
    $mysqli->close();
    echo 'ERROR: ' . mysqli_error($mysqli) . "\n";
    exit(1);
}

$clasificacion = migracion_clasificar_provincias($valores, $prov_map);
$resueltos = $clasificacion['resueltos'];
$sin_mapear = $clasificacion['sin_mapear'];

echo "--- Paso 2: match directo e IA ---\n";
echo 'Provincias oficiales (España): ' . count($prov_map) . "\n";
echo 'Valores distintos en c_provincia: ' . count($valores) . "\n";
echo 'Match directo: ' . count($resueltos) . ' | Pendientes IA: ' . count($sin_mapear) . "\n\n";

$err = 0;
$filasDirecto = 0;
$filasIa = 0;
$iaLotes = 0;
$iaResueltos = 0;

if (!empty($resueltos)) {
    echo "--- Aplicando match directo ---\n";
    $aplicado = migracion_aplicar_provincias_direcciones($mysqli, $resueltos);
    $filasDirecto = $aplicado['filas'];
    if (!empty($aplicado['errores'])) {
        foreach ($aplicado['errores'] as $e) {
            echo 'ERROR: ' . $e . "\n";
            $err++;
        }
    }
    echo 'Filas actualizadas: ' . $filasDirecto . "\n\n";
}

$sin_mapearFinal = array();

if (!empty($sin_mapear)) {
    echo "--- Mapeo con IA (Claude) ---\n";
    $listaOficial = migracion_lista_nombres_provincias($prov_map);
    $lotes = array_chunk($sin_mapear, MIGRACION_PROVINCIAS_LOTE_IA);

    foreach ($lotes as $lote) {
        migracion_refrescar_sesion();
        $iaLotes++;
        migracion_flush_salida('Lote ' . $iaLotes . ' (' . count($lote) . ' valores)...');

        $sugerencias = migracion_ia_mapear_provincias_lote($lote, $listaOficial);
        if ($sugerencias === false) {
            echo "ERROR: Claude no devolvió JSON válido.\n";
            $err++;
            $sin_mapearFinal = array_merge($sin_mapearFinal, $lote);
            continue;
        }

        $resueltosIa = migracion_ia_validar_provincias($sugerencias, $prov_map, $lote);
        $iaResueltos += count($resueltosIa);

        if (!empty($resueltosIa)) {
            $aplicado = migracion_aplicar_provincias_direcciones($mysqli, $resueltosIa);
            $filasIa += $aplicado['filas'];
            if (!empty($aplicado['errores'])) {
                foreach ($aplicado['errores'] as $e) {
                    echo 'ERROR: ' . $e . "\n";
                    $err++;
                }
            }
        }

        foreach ($lote as $valor) {
            if (!isset($resueltosIa[$valor])) {
                $sin_mapearFinal[] = $valor;
            }
        }
    }

    echo 'Lotes IA: ' . $iaLotes . "\n";
    echo 'Valores resueltos por IA: ' . $iaResueltos . "\n";
    echo 'Filas actualizadas por IA: ' . $filasIa . "\n\n";
}

echo "=== RESUMEN ===\n";
echo 'Filas (match directo): ' . $filasDirecto . "\n";
echo 'Filas (IA):            ' . $filasIa . "\n";
echo 'Total filas:           ' . ($filasDirecto + $filasIa) . "\n";
echo 'Sin mapear:            ' . count($sin_mapearFinal) . "\n";
echo 'Errores:               ' . $err . "\n";

if (!empty($sin_mapearFinal)) {
    echo "\n=== PROVINCIAS SIN MAPEAR ===\n";
    foreach ($sin_mapearFinal as $v) {
        echo '  ' . json_encode($v, JSON_UNESCAPED_UNICODE) . "\n";
    }
}

$mysqli->close();

if ($err > 0) {
    echo "\nERROR: hubo errores en la migración\n";
    exit(1);
}

echo "\nHecho.\n";
