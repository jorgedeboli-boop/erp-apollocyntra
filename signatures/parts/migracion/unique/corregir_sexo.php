<?php
/**
 * Corregir sexo en datos_clientes.
 * Mujer → FEMENINO, Hombre/VARON → MASCULINO, vacíos → MASCULINO.
 * Código: corregir_sexo
 */
require_once __DIR__ . '/../../../include/session.php';
require_once __DIR__ . '/../../../include/functions.php';

header('Content-Type: text/plain; charset=utf-8');

set_time_limit(0);
ini_set('max_execution_time', '0');

if (function_exists('apache_setenv')) {
    @apache_setenv('no-gzip', '1');
}
@ini_set('zlib.output_compression', '0');

$mysqli = conectar_bd();
if (!$mysqli) {
    echo "ERROR: No se pudo conectar a la base de datos.\n";
    exit(1);
}

$chk = mysqli_query($mysqli, "SHOW TABLES LIKE 'datos_clientes'");
if (!$chk || mysqli_num_rows($chk) === 0) {
    if ($chk) {
        mysqli_free_result($chk);
    }
    $mysqli->close();
    echo "ERROR: La tabla datos_clientes no existe.\n";
    exit(1);
}
mysqli_free_result($chk);

echo "=== CORREGIR SEXO (datos_clientes) ===\n\n";

$pasos = array(
    array(
        'label' => "Mujer → FEMENINO",
        'sql'   => "UPDATE datos_clientes SET sexo = 'FEMENINO' WHERE sexo = 'Mujer'",
    ),
    array(
        'label' => "Hombre → MASCULINO",
        'sql'   => "UPDATE datos_clientes SET sexo = 'MASCULINO' WHERE sexo = 'Hombre'",
    ),
    array(
        'label' => "VARON → MASCULINO",
        'sql'   => "UPDATE datos_clientes SET sexo = 'MASCULINO' WHERE sexo = 'VARON'",
    ),
    array(
        'label' => 'Vacíos → MASCULINO',
        'sql'   => "UPDATE datos_clientes SET sexo = 'MASCULINO' WHERE sexo IS NULL OR TRIM(sexo) = ''",
    ),
);

$total = 0;
$err = 0;

foreach ($pasos as $paso) {
    if (!$mysqli->query($paso['sql'])) {
        echo 'ERROR (' . $paso['label'] . '): ' . $mysqli->error . "\n";
        $err++;
        continue;
    }

    $n = max(0, (int) $mysqli->affected_rows);
    $total += $n;
    echo $paso['label'] . ': ' . $n . " filas\n";
}

echo "\n=== RESUMEN ===\n";
echo 'Filas actualizadas: ' . $total . "\n";
echo 'Errores: ' . $err . "\n";

$mysqli->close();

if ($err > 0) {
    echo "ERROR: hubo errores en la migración\n";
    exit(1);
}

echo "\nHecho.\n";
