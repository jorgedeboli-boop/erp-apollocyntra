<?php
// Incluir archivos necesarios
require_once '../../../include/session.php';
require_once '../../../include/functions.php';

// Verificar que el usuario esté autenticado
if (!isset($_SESSION['usuario_id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'No autorizado']);
    exit;
}

// Verificar que sea una petición AJAX
if (!isset($_SERVER['HTTP_X_REQUESTED_WITH']) || strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) != 'xmlhttprequest') {
    http_response_code(400);
    echo json_encode(['error' => 'Petición inválida']);
    exit;
}

try {
    // Conectar a la base de datos
    $conexion = conectar_bd();
    
    if (!$conexion) {
        throw new Exception('Error de conexión a la base de datos');
    }
    
    // Consulta para obtener items
    $query = "SELECT id_type_Item, itemName, itemnameText, typ_item, fhater_item, state_item, in_menu, url_item, icon_menu, position_menu FROM itemsSections ORDER BY itemName ASC";
    $resultado = mysqli_query($conexion, $query);
    
    if (!$resultado) {
        throw new Exception('Error en la consulta: ' . mysqli_error($conexion));
    }
    
    $items = [];
    
    while ($fila = mysqli_fetch_assoc($resultado)) {
        $items[] = [
            'id' => $fila['id_type_Item'],
            'itemName' => $fila['itemName'],
            'itemnameText' => $fila['itemnameText'],
            'typ_item' => $fila['typ_item'],
            'fhater_item' => $fila['fhater_item'],
            'state_item' => $fila['state_item'],
            'in_menu' => $fila['in_menu'],
            'url_item' => $fila['url_item'],
            'icon_menu' => $fila['icon_menu'],
            'position_menu' => $fila['position_menu']
        ];
    }
    
    // Cerrar conexión
    mysqli_close($conexion);
    
    // Devolver respuesta JSON
    header('Content-Type: application/json');
    echo json_encode([
        'success' => true,
        'data' => $items,
        'total' => count($items)
    ]);
    
} catch (Exception $e) {
    // En caso de error
    if (isset($conexion)) {
        mysqli_close($conexion);
    }
    
    http_response_code(500);
    echo json_encode([
        'error' => 'Error del servidor: ' . $e->getMessage()
    ]);
}
?>
