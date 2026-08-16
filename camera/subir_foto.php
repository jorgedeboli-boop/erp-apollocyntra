<?php
/**
 * Subida unificada desde la app móvil camera/index.php.
 * POST: camera_type (cliente|lote|renovacion|adelanto|adelanto_venta|articulo|articulo_venta|venta|plazo_venta|autorizar_gasto|ia_chat)
 *       + archivo_foto + campos según tipo (id_cliente, id_lote, id_renovacion, id_foto, id_sucursal, …).
 *
 * Los archivos subir_foto_*.php antiguos redirigen aquí fijando camera_type.
 */
require_once __DIR__ . '/../include/functions.php';
require_once __DIR__ . '/lib/imagenes_catalogo.php';

if (!extension_loaded('gd')) {
    header('Content-Type: application/json');
    http_response_code(500);
    echo json_encode(array(
        'success' => false,
        'error' => 'La extensión GD de PHP no está disponible. No se pueden procesar imágenes.',
    ));
    exit;
}

header('Content-Type: application/json');

$CAMERA_TIPOS = array(
    'cliente',
    'lote',
    'gasto',
    'gasto_prueba',
    'renovacion',
    'adelanto',
    'adelanto_venta',
    'articulo',
    'articulo_venta',
    'venta',
    'plazo_venta',
    'autorizar_gasto',
    'ia_chat',
    'documento_ocr',
    'factura_ocr',
    'traspaso',
);

/**
 * @return array{0:string,1:string,2:string} nombre_archivo, tipo_mime, ruta_completa
 */
function camera_procesar_archivo_subida(array $archivo)
{
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
    $directorio_photos = __DIR__ . '/../photos/';

    if (!is_dir($directorio_photos)) {
        if (!mkdir($directorio_photos, 0755, true)) {
            throw new Exception('No se pudo crear el directorio de fotos');
        }
    }

    if ($tipo_mime === 'application/pdf') {
        $nombre_archivo = generarNombrePDF($archivo['name'], $extension);
    } else {
        do {
            $nombre_archivo = generarNombreUnico() . '.' . $extension;
            $ruta_completa = $directorio_photos . $nombre_archivo;
        } while (file_exists($ruta_completa));
    }

    $ruta_completa = $directorio_photos . $nombre_archivo;

    if ($tipo_mime === 'application/pdf') {
        if (!move_uploaded_file($archivo['tmp_name'], $ruta_completa)) {
            throw new Exception('Error al mover el archivo PDF');
        }
    } else {
        $imagen_procesada = procesarYRedimensionarImagen($archivo['tmp_name'], $extension);
        if (function_exists('giroHorizontal') && $imagen_procesada) {
            $imagen_procesada = giroHorizontal($imagen_procesada);
        }
        if (!$imagen_procesada) {
            throw new Exception('Error al procesar la imagen');
        }
        if (!file_put_contents($ruta_completa, $imagen_procesada)) {
            throw new Exception('Error al guardar la imagen procesada');
        }
    }

    return array($nombre_archivo, $tipo_mime, $ruta_completa);
}

function camera_marcar_token_usado($conexion, $id_token_qr)
{
    $id_token_qr = (int) $id_token_qr;
    if ($id_token_qr <= 0) {
        return;
    }
    $stmtT = mysqli_prepare($conexion, "UPDATE tokens_actions SET state_token = 'false' WHERE id_token = ?");
    if ($stmtT) {
        mysqli_stmt_bind_param($stmtT, 'i', $id_token_qr);
        mysqli_stmt_execute($stmtT);
        mysqli_stmt_close($stmtT);
    }
}

