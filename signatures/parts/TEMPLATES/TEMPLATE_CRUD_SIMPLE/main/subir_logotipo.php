<?php
require_once '../../../include/session.php';
require_once '../../../include/functions.php';

// Headers para JSON
header('Content-Type: application/json');
header('Cache-Control: no-cache, must-revalidate');

try {
    // Verificar que sea una petición POST
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        http_response_code(405);
        echo json_encode(array('success' => false, 'message' => 'Método no permitido'));
        exit;
    }
    
    // Obtener ID de la empresa
    $id_empresa = isset($_POST['id_empresa']) ? (int)$_POST['id_empresa'] : 0;
    
    if (!$id_empresa) {
        echo json_encode(array('success' => false, 'message' => 'ID de empresa no válido'));
        exit;
    }
    
    // Verificar que se haya subido un archivo
    if (!isset($_FILES['logotipo']) || $_FILES['logotipo']['error'] !== UPLOAD_ERR_OK) {
        echo json_encode(array('success' => false, 'message' => 'No se ha subido ningún archivo o ha ocurrido un error'));
        exit;
    }
    
    $archivo = $_FILES['logotipo'];
    
    // Verificar tipo de archivo (solo imágenes)
    $tipos_permitidos = array('image/jpeg', 'image/jpg', 'image/png', 'image/gif');
    $tipo_archivo = mime_content_type($archivo['tmp_name']);
    
    if (!in_array($tipo_archivo, $tipos_permitidos)) {
        echo json_encode(array('success' => false, 'message' => 'Solo se permiten archivos de imagen (JPG, PNG, GIF)'));
        exit;
    }
    
    // Verificar tamaño (máximo 5MB)
    if ($archivo['size'] > 5 * 1024 * 1024) {
        echo json_encode(array('success' => false, 'message' => 'El archivo es demasiado grande. Máximo 5MB'));
        exit;
    }
    
    // Conectar a la base de datos
    $conexion = conectar_bd();
    
    // Verificar que la empresa existe
    $query_check = "SELECT id_empresa FROM empresas WHERE id_empresa = ?";
    $stmt_check = mysqli_prepare($conexion, $query_check);
    mysqli_stmt_bind_param($stmt_check, 'i', $id_empresa);
    mysqli_stmt_execute($stmt_check);
    $result_check = mysqli_stmt_get_result($stmt_check);
    
    if (!$result_check || mysqli_num_rows($result_check) == 0) {
        echo json_encode(array('success' => false, 'message' => 'Empresa no encontrada'));
        exit;
    }
    
    mysqli_stmt_close($stmt_check);
    
    // Generar nombre único para el archivo
    $extension = pathinfo($archivo['name'], PATHINFO_EXTENSION);
    $nombre_archivo = 'logotipo_empresa_' . $id_empresa . '_' . time() . '.' . $extension;
    $ruta_destino = '../../../photos/' . $nombre_archivo;
    
    // Si ya existe un logotipo, eliminarlo primero
    $query_actual = "SELECT logotipo_empresa FROM empresas WHERE id_empresa = ?";
    $stmt_actual = mysqli_prepare($conexion, $query_actual);
    mysqli_stmt_bind_param($stmt_actual, 'i', $id_empresa);
    mysqli_stmt_execute($stmt_actual);
    $result_actual = mysqli_stmt_get_result($stmt_actual);
    
    if ($result_actual && mysqli_num_rows($result_actual) > 0) {
        $row_actual = mysqli_fetch_assoc($result_actual);
        $logotipo_anterior = $row_actual['logotipo_empresa'];
        
        if (!empty($logotipo_anterior)) {
            $ruta_anterior = '../../../photos/' . $logotipo_anterior;
            if (file_exists($ruta_anterior)) {
                unlink($ruta_anterior);
            }
        }
    }
    mysqli_stmt_close($stmt_actual);
    
    // Mover el archivo subido
    if (move_uploaded_file($archivo['tmp_name'], $ruta_destino)) {
        // Actualizar la base de datos
        $query_update = "UPDATE empresas SET logotipo_empresa = ? WHERE id_empresa = ?";
        $stmt_update = mysqli_prepare($conexion, $query_update);
        mysqli_stmt_bind_param($stmt_update, 'si', $nombre_archivo, $id_empresa);
        
        if (mysqli_stmt_execute($stmt_update)) {
            // Log de la acción - Comentado temporalmente para debug
            // log_accion('Logotipo de empresa actualizado', 'empresas', $id_empresa);
            
            // Debug: verificar que se actualizó correctamente
            error_log("Logotipo actualizado en BD: empresa_id={$id_empresa}, archivo={$nombre_archivo}");
            
            echo json_encode(array(
                'success' => true,
                'message' => 'Logotipo subido correctamente',
                'nombre_archivo' => $nombre_archivo
            ));
        } else {
            // Si falla la BD, eliminar el archivo subido
            unlink($ruta_destino);
            throw new Exception('Error al actualizar la base de datos');
        }
        
        mysqli_stmt_close($stmt_update);
    } else {
        throw new Exception('Error al mover el archivo subido');
    }
    
    mysqli_close($conexion);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(array(
        'success' => false,
        'message' => 'Error interno del servidor: ' . $e->getMessage()
    ));
}
?>
