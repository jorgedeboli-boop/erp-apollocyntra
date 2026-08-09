<?php
// Verificar que el usuario esté autenticado
require_once '../../../include/session.php';

// Verificar que sea una petición GET
if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Método no permitido']);
    exit();
}

// Incluir funciones de base de datos
require_once '../../../include/functions.php';

try {
    $conexion = conectar_bd();
    
    // Obtener items de tipo "menu" y activos
    $query = "SELECT id_type_Item, itemName, itemnameText FROM itemsSections 
              WHERE typ_item = 'menu' AND state_item = 'true' 
              ORDER BY itemName";
    
    $resultado = mysqli_query($conexion, $query);
    
    if (!$resultado) {
        throw new Exception('Error al obtener items de menú: ' . mysqli_error($conexion));
    }
    
    $items = [];
    while ($row = mysqli_fetch_assoc($resultado)) {
        $items[] = $row;
    }
    
    mysqli_close($conexion);
    
    // Respuesta exitosa
    echo json_encode([
        'success' => true,
        'data' => $items
    ]);
    
} catch (Exception $e) {
    // Log del error
    error_log("Error en load_items_menu: " . $e->getMessage());
    
    echo json_encode([
        'success' => false, 
        'error' => 'Error interno del servidor: ' . $e->getMessage()
    ]);
}
?>
