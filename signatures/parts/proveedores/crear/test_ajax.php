<?php
/**
 * Archivo de prueba para verificar que ajax_poblaciones.php funciona
 */

echo "<h1>Test AJAX Poblaciones - Proveedores</h1>";

// Simular petición
$_GET['action'] = 'paises';
$_GET['search'] = '';
$_GET['page'] = 1;

echo "<h2>Simulando petición de países...</h2>";
echo "<pre>";

// Incluir el archivo
ob_start();
include 'ajax_poblaciones.php';
$output = ob_get_clean();

echo "Output: " . htmlspecialchars($output);
echo "</pre>";

// Decodificar JSON
$data = json_decode($output, true);
echo "<h2>Datos decodificados:</h2>";
echo "<pre>";
print_r($data);
echo "</pre>";

if (isset($data['results']) && count($data['results']) > 0) {
    echo "<h2 style='color: green;'>✓ ÉXITO - Se encontraron " . count($data['results']) . " países</h2>";
} else {
    echo "<h2 style='color: red;'>✗ ERROR - No se encontraron países</h2>";
}

if (isset($data['error'])) {
    echo "<h2 style='color: red;'>Error: " . $data['message'] . "</h2>";
}
?>

