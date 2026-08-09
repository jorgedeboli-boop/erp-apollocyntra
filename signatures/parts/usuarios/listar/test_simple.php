<?php
/**
 * Archivo de prueba simple para verificar load_list.php
 */

require_once '../../../include/session.php';
require_once '../../../include/functions.php';

echo "<h2>Prueba Simple de load_list.php</h2>";

// Simular una petición POST simple
$_POST['draw'] = 1;
$_POST['start'] = 0;
$_POST['length'] = 10;

echo "<h3>Incluyendo load_list.php:</h3>";

// Incluir y ejecutar load_list.php
ob_start();
include 'load_list.php';
$output = ob_get_clean();

echo "<h3>Respuesta de load_list.php:</h3>";
echo "<pre>" . htmlspecialchars($output) . "</pre>";

// Verificar si es JSON válido
$json_data = json_decode($output, true);
if ($json_data === null) {
    echo "<p>❌ Error: La respuesta no es JSON válido</p>";
    echo "<p>Error JSON: " . json_last_error_msg() . "</p>";
} else {
    echo "<p>✅ JSON válido recibido</p>";
    echo "<p>Total de registros: " . $json_data['recordsTotal'] . "</p>";
    echo "<p>Datos encontrados: " . count($json_data['data']) . "</p>";
    
    if (count($json_data['data']) > 0) {
        echo "<h3>Primer usuario:</h3>";
        echo "<pre>" . print_r($json_data['data'][0], true) . "</pre>";
    }
}
?>
