<?php
require_once '../../../include/config.php';
require_once '../../../include/functions.php';

// Verificar que la función esté disponible
if (!function_exists('conectar_bd')) {
    http_response_code(500);
    echo json_encode(['error' => 'Función conectar_bd no encontrada. Archivo functions.php no cargado correctamente.']);
    exit;
}

// Obtener conexión a la base de datos
$conexion = conectar_bd();

if (!$conexion) {
    http_response_code(500);
    echo json_encode(['error' => 'Error de conexión a la base de datos']);
    exit;
}

// Obtener término de búsqueda
$search = isset($_GET['search']) ? trim($_GET['search']) : '';

// Consulta para obtener nacionalidades filtradas por búsqueda
if (!empty($search)) {
    $query = "SELECT id, nombre_nacionalidad FROM nacionalidades 
              WHERE nombre_nacionalidad LIKE ? 
              ORDER BY nombre_nacionalidad ASC 
              LIMIT 20";
    $stmt = mysqli_prepare($conexion, $query);
    $searchTerm = '%' . $search . '%';
    mysqli_stmt_bind_param($stmt, 's', $searchTerm);
    mysqli_stmt_execute($stmt);
    $resultado = mysqli_stmt_get_result($stmt);
} else {
    // Si no hay búsqueda, devolver solo las primeras 10 para mostrar opciones iniciales
    $query = "SELECT id, nombre_nacionalidad FROM nacionalidades 
              ORDER BY nombre_nacionalidad ASC 
              LIMIT 10";
    $resultado = mysqli_query($conexion, $query);
}

if (!$resultado) {
    http_response_code(500);
    echo json_encode(['error' => 'Error en la consulta: ' . mysqli_error($conexion)]);
    exit;
}

$nacionalidades = [];
while ($row = mysqli_fetch_assoc($resultado)) {
    $nacionalidades[] = [
        'id' => $row['id'],
        'text' => $row['nombre_nacionalidad']
    ];
}

// Cerrar conexión
mysqli_close($conexion);

// Devolver JSON
header('Content-Type: application/json');
echo json_encode($nacionalidades);
?>