try {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        http_response_code(405);
        echo json_encode(array('error' => 'Método no permitido'));
        exit;
    }

    if (!isset($_FILES['archivo_foto']) || $_FILES['archivo_foto']['error'] !== UPLOAD_ERR_OK) {
        throw new Exception('Error al subir el archivo');
    }

    $camera_type = isset($_POST['camera_type']) ? trim((string) $_POST['camera_type']) : '';
    if ($camera_type === '' || !in_array($camera_type, $CAMERA_TIPOS, true)) {
        throw new Exception('Tipo de subida no válido o no indicado (camera_type)');
    }

    $archivo = $_FILES['archivo_foto'];
    list($nombre_archivo, $tipo_mime, $ruta_completa) = camera_procesar_archivo_subida($archivo);

    $conexion = conectar_bd();
    if (!$conexion) {
        @unlink($ruta_completa);
        throw new Exception('Sin conexión a la base de datos');
    }

    $id_token_qr = isset($_POST['id_token']) ? (int) $_POST['id_token'] : 0;
    $mensaje = $tipo_mime === 'application/pdf' ? 'PDF subido exitosamente' : 'Foto subida y redimensionada exitosamente';
    $respuesta = array(
        'success' => true,
        'message' => $mensaje,
        'nombre_archivo' => $nombre_archivo,
        'tipo_archivo' => $tipo_mime,
    );

    switch ($camera_type) {
        case 'cliente':
            $id_cliente = isset($_POST['id_cliente']) ? (int) $_POST['id_cliente'] : 0;
            if ($id_cliente <= 0) {
                throw new Exception('ID de cliente no válido');
            }
            $query_sucursal = 'SELECT sucursal FROM clientes WHERE id_cliente = ?';
            $stmt_s = mysqli_prepare($conexion, $query_sucursal);
            mysqli_stmt_bind_param($stmt_s, 'i', $id_cliente);
            mysqli_stmt_execute($stmt_s);
            $rs = mysqli_stmt_get_result($stmt_s);
            if (!$rs || mysqli_num_rows($rs) === 0) {
                mysqli_stmt_close($stmt_s);
                throw new Exception('Cliente no encontrado');
            }
            $row_c = mysqli_fetch_assoc($rs);
            $id_sucursal = (int) $row_c['sucursal'];
            mysqli_stmt_close($stmt_s);
            if ($id_sucursal <= 0) {
                throw new Exception('Sucursal del cliente no válida');
            }
            $tabla_fotos = 'fotos_app_' . $id_sucursal;
            $query = "INSERT INTO {$tabla_fotos} (id_cliente, nombre_foto) VALUES (?, ?)";
            $stmt = mysqli_prepare($conexion, $query);
            mysqli_stmt_bind_param($stmt, 'is', $id_cliente, $nombre_archivo);
            if (!mysqli_stmt_execute($stmt)) {
                @unlink($ruta_completa);
                throw new Exception('Error al guardar en la base de datos: ' . mysqli_stmt_error($stmt));
            }
            $respuesta['id_foto'] = mysqli_insert_id($conexion);
            mysqli_stmt_close($stmt);
            camera_marcar_token_usado($conexion, $id_token_qr);
            if (function_exists('registrar_accion_usuario')) {
                $texto_action_user = 'Foto subida del cliente';
                $id_action_user = '25';
                $url_completa = APP_URL . '/clientes/main/content.php?id=' . $id_cliente;
                $relItemAction = '46';
                registrar_accion_usuario($usuario_id, $id_action_user, $texto_action_user, $usuario_sucursal, $relItemAction, $url_completa);
            }
            break;

        case 'lote':
            $id_lote = isset($_POST['id_lote']) ? (int) $_POST['id_lote'] : 0;
            $id_sucursal = isset($_POST['id_sucursal']) ? (int) $_POST['id_sucursal'] : 0;
            if ($id_lote <= 0 || $id_sucursal <= 0) {
                throw new Exception('ID de lote o sucursal no válido');
            }
            $tabla_fotos = 'fotos_app_' . $id_sucursal;
            $query = "INSERT INTO {$tabla_fotos} (id_lote, nombre_foto) VALUES (?, ?)";
            $stmt = mysqli_prepare($conexion, $query);
            mysqli_stmt_bind_param($stmt, 'is', $id_lote, $nombre_archivo);
            if (!mysqli_stmt_execute($stmt)) {
                @unlink($ruta_completa);
                throw new Exception('Error al guardar en la base de datos: ' . mysqli_stmt_error($stmt));
            }
            $respuesta['id_foto'] = mysqli_insert_id($conexion);
            mysqli_stmt_close($stmt);
            camera_marcar_token_usado($conexion, $id_token_qr);
            if (function_exists('registrar_accion_usuario')) {
                $texto_action_user = 'Foto subida del lote';
                $id_action_user = '25';
                $url_completa = APP_URL . '/lote.php?id=' . $id_lote;
                $relItemAction = '46';
                registrar_accion_usuario($usuario_id, $id_action_user, $texto_action_user, $usuario_sucursal, $relItemAction, $url_completa);
            }
            break;

        case 'gasto':
            $id_gasto = isset($_POST['id_gasto']) ? (int) $_POST['id_gasto'] : 0;
            $id_empresa = isset($_POST['id_empresa']) ? (int) $_POST['id_empresa'] : 0;
            $id_sucursal = isset($_POST['id_sucursal']) ? (int) $_POST['id_sucursal'] : 0;
            if ($id_gasto <= 0) {
                throw new Exception('ID de gasto no válido');
            }
            $subida_por = 0;
            if (isset($usuario_id) && (int) $usuario_id > 0) {
                $subida_por = (int) $usuario_id;
            } elseif (isset($_POST['subida_por'])) {
                $subida_por = (int) $_POST['subida_por'];
            }
            $respuesta['id_foto'] = camera_insertar_foto_gasto(
                $conexion,
                $id_gasto,
                $id_empresa,
                $id_sucursal,
                $nombre_archivo,
                $subida_por,
                'manual'
            );
            camera_marcar_token_usado($conexion, $id_token_qr);
            if (function_exists('registrar_accion_usuario')) {
                $texto_action_user = 'Documento subido del gasto';
                $id_action_user = '25';
                $url_completa = APP_URL . '/gasto.php?id=' . $id_gasto;
                $relItemAction = '46';
                registrar_accion_usuario($usuario_id, $id_action_user, $texto_action_user, $usuario_sucursal, $relItemAction, $url_completa);
            }
            break;

        case 'gasto_prueba':
            $id_gasto = isset($_POST['id_gasto']) ? (int) $_POST['id_gasto'] : 0;
            $id_empresa = isset($_POST['id_empresa']) ? (int) $_POST['id_empresa'] : 0;
            $id_sucursal = isset($_POST['id_sucursal']) ? (int) $_POST['id_sucursal'] : 0;
            if ($id_gasto <= 0) {
                throw new Exception('ID de gasto no válido');
            }
            $subida_por = 0;
            if (isset($usuario_id) && (int) $usuario_id > 0) {
                $subida_por = (int) $usuario_id;
            } elseif (isset($_POST['subida_por'])) {
                $subida_por = (int) $_POST['subida_por'];
            }
            $respuesta['id_foto'] = camera_insertar_foto_gasto_prueba(
                $conexion,
                $id_gasto,
                $id_empresa,
                $id_sucursal,
                $nombre_archivo,
                $subida_por,
                'manual'
            );
            camera_marcar_token_usado($conexion, $id_token_qr);
            if (function_exists('registrar_accion_usuario')) {
                $texto_action_user = 'Documento subido del gasto de prueba';
                $id_action_user = '25';
                $url_completa = (defined('APP_URL') ? APP_URL : '') . '/gasto_prueba.php?id=' . $id_gasto;
                $relItemAction = '46';
                registrar_accion_usuario($usuario_id, $id_action_user, $texto_action_user, $usuario_sucursal, $relItemAction, $url_completa);
            }
            break;

        case 'traspaso':
            $id_traspaso = isset($_POST['id_traspaso']) ? (int) $_POST['id_traspaso'] : 0;
            if ($id_traspaso <= 0) {
                throw new Exception('ID de traspaso no válido');
            }
            $query = 'INSERT INTO fotos_traspasos (id_trapaso, nombre_foto) VALUES (?, ?)';
            $stmt = mysqli_prepare($conexion, $query);
            mysqli_stmt_bind_param($stmt, 'is', $id_traspaso, $nombre_archivo);
            if (!mysqli_stmt_execute($stmt)) {
                @unlink($ruta_completa);
                throw new Exception('Error al guardar en la base de datos: ' . mysqli_stmt_error($stmt));
            }
            $respuesta['id_foto'] = mysqli_insert_id($conexion);
            mysqli_stmt_close($stmt);
            camera_marcar_token_usado($conexion, $id_token_qr);
            break;

        case 'renovacion':
            $id_renovacion = isset($_POST['id_renovacion']) ? (int) $_POST['id_renovacion'] : 0;
            $id_sucursal = isset($_POST['id_sucursal']) ? (int) $_POST['id_sucursal'] : 0;
            if ($id_renovacion <= 0 || $id_sucursal <= 0) {
                throw new Exception('ID de renovación o sucursal no válido');
            }
            $tabla = 'historico_renovaciones_' . $id_sucursal;
            $query = "UPDATE {$tabla} SET nombre_foto = ? WHERE id_renovaciones = ?";
            $stmt = mysqli_prepare($conexion, $query);
            mysqli_stmt_bind_param($stmt, 'si', $nombre_archivo, $id_renovacion);
            if (!mysqli_stmt_execute($stmt)) {
                @unlink($ruta_completa);
                throw new Exception('Error al actualizar en la base de datos: ' . mysqli_stmt_error($stmt));
            }
            if (mysqli_stmt_affected_rows($stmt) === 0) {
                mysqli_stmt_close($stmt);
                @unlink($ruta_completa);
                throw new Exception('No se encontró el registro de renovación para actualizar');
            }
            mysqli_stmt_close($stmt);
            $respuesta['id_renovacion'] = $id_renovacion;
            camera_marcar_token_usado($conexion, $id_token_qr);
            if (function_exists('registrar_accion_usuario')) {
                registrar_accion_usuario($usuario_id, '25', 'Foto subida de la renovación', $usuario_sucursal, '46', 'renovacion');
            }
            break;

        case 'adelanto':
            $id_foto = isset($_POST['id_foto']) ? (int) $_POST['id_foto'] : 0;
            $id_sucursal = isset($_POST['id_sucursal']) ? (int) $_POST['id_sucursal'] : 0;
            if ($id_foto <= 0 || $id_sucursal <= 0) {
                throw new Exception('Parámetros no válidos');
            }
            $query = 'UPDATE fotos_app_adelantos_cache SET nombre_foto = ? WHERE id_foto = ? AND id_sucursal = ?';
            $stmt = mysqli_prepare($conexion, $query);
            mysqli_stmt_bind_param($stmt, 'sii', $nombre_archivo, $id_foto, $id_sucursal);
            if (!mysqli_stmt_execute($stmt)) {
                @unlink($ruta_completa);
                throw new Exception('Error al actualizar en la base de datos: ' . mysqli_stmt_error($stmt));
            }
            mysqli_stmt_close($stmt);
            $respuesta['id_foto'] = $id_foto;
            camera_marcar_token_usado($conexion, $id_token_qr);
            if (function_exists('registrar_accion_usuario')) {
                registrar_accion_usuario($usuario_id, '25', 'Foto subida de adelanto', $usuario_sucursal, '46', 'adelanto');
            }
            break;

        case 'adelanto_venta':
        case 'plazo_venta':
            $id_foto = isset($_POST['id_foto']) ? (int) $_POST['id_foto'] : 0;
            $id_sucursal = isset($_POST['id_sucursal']) ? (int) $_POST['id_sucursal'] : 0;
            if ($id_foto <= 0 || $id_sucursal <= 0) {
                throw new Exception('Parámetros no válidos');
            }
            $stmt = mysqli_prepare(
                $conexion,
                'UPDATE fotos_app_adelantos_cache SET nombre_foto = ? WHERE id_foto = ? AND id_sucursal = ?'
            );
            mysqli_stmt_bind_param($stmt, 'sii', $nombre_archivo, $id_foto, $id_sucursal);
            if (!mysqli_stmt_execute($stmt)) {
                @unlink($ruta_completa);
                throw new Exception('Error al actualizar en la base de datos: ' . mysqli_stmt_error($stmt));
            }
            mysqli_stmt_close($stmt);
            $respuesta['id_foto'] = $id_foto;
            camera_marcar_token_usado($conexion, $id_token_qr);
            break;

        case 'articulo':
            $id_articulo = isset($_POST['id_articulo']) ? (int) $_POST['id_articulo'] : 0;
            if ($id_articulo <= 0) {
                throw new Exception('ID de artículo no válido');
            }
            $stArt = mysqli_prepare($conexion, 'SELECT sku FROM articulos WHERE sku = ? LIMIT 1');
            if (!$stArt) {
                throw new Exception(mysqli_error($conexion));
            }
            mysqli_stmt_bind_param($stArt, 'i', $id_articulo);
            mysqli_stmt_execute($stArt);
            $rArt = mysqli_stmt_get_result($stArt);
            if (!$rArt || !mysqli_fetch_assoc($rArt)) {
                mysqli_stmt_close($stArt);
                throw new Exception('Artículo no encontrado');
            }
            mysqli_stmt_close($stArt);
            $query = 'INSERT INTO articulos_venta_imagenes (rel_sku_articulo, src) VALUES (?, ?)';
            $stmt = mysqli_prepare($conexion, $query);
            mysqli_stmt_bind_param($stmt, 'is', $id_articulo, $nombre_archivo);
            if (!mysqli_stmt_execute($stmt)) {
                @unlink($ruta_completa);
                throw new Exception('Error al guardar en la base de datos: ' . mysqli_stmt_error($stmt));
            }
            $respuesta['id_foto'] = mysqli_insert_id($conexion);
            mysqli_stmt_close($stmt);
            camera_marcar_token_usado($conexion, $id_token_qr);
            if (function_exists('registrar_accion_usuario')) {
                $relItemAction = isset($id_type_Item) ? $id_type_Item : '0';
                registrar_accion_usuario($usuario_id, '25', 'Foto subida del artículo (móvil)', $usuario_sucursal, $relItemAction, APP_URL . '/articulo.php?id=' . $id_articulo);
            }
            break;

        case 'articulo_venta':
            require_once __DIR__ . '/../parts/ventas/main/_ticket_articulos_ids.php';
            $id_venta = isset($_POST['id_venta']) ? (int) $_POST['id_venta'] : 0;
            $id_sucursal = isset($_POST['id_sucursal']) ? (int) $_POST['id_sucursal'] : 0;
            if ($id_venta <= 0 || $id_sucursal <= 0) {
                throw new Exception('ID de venta o sucursal no válido');
            }
            $stmtV = mysqli_prepare($conexion, 'SELECT id FROM ventas WHERE id = ? AND id_sucursal = ? LIMIT 1');
            if (!$stmtV) {
                @unlink($ruta_completa);
                throw new Exception(mysqli_error($conexion));
            }
            mysqli_stmt_bind_param($stmtV, 'ii', $id_venta, $id_sucursal);
            mysqli_stmt_execute($stmtV);
            $rv = mysqli_stmt_get_result($stmtV);
            if (!$rv || !mysqli_fetch_assoc($rv)) {
                mysqli_stmt_close($stmtV);
                @unlink($ruta_completa);
                throw new Exception('Venta no encontrada o sucursal no coincide');
            }
            mysqli_stmt_close($stmtV);
            $ids_av = ventas_main_obtener_ids_articulo_venta_ticket($conexion, $id_venta);
            if (count($ids_av) === 0) {
                @unlink($ruta_completa);
                throw new Exception('Este ticket no tiene artículos; no se pueden asociar fotos');
            }
            $id_articulo_ancla = min($ids_av);
            $stmtI = mysqli_prepare($conexion, 'INSERT INTO articulos_venta_imagenes (id_articulo_venta, src) VALUES (?, ?)');
            if (!$stmtI) {
                @unlink($ruta_completa);
                throw new Exception(mysqli_error($conexion));
            }
            mysqli_stmt_bind_param($stmtI, 'is', $id_articulo_ancla, $nombre_archivo);
            if (!mysqli_stmt_execute($stmtI)) {
                mysqli_stmt_close($stmtI);
                @unlink($ruta_completa);
                throw new Exception(mysqli_stmt_error($stmtI));
            }
            $respuesta['id_foto'] = (int) mysqli_insert_id($conexion);
            mysqli_stmt_close($stmtI);
            camera_marcar_token_usado($conexion, $id_token_qr);
            if (function_exists('registrar_accion_usuario')) {
                registrar_accion_usuario($usuario_id, '25', 'Foto ticket (artículo venta) desde móvil', $usuario_sucursal, '0', APP_URL . '/venta.php?id=' . $id_venta);
            }
            break;

        case 'venta':
            $id_venta = isset($_POST['id_venta']) ? (int) $_POST['id_venta'] : 0;
            $id_sucursal = isset($_POST['id_sucursal']) ? (int) $_POST['id_sucursal'] : 0;
            if ($id_venta <= 0 || $id_sucursal <= 0) {
                throw new Exception('ID de venta o sucursal no válido');
            }
            $stmtV = mysqli_prepare($conexion, 'SELECT id FROM ventas WHERE id = ? AND id_sucursal = ? LIMIT 1');
            if (!$stmtV) {
                @unlink($ruta_completa);
                throw new Exception(mysqli_error($conexion));
            }
            mysqli_stmt_bind_param($stmtV, 'ii', $id_venta, $id_sucursal);
            mysqli_stmt_execute($stmtV);
            $rv = mysqli_stmt_get_result($stmtV);
            if (!$rv || !mysqli_fetch_assoc($rv)) {
                mysqli_stmt_close($stmtV);
                @unlink($ruta_completa);
                throw new Exception('Venta no encontrada o sucursal no coincide');
            }
            mysqli_stmt_close($stmtV);
            $stmtI = mysqli_prepare($conexion, 'INSERT INTO ventas_imagenes (id_venta, src) VALUES (?, ?)');
            if (!$stmtI) {
                @unlink($ruta_completa);
                throw new Exception(mysqli_error($conexion));
            }
            mysqli_stmt_bind_param($stmtI, 'is', $id_venta, $nombre_archivo);
            if (!mysqli_stmt_execute($stmtI)) {
                mysqli_stmt_close($stmtI);
                @unlink($ruta_completa);
                throw new Exception(mysqli_stmt_error($stmtI));
            }
            $respuesta['id_foto'] = (int) mysqli_insert_id($conexion);
            mysqli_stmt_close($stmtI);
            camera_marcar_token_usado($conexion, $id_token_qr);
            if (function_exists('registrar_accion_usuario')) {
                registrar_accion_usuario($usuario_id, '25', 'Comprobante venta subido desde móvil', $usuario_sucursal, '0', APP_URL . '/venta.php?id=' . $id_venta);
            }
            break;

        case 'autorizar_gasto':
            $id_autorizacion = isset($_POST['id_autorizacion']) ? (int) $_POST['id_autorizacion'] : 0;
            if ($id_autorizacion <= 0) {
                throw new Exception('ID de autorización no válido');
            }
            $query = 'UPDATE autorizaciones_gastos SET imagen = ? WHERE id = ?';
            $stmt = mysqli_prepare($conexion, $query);
            mysqli_stmt_bind_param($stmt, 'si', $nombre_archivo, $id_autorizacion);
            if (!mysqli_stmt_execute($stmt)) {
                @unlink($ruta_completa);
                throw new Exception('Error al actualizar en la base de datos: ' . mysqli_stmt_error($stmt));
            }
            if (mysqli_stmt_affected_rows($stmt) === 0) {
                mysqli_stmt_close($stmt);
                @unlink($ruta_completa);
                throw new Exception('No se encontró el registro de autorización para actualizar');
            }
            mysqli_stmt_close($stmt);
            $respuesta['id_autorizacion'] = $id_autorizacion;
            camera_marcar_token_usado($conexion, $id_token_qr);
            if (function_exists('registrar_accion_usuario')) {
                registrar_accion_usuario($usuario_id, '25', 'Foto subida de la autorización de gasto', $usuario_sucursal, '46', 'autorizar_gasto');
            }
            break;

        case 'ia_chat':
            $id_usuario = isset($_POST['id_usuario']) ? (int) $_POST['id_usuario'] : 0;
            $id_sucursal = isset($_POST['id_sucursal']) ? (int) $_POST['id_sucursal'] : 0;
            if ($id_usuario <= 0 || $id_sucursal <= 0 || $id_token_qr <= 0) {
                @unlink($ruta_completa);
                throw new Exception('Parámetros ia_chat no válidos');
            }
            $token_qr = isset($_POST['token_string']) ? trim((string) $_POST['token_string']) : '';
            if ($token_qr !== '') {
                $stmt_tok = mysqli_prepare(
                    $conexion,
                    'SELECT id_item, type_item, sucursal_token, state_token FROM tokens_actions WHERE id_token = ? AND token_string = ? LIMIT 1'
                );
                if (!$stmt_tok) {
                    @unlink($ruta_completa);
                    throw new Exception(mysqli_error($conexion));
                }
                mysqli_stmt_bind_param($stmt_tok, 'is', $id_token_qr, $token_qr);
            } else {
                $stmt_tok = mysqli_prepare(
                    $conexion,
                    'SELECT id_item, type_item, sucursal_token, state_token FROM tokens_actions WHERE id_token = ? LIMIT 1'
                );
                if (!$stmt_tok) {
                    @unlink($ruta_completa);
                    throw new Exception(mysqli_error($conexion));
                }
                mysqli_stmt_bind_param($stmt_tok, 'i', $id_token_qr);
            }
            mysqli_stmt_execute($stmt_tok);
            $rs_tok = mysqli_stmt_get_result($stmt_tok);
            $row_tok = $rs_tok ? mysqli_fetch_assoc($rs_tok) : null;
            mysqli_stmt_close($stmt_tok);
            if (!$row_tok) {
                @unlink($ruta_completa);
                throw new Exception('Este código QR ha caducado o se canceló. En el TPV, cierra el QR y pulsa «Generar nuevo QR».');
            }
            if ($token_qr === '') {
                $tipo_db = strtolower(trim((string) ($row_tok['type_item'] ?? '')));
                if ($tipo_db !== 'ia_chat') {
                    @unlink($ruta_completa);
                    throw new Exception('Token no válido para esta subida');
                }
            }
            if ((int) $row_tok['id_item'] !== $id_usuario || (int) $row_tok['sucursal_token'] !== $id_sucursal) {
                @unlink($ruta_completa);
                throw new Exception('Token no válido para esta subida');
            }
            $st_raw = strtolower(trim((string) ($row_tok['state_token'] ?? '')));
            $token_activo = ($st_raw === 'true' || $st_raw === '1');
            if (!$token_activo) {
                @unlink($ruta_completa);
                throw new Exception('Este QR ya no es válido. Genera uno nuevo desde el asistente IA.');
            }
            $tmp_ia_dir = __DIR__ . '/tmp_ia_chat';
            if (!is_dir($tmp_ia_dir)) {
                if (!@mkdir($tmp_ia_dir, 0755, true)) {
                    @unlink($ruta_completa);
                    throw new Exception('No se pudo preparar el directorio temporal');
                }
            }
            $meta_written = @file_put_contents(
                $tmp_ia_dir . '/' . $id_token_qr . '.json',
                json_encode(array('nombre_foto' => $nombre_archivo))
            );
            if ($meta_written === false) {
                @unlink($ruta_completa);
                throw new Exception('No se pudo registrar la foto para el chat');
            }
            camera_marcar_token_usado($conexion, $id_token_qr);
            $respuesta['foto_public_path'] = 'photos/' . $nombre_archivo;
            break;

        case 'documento_ocr':
        case 'factura_ocr':
            $id_usuario = isset($_POST['id_usuario']) ? (int) $_POST['id_usuario'] : 0;
            $id_sucursal = isset($_POST['id_sucursal']) ? (int) $_POST['id_sucursal'] : 0;
            if ($id_usuario <= 0 || $id_sucursal <= 0 || $id_token_qr <= 0) {
                @unlink($ruta_completa);
                throw new Exception('Parámetros ' . $camera_type . ' no válidos');
            }
            $token_qr = isset($_POST['token_string']) ? trim((string) $_POST['token_string']) : '';
            if ($token_qr !== '') {
                $stmt_tok = mysqli_prepare(
                    $conexion,
                    'SELECT id_item, type_item, sucursal_token, state_token FROM tokens_actions WHERE id_token = ? AND token_string = ? LIMIT 1'
                );
                if (!$stmt_tok) {
                    @unlink($ruta_completa);
                    throw new Exception(mysqli_error($conexion));
                }
                mysqli_stmt_bind_param($stmt_tok, 'is', $id_token_qr, $token_qr);
            } else {
                $stmt_tok = mysqli_prepare(
                    $conexion,
                    'SELECT id_item, type_item, sucursal_token, state_token FROM tokens_actions WHERE id_token = ? LIMIT 1'
                );
                if (!$stmt_tok) {
                    @unlink($ruta_completa);
                    throw new Exception(mysqli_error($conexion));
                }
                mysqli_stmt_bind_param($stmt_tok, 'i', $id_token_qr);
            }
            mysqli_stmt_execute($stmt_tok);
            $rs_tok = mysqli_stmt_get_result($stmt_tok);
            $row_tok = $rs_tok ? mysqli_fetch_assoc($rs_tok) : null;
            mysqli_stmt_close($stmt_tok);
            if (!$row_tok) {
                @unlink($ruta_completa);
                throw new Exception('Este código QR ha caducado o se canceló. En el TPV, cierra el QR y pulsa «Generar nuevo QR».');
            }
            if ((int) $row_tok['id_item'] !== $id_usuario || (int) $row_tok['sucursal_token'] !== $id_sucursal) {
                @unlink($ruta_completa);
                throw new Exception('Token no válido para esta subida');
            }
            $st_raw = strtolower(trim((string) ($row_tok['state_token'] ?? '')));
            $token_activo = ($st_raw === 'true' || $st_raw === '1');
            if (!$token_activo) {
                @unlink($ruta_completa);
                throw new Exception('Este QR ya no es válido. Genera uno nuevo desde el lector.');
            }
            $tmp_ocr_dir = __DIR__ . '/tmp_' . $camera_type;
            if (!is_dir($tmp_ocr_dir)) {
                if (!@mkdir($tmp_ocr_dir, 0755, true)) {
                    @unlink($ruta_completa);
                    throw new Exception('No se pudo preparar el directorio temporal');
                }
            }
            $meta_written = @file_put_contents(
                $tmp_ocr_dir . '/' . $id_token_qr . '.json',
                json_encode(array('nombre_foto' => $nombre_archivo))
            );
            if ($meta_written === false) {
                @unlink($ruta_completa);
                throw new Exception('No se pudo registrar la foto');
            }
            camera_marcar_token_usado($conexion, $id_token_qr);
            $respuesta['foto_public_path'] = 'photos/' . $nombre_archivo;
            break;

        default:
            @unlink($ruta_completa);
            throw new Exception('Tipo no implementado');
    }

    mysqli_close($conexion);
    echo json_encode($respuesta);
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(array(
        'success' => false,
        'error' => $e->getMessage(),
    ));
}
