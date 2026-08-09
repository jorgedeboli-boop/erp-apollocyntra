<?php
/**
 * Archivo para obtener datos de un item específico
 * Versión corregida y probada
 */

require_once '../../../include/session.php';
require_once '../../../include/functions.php';

// Habilitar reporte de errores para debug
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Verificar que el usuario esté autenticado
if (!isset($_SESSION['usuario_id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'No autorizado']);
    exit;
}

// Verificar que sea una petición GET
if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    echo json_encode(['error' => 'Método no permitido']);
    exit;
}

// Verificar que sea una petición AJAX (más flexible)
$isAjax = false;
if (isset($_SERVER['HTTP_X_REQUESTED_WITH'])) {
    $isAjax = (strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest');
} elseif (isset($_SERVER['HTTP_ACCEPT']) && strpos($_SERVER['HTTP_ACCEPT'], 'application/json') !== false) {
    $isAjax = true;
}

if (!$isAjax) {
    http_response_code(400);
    echo json_encode(['error' => 'Petición inválida - se requiere petición AJAX']);
    exit;
}

// Obtener ID del item
$id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($id <= 0) {
    http_response_code(400);
    echo json_encode(['error' => 'ID de item inválido']);
    exit;
}

try {
    // Conectar a la base de datos
    $conexion = conectar_bd();
    
    if (!$conexion) {
        throw new Exception('Error de conexión a la base de datos');
    }
    
    // Consulta para obtener el item (usando consulta simple como en las pruebas exitosas)
    $query = "SELECT id_type_Item, itemName, itemnameText, typ_item, fhater_item, fhater_menu, state_item, in_menu, url_item, icon_menu, position_menu, tabla_mysql_name, sucursal_section, central_section, recepcion_lotes_section, auditoria_section, item_root FROM itemsSections WHERE id_type_Item = $id LIMIT 1";
    
    $result = mysqli_query($conexion, $query);
    
    if (!$result) {
        throw new Exception('Error en consulta: ' . mysqli_error($conexion));
    }
    
    $item = mysqli_fetch_assoc($result);
    
    if (!$item) {
        throw new Exception('Item no encontrado con ID: ' . $id);
    }
    
    // Cerrar conexión
    mysqli_close($conexion);
    
    // Devolver respuesta JSON exitosa
    header('Content-Type: application/json');
    echo json_encode([
        'success' => true,
        'item' => $item
    ]);
    
} catch (Exception $e) {
    // En caso de error
    if (isset($conexion)) {
        mysqli_close($conexion);
    }
    
    // Log del error para debug
    error_log('Error en get_item.php: ' . $e->getMessage());
    
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Error del servidor: ' . $e->getMessage()
    ]);
}
?>