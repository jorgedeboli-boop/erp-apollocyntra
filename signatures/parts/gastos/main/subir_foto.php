<?php
require_once '../../../include/session.php';
require_once '../../../include/functions.php';
require_once '../../../camera/lib/imagenes_catalogo.php';

header('Content-Type: application/json; charset=utf-8');

if (!extension_loaded('gd')) {
    http_response_code(500);
    echo json_encode(array(
        'success' => false,
        'error' => 'La extensión GD de PHP no está disponible. No se pueden procesar imágenes.',
    ));
    exit;
}

$ruta_completa = null;

try {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        http_response_code(405);
        echo json_encode(array('success' => false, 'error' => 'Método no permitido'));
        exit;
    }

    if (!isset($_FILES['archivo_foto']) || $_FILES['archivo_foto']['error'] !== UPLOAD_ERR_OK) {
        $codigo = isset($_FILES['archivo_foto']['error']) ? (int) $_FILES['archivo_foto']['error'] : -1;
        throw new Exception('Error al subir el archivo (código ' . $codigo . ')');
    }

    $archivo = $_FILES['archivo_foto'];
    $id_gasto = isset($_POST['id_gasto']) ? (int) $_POST['id_gasto'] : 0;
    $id_empresa = isset($_POST['id_empresa']) ? (int) $_POST['id_empresa'] : 0;
    $id_sucursal = isset($_POST['id_sucursal']) ? (int) $_POST['id_sucursal'] : 0;

    if (!$id_gasto) {
        throw new Exception('ID de gasto no válido');
    }

    $tipos_permitidos = array('image/jpeg', 'image/jpg', 'image/png', 'image/gif', 'application/pdf');
    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $tipo_mime = finfo_file($finfo, $archivo['tmp_name']);
    finfo_close($finfo);

    if (!in_array($tipo_mime, $tipos_permitidos, true)) {
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
            $ruta_completa = '../../../photos/' . $nombre_archivo;
        } while (file_exists($ruta_completa));
    }

    $ruta_completa = '../../../photos/' . $nombre_archivo;

    $directorio_photos = '../../../photos/';
    if (!is_dir($directorio_photos)) {
        if (!mkdir($directorio_photos, 0755, true)) {
            throw new Exception('No se pudo crear el directorio de fotos');
        }
    }

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
    if (!$conexion) {
        throw new Exception('Error de conexión a la base de datos');
    }

    $id_foto = camera_insertar_foto_gasto(
        $conexion,
        $id_gasto,
        $id_empresa,
        $id_sucursal,
        $nombre_archivo,
        (int) $usuario_id,
        'manual'
    );

    if (function_exists('registrar_accion_usuario')) {
        try {
            $url_completa = (defined('APP_URL') ? APP_URL : '') . '/gasto.php?id=' . $id_gasto;
            registrar_accion_usuario($usuario_id, '25', 'Documento subido del gasto', $usuario_sucursal, '46', $url_completa);
        } catch (Throwable $logErr) {
            error_log('subir_foto gasto: registrar_accion_usuario: ' . $logErr->getMessage());
        }
    }

    mysqli_close($conexion);

    $mensaje = $tipo_mime === 'application/pdf' ? 'PDF subido exitosamente' : 'Foto subida y redimensionada exitosamente';

    echo json_encode(array(
        'success' => true,
        'message' => $mensaje,
        'id_foto' => $id_foto,
        'nombre_archivo' => $nombre_archivo,
        'tipo_archivo' => $tipo_mime,
    ));
} catch (Throwable $e) {
    if ($ruta_completa && file_exists($ruta_completa)) {
        @unlink($ruta_completa);
    }
    http_response_code(500);
    echo json_encode(array(
        'success' => false,
        'error' => $e->getMessage(),
    ));
}
