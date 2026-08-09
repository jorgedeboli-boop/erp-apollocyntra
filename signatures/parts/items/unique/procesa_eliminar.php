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
    error_log("=== DEBUG ELIMINAR ITEM ===");
    error_log("POST data: " . print_r($_POST, true));
    
    // Obtener el ID del item a eliminar
    $id = trim($_POST['id'] ?? '');
    
    if (empty($id)) {
        throw new Exception('ID del item es obligatorio');
    }
    
    // Validar que el ID sea un número
    if (!is_numeric($id)) {
        throw new Exception('ID del item debe ser un número válido');
    }
    
    // Conectar a la base de datos
    $conexion = conectar_bd();
    
    if (!$conexion) {
        throw new Exception('Error de conexión a la base de datos');
    }
    
    error_log("Conexión a BD exitosa");
    
    // TEST: Verificar que la función de eliminación funciona
    error_log("DEBUG: === TEST DE FUNCIÓN DE ELIMINACIÓN ===");
    $ruta_test = dirname(dirname(dirname(dirname(__FILE__))));
    error_log("DEBUG: Ruta base calculada: $ruta_test");
    error_log("DEBUG: ¿Existe directorio parts? " . (is_dir($ruta_test . "/parts") ? 'SÍ' : 'NO'));
    error_log("DEBUG: Permisos de parts: " . substr(sprintf('%o', fileperms($ruta_test . "/parts")), -4));
    error_log("DEBUG: === FIN TEST ===");
    
    // Verificar si el item existe
    $query_check = "SELECT id_type_Item, itemName, itemnameText, typ_item, fhater_item FROM itemsSections WHERE id_type_Item = ?";
    $stmt_check = mysqli_prepare($conexion, $query_check);
    
    if (!$stmt_check) {
        throw new Exception('Error al preparar la consulta de verificación: ' . mysqli_error($conexion));
    }
    
    mysqli_stmt_bind_param($stmt_check, 'i', $id);
    mysqli_stmt_execute($stmt_check);
    mysqli_stmt_store_result($stmt_check);
    
    if (mysqli_stmt_num_rows($stmt_check) == 0) {
        throw new Exception('El item no existe');
    }
    
    mysqli_stmt_bind_result($stmt_check, $id_item, $itemName, $itemnameText, $typ_item, $fhater_item);
    mysqli_stmt_fetch($stmt_check);
    mysqli_stmt_close($stmt_check);
    
    // Obtener la URL del item
    $query_url = "SELECT url_item FROM itemsSections WHERE id_type_Item = ?";
    $stmt_url = mysqli_prepare($conexion, $query_url);
    if ($stmt_url) {
        mysqli_stmt_bind_param($stmt_url, 'i', $id);
        mysqli_stmt_execute($stmt_url);
        mysqli_stmt_store_result($stmt_url);
        mysqli_stmt_bind_result($stmt_url, $url_item);
        mysqli_stmt_fetch($stmt_url);
        mysqli_stmt_close($stmt_url);
    } else {
        $url_item = '';
    }
    
    error_log("Item encontrado: ID=$id_item, Nombre=$itemName, Tipo=$typ_item, Padre=$fhater_item, URL=$url_item");
    
    // Función para eliminar carpetas y archivos de un item
    function eliminarEstructuraArchivos($url_item, $typ_item, $fhater_item = null) {
        error_log("DEBUG: FUNCIÓN eliminarEstructuraArchivos INICIADA");
        error_log("DEBUG: Parámetros recibidos: url_item='$url_item', typ_item='$typ_item', fhater_item='$fhater_item'");
        
        $tipos_con_carpetas = ['unique', 'main', 'listar', 'editar', 'crear'];
        
        // Solo eliminar carpetas si el tipo genera estructura de archivos
        if (!in_array($typ_item, $tipos_con_carpetas)) {
            error_log("DEBUG: Tipo '$typ_item' no genera carpetas, saltando eliminación de archivos");
            return;
        }
        
        error_log("DEBUG: Eliminando estructura para url_item='$url_item', typ_item='$typ_item', fhater_item='$fhater_item'");
        
        // Obtener la ruta base del proyecto
        $ruta_base = dirname(dirname(dirname(dirname(__FILE__))));
        error_log("DEBUG: Ruta base del proyecto: $ruta_base");
        
        // Determinar la ruta de la carpeta
        if ($fhater_item && $fhater_item != 'false' && !empty($fhater_item) && is_numeric($fhater_item)) {
            // Si tiene padre numérico, necesitamos obtener el nombre del item padre
            global $conexion;
            $query_padre = "SELECT url_item FROM itemsSections WHERE id_type_Item = ?";
            $stmt_padre = mysqli_prepare($conexion, $query_padre);
            if ($stmt_padre) {
                mysqli_stmt_bind_param($stmt_padre, 'i', $fhater_item);
                mysqli_stmt_execute($stmt_padre);
                mysqli_stmt_store_result($stmt_padre);
                mysqli_stmt_bind_result($stmt_padre, $url_padre);
                mysqli_stmt_fetch($stmt_padre);
                mysqli_stmt_close($stmt_padre);
                
                if ($url_padre) {
                    $carpeta_item = $ruta_base . "/parts/" . $url_padre . "/" . $typ_item;
                } else {
                    $carpeta_item = $ruta_base . "/parts/" . $url_item . "/" . $typ_item;
                }
            } else {
                $carpeta_item = $ruta_base . "/parts/" . $url_item . "/" . $typ_item;
            }
        } else {
            // Si no tiene padre, la carpeta está en el nivel raíz
            $carpeta_item = $ruta_base . "/parts/" . $url_item . "/" . $typ_item;
        }
        
        error_log("DEBUG: Ruta de carpeta calculada: $carpeta_item");
        error_log("DEBUG: ¿Existe carpeta? " . (is_dir($carpeta_item) ? 'SÍ' : 'NO'));
        
        // Eliminar archivos específicos del tipo
        $archivos = ['content.php', 'css.php', 'javascript.php'];
        foreach ($archivos as $archivo) {
            $ruta_archivo = $carpeta_item . "/" . $archivo;
            error_log("DEBUG: Intentando eliminar archivo: $ruta_archivo");
            error_log("DEBUG: ¿Existe archivo? " . (file_exists($ruta_archivo) ? 'SÍ' : 'NO'));
            
            if (file_exists($ruta_archivo)) {
                if (unlink($ruta_archivo)) {
                    error_log("Archivo eliminado: $ruta_archivo");
                } else {
                    error_log("Error al eliminar archivo: $ruta_archivo");
                }
            } else {
                error_log("DEBUG: Archivo no encontrado: $ruta_archivo");
            }
        }
        
        // Eliminar la carpeta del tipo si está vacía
        if (is_dir($carpeta_item)) {
            $contenido = scandir($carpeta_item);
            error_log("DEBUG: Contenido de carpeta '$carpeta_item': " . implode(', ', $contenido));
            
            if (count($contenido) <= 2) { // Solo . y ..
                if (rmdir($carpeta_item)) {
                    error_log("Carpeta eliminada: $carpeta_item");
                } else {
                    error_log("Error al eliminar carpeta: $carpeta_item");
                }
            } else {
                error_log("DEBUG: Carpeta no está vacía, no se puede eliminar");
            }
        }
        
        // Si no tiene padre, también eliminar la carpeta principal del item
        if (!$fhater_item || $fhater_item == 'false' || empty($fhater_item)) {
            $carpeta_principal = $ruta_base . "/parts/" . $url_item;
            error_log("DEBUG: Intentando eliminar carpeta principal: $carpeta_principal");
            error_log("DEBUG: ¿Existe carpeta principal? " . (is_dir($carpeta_principal) ? 'SÍ' : 'NO'));
            
            if (is_dir($carpeta_principal)) {
                $contenido_principal = scandir($carpeta_principal);
                error_log("DEBUG: Contenido de carpeta principal: " . implode(', ', $contenido_principal));
                
                if (count($contenido_principal) <= 2) {
                    if (rmdir($carpeta_principal)) {
                        error_log("Carpeta principal eliminada: $carpeta_principal");
                    } else {
                        error_log("Error al eliminar carpeta principal: $carpeta_principal");
                    }
                } else {
                    error_log("DEBUG: Carpeta principal no está vacía, no se puede eliminar");
                }
            }
        }
        
        // Eliminar archivo raíz si existe
        $archivo_raiz = $ruta_base . "/" . $url_item . ".php";
        error_log("DEBUG: Intentando eliminar archivo raíz: $archivo_raiz");
        if (file_exists($archivo_raiz)) {
            if (unlink($archivo_raiz)) {
                error_log("Archivo raíz eliminado: $archivo_raiz");
            } else {
                error_log("Error al eliminar archivo raíz: $archivo_raiz");
            }
        } else {
            error_log("DEBUG: Archivo raíz no encontrado: $archivo_raiz");
        }
        
        // También eliminar archivo raíz en el directorio parts si existe
        $archivo_raiz_parts = $ruta_base . "/parts/" . $url_item . ".php";
        error_log("DEBUG: Intentando eliminar archivo raíz en parts: $archivo_raiz_parts");
        if (file_exists($archivo_raiz_parts)) {
            if (unlink($archivo_raiz_parts)) {
                error_log("Archivo raíz en parts eliminado: $archivo_raiz_parts");
            } else {
                error_log("Error al eliminar archivo raíz en parts: $archivo_raiz_parts");
            }
        } else {
            error_log("DEBUG: Archivo raíz en parts no encontrado: $archivo_raiz_parts");
        }
        
        error_log("DEBUG: FUNCIÓN eliminarEstructuraArchivos COMPLETADA");
    }
    
    // Función recursiva para eliminar items hijos
    function eliminarItemsHijos($conexion, $id_padre) {
        // Obtener todos los items hijos con información necesaria para eliminar carpetas
        $query_hijos = "SELECT id_type_Item, url_item, typ_item, fhater_item FROM itemsSections WHERE fhater_item = ?";
        $stmt_hijos = mysqli_prepare($conexion, $query_hijos);
        
        if (!$stmt_hijos) {
            throw new Exception('Error al preparar la consulta de verificación de hijos: ' . mysqli_error($conexion));
        }
        
        mysqli_stmt_bind_param($stmt_hijos, 'i', $id_padre);
        mysqli_stmt_execute($stmt_hijos);
        mysqli_stmt_store_result($stmt_hijos);
        mysqli_stmt_bind_result($stmt_hijos, $id_hijo, $url_hijo, $typ_hijo, $fhater_hijo);
        
        $ids_hijos = [];
        $info_hijos = [];
        while (mysqli_stmt_fetch($stmt_hijos)) {
            $ids_hijos[] = $id_hijo;
            $info_hijos[] = [
                'id' => $id_hijo,
                'url' => $url_hijo,
                'typ' => $typ_hijo,
                'fhater' => $fhater_hijo
            ];
        }
        mysqli_stmt_close($stmt_hijos);
        
        $total_eliminados = 0;
        
        // Si hay hijos, eliminarlos recursivamente
        foreach ($info_hijos as $info_hijo) {
            // Verificar si este hijo tiene sus propios hijos
            $total_eliminados += eliminarItemsHijos($conexion, $info_hijo['id']);
            
            // Eliminar estructura de archivos del hijo
            error_log("DEBUG: Eliminando estructura de archivos del hijo: url='{$info_hijo['url']}', typ='{$info_hijo['typ']}', fhater='{$info_hijo['fhater']}'");
            eliminarEstructuraArchivos($info_hijo['url'], $info_hijo['typ'], $info_hijo['fhater']);
            
            // Eliminar el hijo actual de la base de datos
            $query_delete_hijo = "DELETE FROM itemsSections WHERE id_type_Item = ?";
            $stmt_delete_hijo = mysqli_prepare($conexion, $query_delete_hijo);
            
            if (!$stmt_delete_hijo) {
                throw new Exception('Error al preparar la consulta de eliminación del hijo: ' . mysqli_error($conexion));
            }
            
            mysqli_stmt_bind_param($stmt_delete_hijo, 'i', $info_hijo['id']);
            $resultado_hijo = mysqli_stmt_execute($stmt_delete_hijo);
            
            if (!$resultado_hijo) {
                throw new Exception('Error al eliminar item hijo: ' . mysqli_stmt_error($stmt_delete_hijo));
            }
            
            mysqli_stmt_close($stmt_delete_hijo);
            error_log("Item hijo eliminado: ID={$info_hijo['id']}");
            $total_eliminados++;
        }
        
        return $total_eliminados;
    }
    
    // Eliminar todos los items hijos de forma recursiva
    $total_hijos_eliminados = eliminarItemsHijos($conexion, $id);
    error_log("Items hijos eliminados recursivamente para item padre ID=$id. Total eliminados: $total_hijos_eliminados");
    
    // Verificar si es referenciado por otros items como padre de menú
    $query_ref_menu = "SELECT COUNT(*) as total FROM itemsSections WHERE fhater_menu = ?";
    $stmt_ref_menu = mysqli_prepare($conexion, $query_ref_menu);
    
    if (!$stmt_ref_menu) {
        throw new Exception('Error al preparar la consulta de verificación de referencias de menú: ' . mysqli_error($conexion));
    }
    
    mysqli_stmt_bind_param($stmt_ref_menu, 'i', $id);
    mysqli_stmt_execute($stmt_ref_menu);
    mysqli_stmt_store_result($stmt_ref_menu);
    mysqli_stmt_bind_result($stmt_ref_menu, $total_ref_menu);
    mysqli_stmt_fetch($stmt_ref_menu);
    mysqli_stmt_close($stmt_ref_menu);
    
    if ($total_ref_menu > 0) {
        throw new Exception('No se puede eliminar el item porque es referenciado como padre de menú por otros items.');
    }
    
    // Eliminar estructura de archivos del item principal
    error_log("DEBUG: Llamando a eliminarEstructuraArchivos con url_item='$url_item', typ_item='$typ_item', fhater_item='$fhater_item'");
    eliminarEstructuraArchivos($url_item, $typ_item, $fhater_item);
    
    // Eliminar el item
    $query_delete = "DELETE FROM itemsSections WHERE id_type_Item = ?";
    $stmt_delete = mysqli_prepare($conexion, $query_delete);
    
    if (!$stmt_delete) {
        throw new Exception('Error al preparar la consulta de eliminación: ' . mysqli_error($conexion));
    }
    
    mysqli_stmt_bind_param($stmt_delete, 'i', $id);
    $resultado = mysqli_stmt_execute($stmt_delete);
    
    if (!$resultado) {
        throw new Exception('Error al eliminar el item: ' . mysqli_stmt_error($stmt_delete));
    }
    
    // Verificar que se haya eliminado
    if (mysqli_affected_rows($conexion) == 0) {
        throw new Exception('No se pudo eliminar el item');
    }
    
    error_log("Item eliminado exitosamente: ID=$id, Nombre=$itemName");
    
    // Cerrar statement y conexión
    mysqli_stmt_close($stmt_delete);
    mysqli_close($conexion);
    
    // Devolver respuesta JSON exitosa
    header('Content-Type: application/json');
    echo json_encode([
        'success' => true,
        'message' => 'Item eliminado correctamente',
        'id' => $id,
        'itemName' => $itemName,
        'total_eliminados' => $total_hijos_eliminados + 1 // +1 por el item padre
    ]);
    
} catch (Exception $e) {
    // En caso de error
    error_log("ERROR en procesa_eliminar: " . $e->getMessage());
    error_log("Stack trace: " . $e->getTraceAsString());
    
    if (isset($stmt_check)) {
        mysqli_stmt_close($stmt_check);
    }
    if (isset($stmt_hijos)) {
        mysqli_stmt_close($stmt_hijos);
    }
    if (isset($stmt_ref_menu)) {
        mysqli_stmt_close($stmt_ref_menu);
    }
    if (isset($stmt_delete)) {
        mysqli_stmt_close($stmt_delete);
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
