<?php
/**
 * Corregir y limpiar campos de la tabla direcciones.
 * Código: corregir_direcciones_clientes
 */
require_once __DIR__ . '/../../../include/session.php';
require_once __DIR__ . '/../../../include/functions.php';

header('Content-Type: text/plain; charset=utf-8');

set_time_limit(0);
ini_set('max_execution_time', '0');
ini_set('memory_limit', '512M');

if (function_exists('apache_setenv')) {
    @apache_setenv('no-gzip', '1');
}
@ini_set('zlib.output_compression', '0');

function corregir_dir_abort($mysqli, $mensaje)
{
    if ($mysqli instanceof mysqli) {
        $mysqli->close();
    }
    echo 'ERROR: ' . $mensaje . "\n";
    exit(1);
}

function corregir_dir_limpia_campo($valor)
{
    $v = preg_replace('/[\r\n?]+/', '', (string) $valor);
    return trim($v);
}

function corregir_dir_procesar_columna($mysqli, $columna, $etiqueta)
{
    $columnaPermitida = array(
        'direccion',
        'c_provincia',
        'c_poblacion',
        'c_pais',
        'codigo_postal',
    );

    if (!in_array($columna, $columnaPermitida, true)) {
        return array('ok' => false, 'filas' => 0, 'error' => 'Columna no permitida.');
    }

    $sqlSelect = "SELECT id_direcciones, `{$columna}` AS valor FROM direcciones";
    $res = mysqli_query($mysqli, $sqlSelect);
    if (!$res) {
        return array('ok' => false, 'filas' => 0, 'error' => mysqli_error($mysqli));
    }

    $sqlUpdate = "UPDATE direcciones SET `{$columna}` = ? WHERE id_direcciones = ?";
    $stmt = mysqli_prepare($mysqli, $sqlUpdate);
    if (!$stmt) {
        mysqli_free_result($res);
        return array('ok' => false, 'filas' => 0, 'error' => mysqli_error($mysqli));
    }

    $filas = 0;
    while ($row = mysqli_fetch_assoc($res)) {
        $original = (string) ($row['valor'] ?? '');
        $limpio = corregir_dir_limpia_campo($original);
        if ($limpio === $original) {
            continue;
        }

        $id = (int) $row['id_direcciones'];
        mysqli_stmt_bind_param($stmt, 'si', $limpio, $id);
        if (mysqli_stmt_execute($stmt)) {
            $filas += max(0, mysqli_stmt_affected_rows($stmt));
        }
    }

    mysqli_stmt_close($stmt);
    mysqli_free_result($res);

    return array('ok' => true, 'filas' => $filas, 'error' => null, 'etiqueta' => $etiqueta);
}

$mysqli = conectar_bd();
if (!$mysqli) {
    echo "ERROR: No se pudo conectar a la base de datos.\n";
    exit(1);
}

$chk = mysqli_query($mysqli, "SHOW TABLES LIKE 'direcciones'");
if (!$chk || mysqli_num_rows($chk) === 0) {
    if ($chk) {
        mysqli_free_result($chk);
    }
    corregir_dir_abort($mysqli, 'La tabla direcciones no existe.');
}
mysqli_free_result($chk);

$err = 0;
$total = 0;

echo "=== CORREGIR DIRECCIONES (direcciones) ===\n\n";

$pasosLimpieza = array(
    'direccion'     => 'direccion',
    'c_provincia'   => 'c_provincia',
    'c_poblacion'   => 'c_poblacion',
    'c_pais'        => 'c_pais',
    'codigo_postal' => 'codigo_postal',
);

foreach ($pasosLimpieza as $columna => $etiqueta) {
    echo "--- Paso: limpiar {$etiqueta} ---\n";
    $resultado = corregir_dir_procesar_columna($mysqli, $columna, $etiqueta);
    if (!$resultado['ok']) {
        echo 'ERROR: ' . $resultado['error'] . "\n";
        $err++;
        continue;
    }
    $total += $resultado['filas'];
    echo 'Filas actualizadas: ' . $resultado['filas'] . "\n\n";
}

echo "--- Paso: c_pais = España y rel_id_pais = 68 (todos los registros) ---\n";
$sqlPais = "UPDATE direcciones SET c_pais = 'España', rel_id_pais = 68";
if (!$mysqli->query($sqlPais)) {
    echo 'ERROR: ' . $mysqli->error . "\n";
    $err++;
} else {
    $n = max(0, (int) $mysqli->affected_rows);
    $total += $n;
    echo 'Filas actualizadas: ' . $n . "\n";
}

echo "\n=== RESUMEN ===\n";
echo 'Total filas actualizadas (suma de pasos): ' . $total . "\n";
echo 'Errores: ' . $err . "\n";

$mysqli->close();

if ($err > 0) {
    echo "ERROR: hubo errores en la migración\n";
    exit(1);
}

echo "\nHecho.\n";
