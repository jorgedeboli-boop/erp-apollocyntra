<?php
require_once '../../../include/session.php';

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Método no permitido']);
    exit();
}

$id_jerarquia = isset($_GET['id_jerarquia']) ? (int)$_GET['id_jerarquia'] : 0;
$id_usuario = isset($_GET['id_usuario']) ? (int)$_GET['id_usuario'] : 0;

if ($id_jerarquia <= 0) {
    echo json_encode(['success' => false, 'error' => 'ID de jerarquía no válido']);
    exit();
}

if ($id_usuario <= 0) {
    echo json_encode(['success' => false, 'error' => 'ID de usuario no válido']);
    exit();
}

require_once '../../../include/functions.php';

try {
    $conexion = conectar_bd();

    $es_usuario_root = (isset($usuario_root) && $usuario_root === 'true');

    $columnas_section = [
        'central_section',
        'recepcion_lotes_section',
        'auditoria_section',
    ];

    $central_section_jerarquia = 'false';
    $recepcion_lotes_section_jerarquia = 'false';
    $auditoria_section_jerarquia = 'false';

    $query_jerarquia = "SELECT central_section, recepcion_lotes_section, auditoria_section FROM privilegios_usuarios WHERE id_privilegios = ?";
    $stmt_jerarquia = mysqli_prepare($conexion, $query_jerarquia);

    if (!$stmt_jerarquia) {
        throw new Exception('Error al preparar consulta de jerarquía: ' . mysqli_error($conexion));
    }

    mysqli_stmt_bind_param($stmt_jerarquia, 'i', $id_jerarquia);
    mysqli_stmt_execute($stmt_jerarquia);
    mysqli_stmt_bind_result(
        $stmt_jerarquia,
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

    $query_permisos = "SELECT relIdItems FROM relItemsLevel WHERE relIdUsersLevel = ?";
    $stmt = mysqli_prepare($conexion, $query_permisos);

    if (!$stmt) {
        throw new Exception('Error al preparar consulta de permisos: ' . mysqli_error($conexion));
    }

    mysqli_stmt_bind_param($stmt, "i", $id_jerarquia);
    mysqli_stmt_execute($stmt);

    mysqli_stmt_store_result($stmt);
    mysqli_stmt_bind_result($stmt, $relIdItems);

    $permisos_jerarquia = [];
    $query_permisos = "SELECT relIdItems FROM relItemsLevel WHERE relIdUsersLevel = ?";
    $stmt = mysqli_prepare($conexion, $query_permisos);

    if (!$stmt) {
        throw new Exception('Error al preparar consulta de permisos: ' . mysqli_error($conexion));
    }

    mysqli_stmt_bind_param($stmt, "i", $id_jerarquia);
    mysqli_stmt_execute($stmt);

    mysqli_stmt_store_result($stmt);
    mysqli_stmt_bind_result($stmt, $relIdItems);

    while (mysqli_stmt_fetch($stmt)) {
        $idItem = (int) $relIdItems;
        if ($es_usuario_root || in_array($idItem, $ids_visibles, true)) {
            $permisos_jerarquia[] = $idItem;
        }
    }

    mysqli_stmt_close($stmt);

    $permisos_usuario = [];
    $permisos_solo_usuario = [];
    $query_permisos_usuario = "
        SELECT u.relIdItems, l.relIdItems AS en_jerarquia
        FROM relItemsUser u
        LEFT JOIN relItemsLevel l ON l.relIdUsersLevel = ? AND l.relIdItems = u.relIdItems
        WHERE u.relIdUser = ?
    ";
    $stmt_usuario = mysqli_prepare($conexion, $query_permisos_usuario);

    if (!$stmt_usuario) {
        throw new Exception('Error al preparar consulta de permisos de usuario: ' . mysqli_error($conexion));
    }

    mysqli_stmt_bind_param($stmt_usuario, 'ii', $id_jerarquia, $id_usuario);
    mysqli_stmt_execute($stmt_usuario);
    mysqli_stmt_store_result($stmt_usuario);
    mysqli_stmt_bind_result($stmt_usuario, $relIdItemsUsuario, $enJerarquia);

    $ids_items_existentes = [];
    foreach ($items as $item) {
        $ids_items_existentes[(int) $item['id_type_Item']] = true;
    }

    while (mysqli_stmt_fetch($stmt_usuario)) {
        $idItem = (int) $relIdItemsUsuario;
        if (!$es_usuario_root && !in_array($idItem, $ids_visibles, true)) {
            continue;
        }

        $permisos_usuario[] = $idItem;

        if ($enJerarquia === null) {
            $permisos_solo_usuario[] = $idItem;

            if (!isset($ids_items_existentes[$idItem])) {
                $stmt_item_extra = mysqli_prepare(
                    $conexion,
                    'SELECT id_type_Item, itemName, typ_item, url_item, itemnameText, fhater_item FROM itemsSections WHERE id_type_Item = ?'
                );
                if ($stmt_item_extra) {
                    mysqli_stmt_bind_param($stmt_item_extra, 'i', $idItem);
                    mysqli_stmt_execute($stmt_item_extra);
                    $result_item_extra = mysqli_stmt_get_result($stmt_item_extra);
                    $item_extra = mysqli_fetch_assoc($result_item_extra);
                    mysqli_stmt_close($stmt_item_extra);

                    if ($item_extra) {
                        $nombreItemExtra = isset($item_extra['itemName']) ? strtolower(trim($item_extra['itemName'])) : '';
                        if ($es_usuario_root || !in_array($nombreItemExtra, $items_excluidos, true)) {
                            $items[] = $item_extra;
                            $ids_items_existentes[$idItem] = true;
                        }
                    }
                }
            }
        }
    }

    mysqli_stmt_close($stmt_usuario);

    $elementos_dom = [];
    $query_elementos = '
        SELECT edl.id_element, edl.id_dom_element, edl.name_text_element, edl.state_element_rel,
               edl.rel_id_type_Item, i.url_item, i.id_type_Item
        FROM elementsDomLevels edl
        INNER JOIN itemsSections i ON i.id_type_Item = edl.rel_id_type_Item
    ';
    if (!$es_usuario_root) {
        $query_elementos .= ' WHERE i.`' . $section_activa . '` = \'true\'';
    }
    $query_elementos .= ' ORDER BY i.url_item, edl.name_text_element, edl.id_dom_element';

    $resultado_elementos = mysqli_query($conexion, $query_elementos);
    if (!$resultado_elementos) {
        throw new Exception('Error al obtener elementos DOM: ' . mysqli_error($conexion));
    }

    while ($row_elemento = mysqli_fetch_assoc($resultado_elementos)) {
        $elementos_dom[] = [
            'id_element' => (int) $row_elemento['id_element'],
            'id_dom_element' => (string) $row_elemento['id_dom_element'],
            'name_text_element' => (string) ($row_elemento['name_text_element'] ?? ''),
            'state_element_rel' => (string) $row_elemento['state_element_rel'],
            'rel_id_type_Item' => (int) $row_elemento['rel_id_type_Item'],
            'url_item' => (string) ($row_elemento['url_item'] ?? ''),
            'id_type_Item' => (int) $row_elemento['id_type_Item'],
        ];
    }

    $permisos_elementos_jerarquia = [];
    $stmt_elementos = mysqli_prepare(
        $conexion,
        'SELECT rel_id_element FROM elementsRelLevelsUsers WHERE relIdUsersLevel = ?'
    );
    if (!$stmt_elementos) {
        throw new Exception('Error al preparar consulta de elementos DOM: ' . mysqli_error($conexion));
    }

    mysqli_stmt_bind_param($stmt_elementos, 'i', $id_jerarquia);
    mysqli_stmt_execute($stmt_elementos);
    mysqli_stmt_store_result($stmt_elementos);
    mysqli_stmt_bind_result($stmt_elementos, $relIdElement);

    while (mysqli_stmt_fetch($stmt_elementos)) {
        $permisos_elementos_jerarquia[] = (int) $relIdElement;
    }

    mysqli_stmt_close($stmt_elementos);

    $permisos_elementos_usuario = [];
    $permisos_elementos_solo_usuario = [];
    $stmt_elementos_usuario = mysqli_prepare(
        $conexion,
        'SELECT u.rel_id_element, l.rel_id_element AS en_jerarquia
         FROM elementsRelUsers u
         LEFT JOIN elementsRelLevelsUsers l
           ON l.relIdUsersLevel = ? AND l.rel_id_element = u.rel_id_element
         WHERE u.relIdUser = ?'
    );
    if (!$stmt_elementos_usuario) {
        throw new Exception('Error al preparar consulta de elementos DOM de usuario: ' . mysqli_error($conexion));
    }

    mysqli_stmt_bind_param($stmt_elementos_usuario, 'ii', $id_jerarquia, $id_usuario);
    mysqli_stmt_execute($stmt_elementos_usuario);
    mysqli_stmt_store_result($stmt_elementos_usuario);
    mysqli_stmt_bind_result($stmt_elementos_usuario, $relIdElementUsuario, $enJerarquiaElemento);

    while (mysqli_stmt_fetch($stmt_elementos_usuario)) {
        $idElemento = (int) $relIdElementUsuario;
        $permisos_elementos_usuario[] = $idElemento;
        if ($enJerarquiaElemento === null) {
            $permisos_elementos_solo_usuario[] = $idElemento;
        }
    }

    mysqli_stmt_close($stmt_elementos_usuario);
    mysqli_close($conexion);

    echo json_encode([
        'success' => true,
        'items' => $items,
        'elementos_dom' => $elementos_dom,
        'permisos_actuales' => $permisos_jerarquia,
        'permisos_jerarquia' => $permisos_jerarquia,
        'permisos_usuario' => $permisos_usuario,
        'permisos_solo_usuario' => $permisos_solo_usuario,
        'permisos_elementos_jerarquia' => $permisos_elementos_jerarquia,
        'permisos_elementos_usuario' => $permisos_elementos_usuario,
        'permisos_elementos_solo_usuario' => $permisos_elementos_solo_usuario,
        'jerarquia_sections' => $jerarquia_sections,
        'section_activa' => $section_activa,
    ]);
} catch (Exception $e) {
    error_log("Error en usuarios/main/get_items_permisos: " . $e->getMessage());

    echo json_encode([
        'success' => false,
        'error' => 'Error interno del servidor: ' . $e->getMessage(),
    ]);
}
