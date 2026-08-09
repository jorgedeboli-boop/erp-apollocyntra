<?php
// Verificar que el usuario esté autenticado
require_once '../../../include/session.php';

// Verificar que sea una petición GET
if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Método no permitido']);
    exit();
}

// Obtener ID de la jerarquía
$id_jerarquia = isset($_GET['id_jerarquia']) ? (int)$_GET['id_jerarquia'] : 0;

if ($id_jerarquia <= 0) {
    echo json_encode(['success' => false, 'error' => 'ID de jerarquía no válido']);
    exit();
}

// Incluir funciones de base de datos
require_once '../../../include/functions.php';

try {
    $conexion = conectar_bd();
    
    $es_usuario_root = (isset($usuario_root) && $usuario_root === 'true');

    $columnas_section = [
        'sucursal_section',
        'central_section',
        'recepcion_lotes_section',
        'auditoria_section',
    ];

    $sucursal_section_jerarquia = 'false';
    $central_section_jerarquia = 'false';
    $recepcion_lotes_section_jerarquia = 'false';
    $auditoria_section_jerarquia = 'false';

    $query_jerarquia = "SELECT sucursal_section, central_section, recepcion_lotes_section, auditoria_section FROM privilegios_usuarios WHERE id_privilegios = ?";
    $stmt_jerarquia = mysqli_prepare($conexion, $query_jerarquia);

    if (!$stmt_jerarquia) {
        throw new Exception('Error al preparar consulta de jerarquía: ' . mysqli_error($conexion));
    }

    mysqli_stmt_bind_param($stmt_jerarquia, 'i', $id_jerarquia);
    mysqli_stmt_execute($stmt_jerarquia);
    mysqli_stmt_bind_result(
        $stmt_jerarquia,
        $sucursal_section_jerarquia,
        $central_section_jerarquia,
        $recepcion_lotes_section_jerarquia,
        $auditoria_section_jerarquia
    );
    $jerarquia_encontrada = mysqli_stmt_fetch($stmt_jerarquia);
    mysqli_stmt_close($stmt_jerarquia);

    if (!$jerarquia_encontrada) {
        throw new Exception('Jerarquía no encontrada');
    }

    $jerarquia_sections = [
        'sucursal_section' => ($sucursal_section_jerarquia === 'true') ? 'true' : 'false',
        'central_section' => ($central_section_jerarquia === 'true') ? 'true' : 'false',
        'recepcion_lotes_section' => ($recepcion_lotes_section_jerarquia === 'true') ? 'true' : 'false',
        'auditoria_section' => ($auditoria_section_jerarquia === 'true') ? 'true' : 'false',
    ];

    $section_activa = null;
    foreach ($columnas_section as $columna) {
        if ($jerarquia_sections[$columna] === 'true') {
            $section_activa = $columna;
            break;
        }
    }

    if ($section_activa === null) {
        $section_activa = 'central_section';
        $jerarquia_sections['central_section'] = 'true';
    }

    $items_excluidos = array(
        'root',
        'items',
        'items_positions',
        'binlist',
        'migracion',
        'importaciones',
        'languages',
    );

    // 1. Obtener items del sistema (items internos solo visibles para usuario_root)
    $query_items = "SELECT id_type_Item, itemName, typ_item, url_item, itemnameText, fhater_item FROM itemsSections";
    if (!$es_usuario_root) {
        $query_items .= " WHERE item_root = 'false' AND `" . $section_activa . "` = 'true'";
    }
    $query_items .= " ORDER BY itemName";
    $resultado_items = mysqli_query($conexion, $query_items);
    
    if (!$resultado_items) {
        throw new Exception('Error al obtener items: ' . mysqli_error($conexion));
    }
    
    $items = [];
    $ids_visibles = [];
    while ($row = mysqli_fetch_assoc($resultado_items)) {
        $nombreItem = isset($row['itemName']) ? strtolower(trim($row['itemName'])) : '';
        if (!$es_usuario_root && in_array($nombreItem, $items_excluidos, true)) {
            continue;
        }
        $items[] = $row;
        $ids_visibles[] = (int) $row['id_type_Item'];
    }
    
    // 2. Obtener permisos actuales de esta jerarquía
    $query_permisos = "SELECT relIdItems FROM relItemsLevel WHERE relIdUsersLevel = ?";
    $stmt = mysqli_prepare($conexion, $query_permisos);
    
    if (!$stmt) {
        throw new Exception('Error al preparar consulta de permisos: ' . mysqli_error($conexion));
    }
    
    mysqli_stmt_bind_param($stmt, "i", $id_jerarquia);
    mysqli_stmt_execute($stmt);
    
    // Compatible con PHP 7.0
    mysqli_stmt_store_result($stmt);
    mysqli_stmt_bind_result($stmt, $relIdItems);
    
    $permisos_actuales = [];
    while (mysqli_stmt_fetch($stmt)) {
        $idItem = (int) $relIdItems;
        if ($es_usuario_root || in_array($idItem, $ids_visibles, true)) {
            $permisos_actuales[] = $idItem;
        }
    }
    
    mysqli_stmt_close($stmt);
    mysqli_close($conexion);
    
    // Debug: log de datos
    error_log("ID Jerarquía: " . $id_jerarquia);
    error_log("Section activa jerarquía: " . $section_activa);
    error_log("Items encontrados: " . count($items));
    error_log("Permisos actuales: " . implode(', ', $permisos_actuales));
    
    // Respuesta exitosa
    echo json_encode([
        'success' => true,
        'items' => $items,
        'permisos_actuales' => $permisos_actuales,
        'jerarquia_sections' => $jerarquia_sections,
        'section_activa' => $section_activa
    ]);
    
} catch (Exception $e) {
    // Log del error
    error_log("Error en get_items_permisos: " . $e->getMessage());
    
    echo json_encode([
        'success' => false, 
        'error' => 'Error interno del servidor: ' . $e->getMessage()
    ]);
}
?>
