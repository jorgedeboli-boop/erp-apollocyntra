<?php
require_once '../../../include/session.php';
require_once '../../../include/functions.php';

if (!extension_loaded('gd')) {
    header('Content-Type: application/json');
    http_response_code(500);
    echo json_encode(array(
        'success' => false,
        'error' => 'La extensión GD de PHP no está disponible. No se pueden procesar imágenes.'
    ));
    exit;
}

header('Content-Type: application/json');

try {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        http_response_code(405);
        echo json_encode(array('error' => 'Método no permitido'));
        exit;
    }
    
    if (!isset($_FILES['archivo_foto']) || $_FILES['archivo_foto']['error'] !== UPLOAD_ERR_OK) {
        throw new Exception("Error al subir el archivo");
    }
    
    $archivo = $_FILES['archivo_foto'];
    $id_cliente = isset($_POST['id_cliente']) ? (int)$_POST['id_cliente'] : 0;
    
    if (!$id_cliente) {
        throw new Exception("ID de cliente no válido");
    }
    
    $conexion_temp = conectar_bd();
    
    $query_cliente = "SELECT id_cliente FROM clientes WHERE id_cliente = ?";
    $stmt_cliente = mysqli_prepare($conexion_temp, $query_cliente);
    mysqli_stmt_bind_param($stmt_cliente, 'i', $id_cliente);
    mysqli_stmt_execute($stmt_cliente);
    $result_cliente = mysqli_stmt_get_result($stmt_cliente);
    
    if (!$result_cliente || mysqli_num_rows($result_cliente) === 0) {
        mysqli_close($conexion_temp);
        throw new Exception("Cliente no encontrado");
    }
    mysqli_stmt_close($stmt_cliente);

    $tabla_fotos = '';
    $result_tablas = mysqli_query($conexion_temp, "SHOW TABLES LIKE 'fotos_app_%'");
    if ($result_tablas) {
        while ($row_tabla = mysqli_fetch_row($result_tablas)) {
            if (preg_match('/^fotos_app_(\d+)$/', $row_tabla[0])) {
                $tabla_fotos = $row_tabla[0];
                break;
            }
        }
    }
    mysqli_close($conexion_temp);
    
    if ($tabla_fotos === '') {
        throw new Exception("No se encontró una tabla de fotos disponible");
    }
    
    // Validar tipo de archivo
    $tipos_permitidos = array('image/jpeg', 'image/jpg', 'image/png', 'image/gif', 'application/pdf');
    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $tipo_mime = finfo_file($finfo, $archivo['tmp_name']);
    finfo_close($finfo);
    
    if (!in_array($tipo_mime, $tipos_permitidos)) {
        throw new Exception("Tipo de archivo no permitido. Solo se permiten imágenes JPG, PNG, GIF y PDF");
    }
    
    // Validar tamaño (5MB máximo)
    if ($archivo['size'] > 5 * 1024 * 1024) {
        throw new Exception("El archivo es demasiado grande. Máximo 5MB");
    }
    
    // Obtener extensión del archivo
    $extension = strtolower(pathinfo($archivo['name'], PATHINFO_EXTENSION));
    
    // Generar nombre único alfanumérico
    if ($tipo_mime === 'application/pdf') {
        // Para PDFs: mantener parte del nombre original + identificador único
        $nombre_archivo = generarNombrePDF($archivo['name'], $extension);
    } else {
        // Para imágenes: solo identificador único
        do {
            $nombre_archivo = generarNombreUnico() . '.' . $extension;
            $ruta_completa = '../../../photos/' . $nombre_archivo;
        } while (file_exists($ruta_completa));
    }
    
    // Definir ruta completa
    $ruta_completa = '../../../photos/' . $nombre_archivo;
    
    // Crear directorio photos si no existe
    $directorio_photos = '../../../photos/';
    if (!is_dir($directorio_photos)) {
        if (!mkdir($directorio_photos, 0755, true)) {
            throw new Exception("No se pudo crear el directorio de fotos");
        }
    }
    
    // Procesar archivo según su tipo
    if ($tipo_mime === 'application/pdf') {
        // Para PDFs, solo mover el archivo sin procesar
        if (!move_uploaded_file($archivo['tmp_name'], $ruta_completa)) {
            throw new Exception("Error al mover el archivo PDF");
        }
    } else {
        // Para imágenes, procesar y redimensionar
        $imagen_procesada = procesarYRedimensionarImagen($archivo['tmp_name'], $extension);
        
        if (!$imagen_procesada) {
            throw new Exception("Error al procesar la imagen");
        }
        
        // Guardar la imagen procesada
        if (!file_put_contents($ruta_completa, $imagen_procesada)) {
            throw new Exception("Error al guardar la imagen procesada");
        }
    }
    
    // Conectar BD
    $conexion = conectar_bd();
    
    // Insertar registro en la tabla fotos_app
    $query = "
        INSERT INTO " . $tabla_fotos . " (id_cliente, nombre_foto) 
        VALUES (?, ?)
    ";
    
    $stmt = mysqli_prepare($conexion, $query);
    mysqli_stmt_bind_param($stmt, 'is', $id_cliente, $nombre_archivo);
    
    if (!mysqli_stmt_execute($stmt)) {
        // Si falla la inserción, eliminar el archivo
        unlink($ruta_completa);
        throw new Exception("Error al guardar en la base de datos: " . mysqli_stmt_error($stmt));
    }
    
    $id_foto = mysqli_insert_id($conexion);
    mysqli_stmt_close($stmt);
    
    // Registrar la acción en el log
    if (function_exists('registrar_accion_usuario')) {
        $texto_action_user = 'Foto subida del cliente';
        $id_action_user = "25";
        $url_completa = APP_URL.'/clientes/main/content.php?id=' . $id_cliente;
        $relItemAction = "46";
        registrar_accion_usuario($usuario_id, $id_action_user, $texto_action_user, $usuario_sucursal, $relItemAction, $url_completa);
    }
    
    mysqli_close($conexion);
    
    // Respuesta de éxito
    $mensaje = $tipo_mime === 'application/pdf' ? 'PDF subido exitosamente' : 'Foto subida y redimensionada exitosamente';
    
    echo json_encode(array(
        'success' => true,
        'message' => $mensaje,
        'id_foto' => $id_foto,
        'nombre_archivo' => $nombre_archivo,
        'tipo_archivo' => $tipo_mime
    ));
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(array(
        'success' => false,
        'error' => $e->getMessage()
    ));
}
?>
