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
    
    // Obtener itemsSections que deben aparecer en el menú (misma consulta que en menu.php)
    $query_menu = "SELECT id_type_Item, itemName, typ_item, url_item, icon_menu, itemnameText FROM itemsSections 
                  WHERE in_menu = 'true' AND fhater_menu = 0 AND state_item = 'true'
                  ORDER BY position_menu ASC";
    $resultado_menu = mysqli_query($conexion, $query_menu);
    
    if (!$resultado_menu) {
        throw new Exception('Error en la consulta del menú: ' . mysqli_error($conexion));
    }
    
    $menu_html = '';
    
    if (mysqli_num_rows($resultado_menu) > 0) {
        while ($item = mysqli_fetch_assoc($resultado_menu)) {
            // Verificar si el usuario tiene acceso a este item según su jerarquía
            if (usuario_puede_acceder_item($_SESSION['usuario_privilegio_id'], $item['id_type_Item'])) {
                
                $type_item_parset = $item['typ_item'];
                $itemName_parset = $item['itemName'];
                $url_item_parset = $item['url_item'];
                $icon_menu_parset = $item['icon_menu'];
                $id_type_Item_parset = $item['id_type_Item'];
                $itemnameText_parset = ucfirst($item['itemnameText']);
                
                if($type_item_parset == "menu"){ 
                    $menu_html .= '<li class="menu-item">
                        <a href="javascript:void(0);" class="menu-link menu-toggle">
                          <i class="menu-icon icon-base ri ri-'.$icon_menu_parset.'"></i>
                          <div data-i18n="'.$itemnameText_parset.'">'.$itemnameText_parset.'</div>
                        </a>
                        <ul class="menu-sub">';

                    $query_submenu = "SELECT id_type_Item, itemName, typ_item, url_item, icon_menu, itemnameText 
                                    FROM itemsSections 
                                    WHERE state_item = 'true' AND in_menu = 'true' AND fhater_menu = ? 
                                    ORDER BY position_menu ASC";
                    $stmt_submenu = mysqli_prepare($conexion, $query_submenu);
                    mysqli_stmt_bind_param($stmt_submenu, "i", $id_type_Item_parset);
                    mysqli_stmt_execute($stmt_submenu);
                    $resultado_submenu = mysqli_stmt_get_result($stmt_submenu);
                    
                    if ($resultado_submenu && mysqli_num_rows($resultado_submenu) > 0) {
                        while ($subitem = mysqli_fetch_assoc($resultado_submenu)) {
                            // Verificar si el usuario tiene acceso a este subitem según su jerarquía
                            if (usuario_puede_acceder_item($_SESSION['usuario_privilegio_id'], $subitem['id_type_Item'])) {
                                $subitemName = $subitem['itemName'];
                                $suburl_item = $subitem['url_item'];
                                $subicon_menu = $subitem['icon_menu'];
                                $subitemnameText = ucfirst($subitem['itemnameText']);
                                $menu_html .= '<li class="menu-item">
                                    <a href="'.$suburl_item.'.php" class="menu-link">
                                        <div data-i18n="'.$subitemnameText.'">'.$subitemnameText.'</div>
                                    </a>
                                </li>';
                            }
                        }
                    }
                    $menu_html .= '</ul>
                    </li>';
                } else {  
                    $menu_html .= '<li class="menu-item ">
                    <a href="'.$url_item_parset.'.php" class="menu-link">
                      <i class="menu-icon icon-base ri ri-'.$icon_menu_parset.'"></i>
                      <div data-i18n="'.$itemnameText_parset.'">'.$itemnameText_parset.'</div>
                    </a>
                  </li>';
                }
            } // Cerrar el if de verificación de acceso
        }
    }
    
    // Cerrar conexión
    mysqli_close($conexion);
    
    // Devolver respuesta JSON
    header('Content-Type: application/json');
    echo json_encode([
        'success' => true,
        'menu_html' => $menu_html
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
