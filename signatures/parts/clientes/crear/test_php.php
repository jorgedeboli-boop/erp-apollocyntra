<?php
// Test simple del archivo AJAX
$_GET['action'] = 'paises';
$_GET['search'] = '';
$_GET['page'] = 1;

echo "=== TEST PHP ===\n";
echo "Action: " . $_GET['action'] . "\n";
echo "Search: " . $_GET['search'] . "\n";
echo "Page: " . $_GET['page'] . "\n";

// Incluir el archivo AJAX
include 'ajax_poblaciones.php';
?>
