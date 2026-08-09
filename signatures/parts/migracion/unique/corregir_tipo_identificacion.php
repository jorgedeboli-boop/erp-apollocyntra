<?php
/**
 * Corregir tipo_identificacion, tipo_identificacion_id y limpiar campos en clientes.
 * Código: corregir_tipo_identificacion
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

function corregir_id_abort($mysqli, $mensaje)
{
    if ($mysqli instanceof mysqli) {
        $mysqli->close();
    }
    echo 'ERROR: ' . $mensaje . "\n";
    exit(1);
}

function corregir_id_limpia_identificacion($valor)
{
    return preg_replace('/\s+/u', '', (string) $valor);
}

function corregir_id_limpia_nombre_apellido($valor)
{
    $v = preg_replace('/[\r\n?]+/', '', (string) $valor);
    return trim($v);
}

function corregir_id_limpia_telefono($valor)
{
    $v = preg_replace('/[\r\n?\s]+/', '', (string) $valor);
    return preg_replace('/[a-zA-ZáéíóúÁÉÍÓÚñÑüÜ]/u', '', $v);
}

$mysqli = conectar_bd();
if (!$mysqli) {
    echo "ERROR: No se pudo conectar a la base de datos.\n";
    exit(1);
}

$chk = mysqli_query($mysqli, "SHOW TABLES LIKE 'clientes'");
if (!$chk || mysqli_num_rows($chk) === 0) {
    if ($chk) {
        mysqli_free_result($chk);
    }
    corregir_id_abort($mysqli, 'La tabla clientes no existe.');
}
mysqli_free_result($chk);

$err = 0;
$total = 0;

echo "=== CORREGIR TIPO IDENTIFICACIÓN (clientes) ===\n\n";

echo "--- Paso 1: pasaporte + nacionalidad_id <> 54 → otros ---\n";
$sql1 = "UPDATE clientes
            SET tipo_identificacion = 'otros'
          WHERE LOWER(TRIM(tipo_identificacion)) = 'pasaporte'
            AND COALESCE(nacionalidad_id, 0) <> 54";
if (!$mysqli->query($sql1)) {
    echo 'ERROR: ' . $mysqli->error . "\n";
    $err++;
} else {
    $n = max(0, (int) $mysqli->affected_rows);
    $total += $n;
    echo 'Filas actualizadas: ' . $n . "\n";
}

echo "\n--- Paso 2: tipo_identificacion_id según tipo_identificacion ---\n";
$mapaIds = array(
    'pasaporte' => 3,
    'dni'       => 1,
    'nie'       => 2,
    'cif'       => 4,
    'otros'     => 5,
);

foreach ($mapaIds as $tipo => $idTipo) {
    $tipoEsc = $mysqli->real_escape_string($tipo);
    $idTipo = (int) $idTipo;
    $sql = "UPDATE clientes
               SET tipo_identificacion_id = {$idTipo}
             WHERE LOWER(TRIM(tipo_identificacion)) = '{$tipoEsc}'
               AND (tipo_identificacion_id IS NULL OR tipo_identificacion_id <> {$idTipo})";
    if (!$mysqli->query($sql)) {
        echo "ERROR ({$tipo} → {$idTipo}): " . $mysqli->error . "\n";
        $err++;
        continue;
    }
    $n = max(0, (int) $mysqli->affected_rows);
    $total += $n;
    echo "{$tipo} → id {$idTipo}: {$n} filas\n";
}

echo "\n--- Paso 3: limpiar identificacion (sin espacios ni saltos de línea) ---\n";
$res = mysqli_query($mysqli, 'SELECT id_cliente, identificacion FROM clientes');
if (!$res) {
    echo 'ERROR: ' . $mysqli->error . "\n";
    $err++;
} else {
    $stmt = mysqli_prepare($mysqli, 'UPDATE clientes SET identificacion = ? WHERE id_cliente = ?');
    $n = 0;
    while ($row = mysqli_fetch_assoc($res)) {
        $original = (string) ($row['identificacion'] ?? '');
        $limpio = corregir_id_limpia_identificacion($original);
        if ($limpio === $original) {
            continue;
        }
        $id = (int) $row['id_cliente'];
        mysqli_stmt_bind_param($stmt, 'si', $limpio, $id);
        if (mysqli_stmt_execute($stmt)) {
            $n += max(0, mysqli_stmt_affected_rows($stmt));
        }
    }
    mysqli_stmt_close($stmt);
    mysqli_free_result($res);
    $total += $n;
    echo 'Filas actualizadas: ' . $n . "\n";
}

echo "\n--- Paso 4: limpiar nombre y apellido (trim extremos, sin ? ni saltos) ---\n";
$res = mysqli_query($mysqli, 'SELECT id_cliente, nombre, apellido FROM clientes');
if (!$res) {
    echo 'ERROR: ' . $mysqli->error . "\n";
    $err++;
} else {
    $stmt = mysqli_prepare($mysqli, 'UPDATE clientes SET nombre = ?, apellido = ? WHERE id_cliente = ?');
    $n = 0;
    while ($row = mysqli_fetch_assoc($res)) {
        $nombreOrig = (string) ($row['nombre'] ?? '');
        $apellidoOrig = (string) ($row['apellido'] ?? '');
        $nombre = corregir_id_limpia_nombre_apellido($nombreOrig);
        $apellido = corregir_id_limpia_nombre_apellido($apellidoOrig);
        if ($nombre === $nombreOrig && $apellido === $apellidoOrig) {
            continue;
        }
        $id = (int) $row['id_cliente'];
        mysqli_stmt_bind_param($stmt, 'ssi', $nombre, $apellido, $id);
        if (mysqli_stmt_execute($stmt)) {
            $n += max(0, mysqli_stmt_affected_rows($stmt));
        }
    }
    mysqli_stmt_close($stmt);
    mysqli_free_result($res);
    $total += $n;
    echo 'Filas actualizadas: ' . $n . "\n";
}

echo "\n--- Paso 5: limpiar telefono (sin espacios, ?, saltos ni letras) ---\n";
$res = mysqli_query($mysqli, 'SELECT id_cliente, telefono FROM clientes');
if (!$res) {
    echo 'ERROR: ' . $mysqli->error . "\n";
    $err++;
} else {
    $stmt = mysqli_prepare($mysqli, 'UPDATE clientes SET telefono = ? WHERE id_cliente = ?');
    $n = 0;
    while ($row = mysqli_fetch_assoc($res)) {
        $original = (string) ($row['telefono'] ?? '');
        $limpio = corregir_id_limpia_telefono($original);
        if ($limpio === $original) {
            continue;
        }
        $id = (int) $row['id_cliente'];
        mysqli_stmt_bind_param($stmt, 'si', $limpio, $id);
        if (mysqli_stmt_execute($stmt)) {
            $n += max(0, mysqli_stmt_affected_rows($stmt));
        }
    }
    mysqli_stmt_close($stmt);
    mysqli_free_result($res);
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
