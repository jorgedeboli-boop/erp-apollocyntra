<?php
// Archivo de debug temporal para identificar el problema
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "=== DEBUG UPDATE ITEM ===\n";
echo "POST data: " . print_r($_POST, true) . "\n";

// Verificar si existe la función conectar_bd
if (!function_exists('conectar_bd')) {
    echo "ERROR: La función conectar_bd no existe\n";
    exit;
}

// Verificar si existe la función functions.php
if (!file_exists('../../../include/functions.php')) {
    echo "ERROR: El archivo functions.php no existe\n";
    exit;
}

// Verificar si existe la función session.php
if (!file_exists('../../../include/session.php')) {
    echo "ERROR: El archivo session.php no existe\n";
    exit;
}

echo "Archivos de inclusión verificados correctamente\n";

// Intentar incluir los archivos
try {
    require_once '../../../include/session.php';
    echo "session.php incluido correctamente\n";
    
    require_once '../../../include/functions.php';
    echo "functions.php incluido correctamente\n";
    
    // Verificar conexión a BD
    $conexion = conectar_bd();
    if ($conexion) {
        echo "Conexión a BD exitosa\n";
        mysqli_close($conexion);
    } else {
        echo "ERROR: No se pudo conectar a la BD\n";
    }
    
} catch (Exception $e) {
    echo "ERROR al incluir archivos: " . $e->getMessage() . "\n";
    echo "Stack trace: " . $e->getTraceAsString() . "\n";
}

echo "=== FIN DEBUG ===\n";
?>
