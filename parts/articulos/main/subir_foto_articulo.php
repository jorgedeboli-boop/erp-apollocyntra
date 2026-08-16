<?php
/**
 * Sube imagen/PDF a photos/ y registra en articulos_venta_imagenes.
 */
require_once __DIR__ . '/../../../include/session.php';
require_once __DIR__ . '/../../../include/functions.php';

if (!extension_loaded('gd')) {
    header('Content-Type: application/json');
    http_response_code(500);
    echo json_encode(array(
        'success' => false,
        'error' => 'La extensión GD de PHP no está disponible. No se pueden procesar imágenes.',
    ));
    exit;
}

header('Content-Type: application/json; charset=utf-8');

try {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        http_response_code(405);
        echo json_encode(array('error' => 'Método no permitido'));
        exit;
    }

    if (!isset($_FILES['archivo_foto']) || $_FILES['archivo_foto']['error'] !== UPLOAD_ERR_OK) {
        throw new Exception('Error al subir el archivo');
    }

    $archivo = $_FILES['archivo_foto'];
    $id_articulo = isset($_POST['id_articulo']) ? (int) $_POST['id_articulo'] : 0;
    if ($id_articulo <= 0) {
        throw new Exception('ID de artículo no válido');
    }

    $tipos_permitidos = array('image/jpeg', 'image/jpg', 'image/png', 'image/gif', 'application/pdf');
    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $tipo_mime = finfo_file($finfo, $archivo['tmp_name']);
    finfo_close($finfo);

    if (!in_array($tipo_mime, $tipos_permitidos)) {
        throw new Exception('Tipo de archivo no permitido. Solo se permiten imágenes JPG, PNG, GIF y PDF');
    }

    if ($archivo['size'] > 5 * 1024 * 1024) {
        throw new Exception('El archivo es demasiado grande. Máximo 5MB');
    }

    $extension = strtolower(pathinfo($archivo['name'], PATHINFO_EXTENSION));

    if ($tipo_mime === 'application/pdf') {
        $nombre_archivo = generarNombrePDF($archivo['name'], $extension);
    } else {
        do {
            $nombre_archivo = generarNombreUnico() . '.' . $extension;
            $ruta_completa = __DIR__ . '/../../../photos/' . $nombre_archivo;
        } while (file_exists($ruta_completa));
    }

    $directorio_photos = __DIR__ . '/../../../photos/';
    if (!is_dir($directorio_photos)) {
        if (!mkdir($directorio_photos, 0755, true)) {
            throw new Exception('No se pudo crear el directorio de fotos');
        }
    }

    $ruta_completa = $directorio_photos . $nombre_archivo;

    if ($tipo_mime === 'application/pdf') {
        if (!move_uploaded_file($archivo['tmp_name'], $ruta_completa)) {
            throw new Exception('Error al mover el archivo PDF');
        }
    } else {
        $imagen_procesada = procesarYRedimensionarImagen($archivo['tmp_name'], $extension);
        if (!$imagen_procesada) {
            throw new Exception('Error al procesar la imagen');
        }
        if (!file_put_contents($ruta_completa, $imagen_procesada)) {
            throw new Exception('Error al guardar la imagen procesada');
        }
    }

    $conexion = conectar_bd();
    $stChk = mysqli_prepare($conexion, 'SELECT sku FROM articulos WHERE sku = ? LIMIT 1');
    mysqli_stmt_bind_param($stChk, 'i', $id_articulo);
    mysqli_stmt_execute($stChk);
    $rChk = mysqli_stmt_get_result($stChk);
    if (!$rChk || !mysqli_fetch_assoc($rChk)) {
        mysqli_stmt_close($stChk);
        mysqli_close($conexion);
        @unlink($ruta_completa);
        throw new Exception('Artículo no encontrado');
    }
    mysqli_stmt_close($stChk);

    $ins = mysqli_prepare(
        $conexion,
        'INSERT INTO articulos_venta_imagenes (rel_sku_articulo, src) VALUES (?, ?)'
    );
    if (!$ins) {
        @unlink($ruta_completa);
        throw new Exception(mysqli_error($conexion));
    }
    mysqli_stmt_bind_param($ins, 'is', $id_articulo, $nombre_archivo);
    if (!mysqli_stmt_execute($ins)) {
        mysqli_stmt_close($ins);
        mysqli_close($conexion);
        @unlink($ruta_completa);
        throw new Exception('Error al guardar en la base de datos: ' . mysqli_error($conexion));
    }
    $id_foto = (int) mysqli_insert_id($conexion);
    mysqli_stmt_close($ins);
    mysqli_close($conexion);

    if (function_exists('registrar_accion_usuario')) {
        $texto_action_user = 'Documento subido para artículo venta #' . $id_articulo;
        $id_action_user = '25';
        $url_completa = APP_URL . '/articulo.php?id=' . $id_articulo;
        $relItemAction = isset($id_type_Item) ? $id_type_Item : '0';
        registrar_accion_usuario($usuario_id, $id_action_user, $texto_action_user, $usuario_sucursal, $relItemAction, $url_completa);
    }

    $mensaje = $tipo_mime === 'application/pdf' ? 'PDF subido exitosamente' : 'Foto subida y redimensionada exitosamente';

    echo json_encode(array(
        'success' => true,
        'message' => $mensaje,
        'id_foto' => $id_foto,
        'nombre_archivo' => $nombre_archivo,
        'tipo_archivo' => $tipo_mime,
    ));
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(array(
        'success' => false,
        'error' => $e->getMessage(),
    ));
}
