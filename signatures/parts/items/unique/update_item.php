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

// Verificar que sea una petición POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Método no permitido']);
    exit;
}

// Verificar que sea una petición AJAX
if (!isset($_SERVER['HTTP_X_REQUESTED_WITH']) || strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) != 'xmlhttprequest') {
    http_response_code(400);
    echo json_encode(['error' => 'Petición inválida']);
    exit;
}

try {
    // Log para debugging
    error_log("=== DEBUG UPDATE ITEM ===");
    error_log("POST data: " . print_r($_POST, true));
    
    // Obtener datos del formulario de edición
    $id = trim($_POST['id'] ?? '');
    $itemnameText = trim($_POST['itemnameText'] ?? '');
    $fhater_item = trim($_POST['fhater_item'] ?? '');
    $fhater_menu = trim($_POST['fhater_menu'] ?? '0');
    $icon_menu = trim($_POST['icon_menu'] ?? '');
    $state_item = $_POST['edit_state_item'] ?? 'true';
    $in_menu = $_POST['edit_in_menu'] ?? 'true';
    $typ_item = trim($_POST['typ_item'] ?? '');
    $position_menu = trim($_POST['position_menu'] ?? '');
    $tabla_mysql_name = trim($_POST['edit_tabla_mysql_name'] ?? '');
    $section_activa = trim($_POST['section_activa'] ?? 'central_section');
    $columnas_section = ['sucursal_section', 'central_section', 'recepcion_lotes_section', 'auditoria_section'];
    if (!in_array($section_activa, $columnas_section, true)) {
        $section_activa = 'central_section';
    }
    $sucursal_section = 'false';
    $central_section = 'false';
    $recepcion_lotes_section = 'false';
    $auditoria_section = 'false';
    if ($section_activa === 'sucursal_section') {
        $sucursal_section = 'true';
    } elseif ($section_activa === 'central_section') {
        $central_section = 'true';
    } elseif ($section_activa === 'recepcion_lotes_section') {
        $recepcion_lotes_section = 'true';
    } elseif ($section_activa === 'auditoria_section') {
        $auditoria_section = 'true';
    }
    $item_root = trim($_POST['item_root'] ?? 'false');
    // Log de datos procesados
    error_log("Datos procesados para actualización:");
    error_log("id: '$id'");
    error_log("itemnameText: '$itemnameText'");
    error_log("fhater_item: '$fhater_item'");
    error_log("fhater_menu: '$fhater_menu'");
    error_log("icon_menu: '$icon_menu'");
    error_log("state_item: '$state_item'");
    error_log("in_menu: '$in_menu'");
    error_log("typ_item: '$typ_item'");
    error_log("position_menu: '$position_menu'");
    error_log("tabla_mysql_name: '$tabla_mysql_name'");
    error_log("sucursal_section: '$sucursal_section'");
    // Debug adicional para ver qué está llegando
    error_log("Valores POST recibidos:");
    error_log("edit_state_item: " . ($_POST['edit_state_item'] ?? 'NO ENCONTRADO'));
    error_log("edit_in_menu: " . ($_POST['edit_in_menu'] ?? 'NO ENCONTRADO'));
    error_log("sucursal_section: " . ($_POST['sucursal_section'] ?? 'NO ENCONTRADO'));
    // Validar datos obligatorios
    if (empty($id)) {
        throw new Exception('El ID del item es obligatorio');
    }

    if (empty($itemnameText)) {
        throw new Exception('El nombre del item a mostrar en el menú es obligatorio');
    }
    
    if (empty($typ_item)) {
        throw new Exception('El tipo de item es obligatorio');
    }
    
    // Validar que el tipo sea válido
    $tipos_validos = ['unique', 'main', 'listar', 'editar', 'crear', 'delete', 'menu', 'crud', 'acces_special', 'edit', 'blank_page'];
    if (!in_array($typ_item, $tipos_validos)) {
        throw new Exception('El tipo de item no es válido');
    }
    
    // Validar que in_menu sea válido
    if (!in_array($in_menu, ['true', 'false'])) {
        throw new Exception('El valor de in_menu no es válido');
    }
    
    // Validar que state_item sea válido
    if (!in_array($state_item, ['true', 'false'])) {
        throw new Exception('El valor de state_item no es válido');
    }

    if (!in_array($item_root, ['true', 'false'])) {
        throw new Exception('El valor de item_root no es válido');
    }
    
    // Validar position_menu si se proporciona
    if (!empty($position_menu) && (!is_numeric($position_menu) || $position_menu < 1)) {
        throw new Exception('La posición del menú debe ser un número mayor que 0');
    }
    
    // Conectar a la base de datos
    $conexion = conectar_bd();
    
    if (!$conexion) {
        throw new Exception('Error de conexión a la base de datos');
    }
    
    error_log("Conexión a BD exitosa");
    
    // Verificar que el item existe
    $query_check = "SELECT id_type_Item, itemName FROM itemsSections WHERE id_type_Item = ?";
    $stmt_check = mysqli_prepare($conexion, $query_check);
    
    if (!$stmt_check) {
        throw new Exception('Error al preparar la consulta de verificación: ' . mysqli_error($conexion));
    }
    
    mysqli_stmt_bind_param($stmt_check, 'i', $id);
    mysqli_stmt_execute($stmt_check);
    mysqli_stmt_store_result($stmt_check);
    
    if (mysqli_stmt_num_rows($stmt_check) == 0) {
        mysqli_stmt_close($stmt_check);
        throw new Exception('El item especificado no existe');
    }
    
    mysqli_stmt_bind_result($stmt_check, $id_existente, $nombre_existente);
    mysqli_stmt_fetch($stmt_check);
    mysqli_stmt_close($stmt_check);
    
    error_log("Item verificado: ID $id_existente, Nombre: $nombre_existente");
    
    // UPDATE - Actualizar solo los campos permitidos
    $query = "UPDATE itemsSections SET 
        itemnameText = ?, 
        fhater_item = ?, 
        fhater_menu = ?, 
        icon_menu = ?, 
        state_item = ?, 
        in_menu = ?, 
        position_menu = ?,
        tabla_mysql_name= ?,
        sucursal_section = ?,
        central_section = ?,
        recepcion_lotes_section = ?,
        auditoria_section = ?,
        item_root = ?
        WHERE id_type_Item = ?";
    
    error_log("Query UPDATE: $query");
    
    $stmt = mysqli_prepare($conexion, $query);
    
    if (!$stmt) {
        error_log("Error en prepare UPDATE: " . mysqli_error($conexion));
        throw new Exception('Error al preparar la consulta: ' . mysqli_error($conexion));
    }
    
    error_log("Prepare UPDATE exitoso, ejecutando bind_param");
    error_log("Parámetros para bind_param:");
    error_log("itemnameText: '$itemnameText' (string)");
    error_log("fhater_item: '$fhater_item' (string)");
    error_log("fhater_menu: '$fhater_menu' (string)");
    error_log("icon_menu: '$icon_menu' (string)");
    error_log("state_item: '$state_item' (string)");
    error_log("in_menu: '$in_menu' (string)");
    error_log("position_menu: '$position_menu' (string)");
    error_log("tabla_mysql_name: '$tabla_mysql_name' (string)");
    error_log("id: $id (integer)");
    error_log("sucursal_section: '$sucursal_section' (string)");
    mysqli_stmt_bind_param($stmt, 'sssssssssssssi', $itemnameText, $fhater_item, $fhater_menu, $icon_menu, $state_item, $in_menu, $position_menu, $tabla_mysql_name, $sucursal_section, $central_section, $recepcion_lotes_section, $auditoria_section, $item_root, $id);
    error_log("bind_param UPDATE exitoso, ejecutando execute");
    
    $resultado = mysqli_stmt_execute($stmt);
    
    if (!$resultado) {
        error_log("Error en execute UPDATE: " . mysqli_stmt_error($stmt));
        throw new Exception('Error al actualizar: ' . mysqli_stmt_error($stmt));
    }
    
    error_log("Execute UPDATE exitoso, item actualizado");
    
    // Verificar si se actualizó alguna fila
    if (mysqli_affected_rows($conexion) == 0) {
        error_log("No se actualizó ninguna fila");
        // No es un error, puede que los datos sean iguales
    }
    
    // Cerrar statement y conexión
    if (isset($stmt)) {
        mysqli_stmt_close($stmt);
    }
    mysqli_close($conexion);
    
    error_log("Proceso de actualización completado exitosamente. ID: $id");
    
    // Devolver respuesta JSON exitosa
    header('Content-Type: application/json');
    echo json_encode([
        'success' => true,
        'message' => 'Item actualizado correctamente',
        'id' => $id,
        'itemName' => $nombre_existente,
        'itemnameText' => $itemnameText
    ]);
    
} catch (Exception $e) {
    // En caso de error
    error_log("ERROR en update_item: " . $e->getMessage());
    error_log("Stack trace: " . $e->getTraceAsString());
    
    if (isset($stmt)) {
        mysqli_stmt_close($stmt);
    }
    if (isset($stmt_check)) {
        mysqli_stmt_close($stmt_check);
    }
    if (isset($conexion)) {
        mysqli_close($conexion);
    }
    
    http_response_code(500);
    echo json_encode([
        'error' => 'Error del servidor: ' . $e->getMessage()
    ]);
}
?>
