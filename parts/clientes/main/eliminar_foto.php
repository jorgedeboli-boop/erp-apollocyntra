<?php
/**
 * Archivo para eliminar fotos del cliente
 * Compatible con PHP 7.0
 */

require_once '../../../include/session.php';
require_once '../../../include/functions.php';

header('Content-Type: application/json');

try {
    // Verificar que sea una petición POST
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        http_response_code(405);
        echo json_encode(array('error' => 'Método no permitido'));
        exit;
    }
    
    // Obtener datos
    $id_foto = isset($_POST['id_foto']) ? (int)$_POST['id_foto'] : 0;
    $nombre_foto = isset($_POST['nombre_foto']) ? trim($_POST['nombre_foto']) : '';
    
    if (!$id_foto) {
        throw new Exception("ID de foto no válido");
    }
    
    if (empty($nombre_foto)) {
        throw new Exception("Nombre de foto no válido");
    }
    
    // Conectar BD
    $conexion = conectar_bd();
    
    // Buscar la foto en las tablas fotos_app_*
    $result_tablas = mysqli_query($conexion, "SHOW TABLES LIKE 'fotos_app_%'");
    
    $foto_info = null;
    $tabla_fotos = '';
    
    if ($result_tablas) {
        while ($row_tabla = mysqli_fetch_row($result_tablas)) {
            if (!preg_match('/^fotos_app_\d+$/', $row_tabla[0])) {
                continue;
            }
            $tabla_fotos_temp = $row_tabla[0];
            
            // Buscar la foto por id_foto Y nombre_foto a la vez para evitar colisiones
            // entre tablas (id_foto es AUTOINCREMENT y puede repetirse).
            $query_info = "SELECT id_cliente, nombre_foto FROM " . $tabla_fotos_temp . " WHERE id_foto = ? AND nombre_foto = ?";
            $stmt_info = mysqli_prepare($conexion, $query_info);
            
            if ($stmt_info) {
                mysqli_stmt_bind_param($stmt_info, 'is', $id_foto, $nombre_foto);
                mysqli_stmt_execute($stmt_info);
                $result_info = mysqli_stmt_get_result($stmt_info);
                
                if ($result_info && mysqli_num_rows($result_info) > 0) {
                    $foto_info = mysqli_fetch_assoc($result_info);
                    $tabla_fotos = $tabla_fotos_temp;
                    mysqli_stmt_close($stmt_info);
                    break;
                }
                mysqli_stmt_close($stmt_info);
            }
        }
    }
    
    if (!$foto_info || $tabla_fotos === '') {
        throw new Exception("Foto no encontrada en la base de datos");
    }
    
    // Verificar que el nombre de la foto coincida
    if ($foto_info['nombre_foto'] !== $nombre_foto) {
        throw new Exception("El nombre de la foto no coincide");
    }
    
    // Eliminar registro de la base de datos
    $query_delete = "DELETE FROM " . $tabla_fotos . " WHERE id_foto = ? AND nombre_foto = ?";
    $stmt_delete = mysqli_prepare($conexion, $query_delete);
    mysqli_stmt_bind_param($stmt_delete, 'is', $id_foto, $nombre_foto);
    
    if (!mysqli_stmt_execute($stmt_delete)) {
        throw new Exception("Error al eliminar de la base de datos: " . mysqli_stmt_error($stmt_delete));
    }
    
    mysqli_stmt_close($stmt_delete);
    
    // Eliminar archivo físico
    $ruta_archivo = '../../../photos/' . $nombre_foto;
    if (file_exists($ruta_archivo)) {
        if (!unlink($ruta_archivo)) {
            // Si no se puede eliminar el archivo, registrar el error pero no fallar
            error_log("No se pudo eliminar el archivo físico: " . $ruta_archivo);
        }
    }
    
    // Registrar la acción en el log
    if (function_exists('registrar_accion_usuario')) {
        $texto_action_user = 'Foto eliminada del cliente';
        $id_action_user = "27";
        $url_completa = APP_URL.'/clientes/main/content.php?id=' . $foto_info['id_cliente'];
        $relItemAction = "45";
        registrar_accion_usuario($usuario_id, $id_action_user, $texto_action_user, $usuario_sucursal, $relItemAction, $url_completa);
    }
    
    mysqli_close($conexion);
    
    // Respuesta de éxito
    echo json_encode(array(
        'success' => true,
        'message' => 'Foto eliminada exitosamente',
        'id_foto' => $id_foto,
        'nombre_foto' => $nombre_foto
    ));
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(array(
        'success' => false,
        'error' => $e->getMessage()
    ));
}
?>
