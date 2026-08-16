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
    
    $query_info = 'SELECT id_cliente, nombre_foto FROM fotos_app WHERE id_foto = ? AND nombre_foto = ? LIMIT 1';
    $stmt_info = mysqli_prepare($conexion, $query_info);
    if (!$stmt_info) {
        throw new Exception('Error al preparar la consulta: ' . mysqli_error($conexion));
    }
    mysqli_stmt_bind_param($stmt_info, 'is', $id_foto, $nombre_foto);
    mysqli_stmt_execute($stmt_info);
    $result_info = mysqli_stmt_get_result($stmt_info);
    $foto_info = ($result_info && mysqli_num_rows($result_info) > 0) ? mysqli_fetch_assoc($result_info) : null;
    mysqli_stmt_close($stmt_info);
    
    if (!$foto_info) {
        throw new Exception("Foto no encontrada en la base de datos");
    }
    
    $query_delete = 'DELETE FROM fotos_app WHERE id_foto = ? AND nombre_foto = ?';
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
