<?php
/**
 * Sube archivo a photos/ e inserta en articulos_venta_imagenes usando ancla mínima del ticket
 * (misma lógica de negocio: galería del ticket, no por artículo en UI).
 */
require_once __DIR__ . '/../../../include/session.php';
require_once __DIR__ . '/../../../include/functions.php';
require_once __DIR__ . '/_ticket_articulos_ids.php';

if (!extension_loaded('gd')) {
    header('Content-Type: application/json; charset=utf-8');
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'La extensión GD de PHP no está disponible. No se pueden procesar imágenes.',
    ]);
    exit;
}

header('Content-Type: application/json; charset=utf-8');

try {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        http_response_code(405);
        echo json_encode(['success' => false, 'error' => 'Método no permitido']);
        exit;
    }

    if (!isset($_FILES['archivo_foto']) || $_FILES['archivo_foto']['error'] !== UPLOAD_ERR_OK) {
        throw new Exception('Error al subir el archivo');
    }

    $archivo = $_FILES['archivo_foto'];
    $id_venta = isset($_POST['id_venta']) ? (int) $_POST['id_venta'] : 0;
    if ($id_venta <= 0) {
        throw new Exception('ID de venta no válido');
    }

    $tipos_permitidos = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif', 'application/pdf'];
    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $tipo_mime = finfo_file($finfo, $archivo['tmp_name']);
    finfo_close($finfo);

    if (!in_array($tipo_mime, $tipos_permitidos, true)) {
        throw new Exception('Tipo de archivo no permitido. Solo JPG, PNG, GIF y PDF');
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

    $ruta_completa = __DIR__ . '/../../../photos/' . $nombre_archivo;
    $directorio_photos = __DIR__ . '/../../../photos/';
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
    $ids_av = ventas_main_obtener_ids_articulo_venta_ticket($conexion, $id_venta);
    if (count($ids_av) === 0) {
        @unlink($ruta_completa);
        mysqli_close($conexion);
        throw new Exception('Este ticket no tiene artículos; no se pueden asociar fotos');
    }
    $id_articulo_ancla = min($ids_av);

    $stmtI = mysqli_prepare(
        $conexion,
        'INSERT INTO articulos_venta_imagenes (id_articulo_venta, src) VALUES (?, ?)'
    );
    if (!$stmtI) {
        @unlink($ruta_completa);
        throw new Exception(mysqli_error($conexion));
    }
    mysqli_stmt_bind_param($stmtI, 'is', $id_articulo_ancla, $nombre_archivo);
    if (!mysqli_stmt_execute($stmtI)) {
        @unlink($ruta_completa);
        mysqli_stmt_close($stmtI);
        mysqli_close($conexion);
        throw new Exception(mysqli_stmt_error($stmtI));
    }
    $id_foto = (int) mysqli_insert_id($conexion);
    mysqli_stmt_close($stmtI);
    mysqli_close($conexion);

    $mensaje = $tipo_mime === 'application/pdf' ? 'PDF subido correctamente' : 'Archivo subido correctamente';

    echo json_encode([
        'success' => true,
        'message' => $mensaje,
        'id_foto' => $id_foto,
        'nombre_archivo' => $nombre_archivo,
    ]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
