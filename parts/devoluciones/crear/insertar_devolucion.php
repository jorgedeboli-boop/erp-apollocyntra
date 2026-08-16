<?php
/**
 * Inserta una nueva devolución.
 * Requiere: id_articulo (articulos_venta.id), motivo_devolucion.
 * Obtiene id_venta_original, cliente, sucursal, importe y forma de pago de la venta asociada al artículo.
 */

require_once '../../../include/session.php';
require_once '../../../include/functions.php';

header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Método no permitido']);
    exit;
}

$id_articulo = isset($_POST['id_articulo']) ? (int)$_POST['id_articulo'] : 0;
$motivo = isset($_POST['motivo_devolucion']) ? trim($_POST['motivo_devolucion']) : '';
$id_autorizacion_req = isset($_POST['id_autorizacion']) ? (int)$_POST['id_autorizacion'] : 0;

if (!$id_articulo || $motivo === '') {
    echo json_encode(['success' => false, 'message' => 'Faltan artículo o motivo de devolución.']);
    exit;
}

try {
    $conexion = conectar_bd();

    if ($id_autorizacion_req > 0) {
        $sku_match = (string) $id_articulo;
        $stmt_auth = mysqli_prepare(
            $conexion,
            'SELECT id_autorizacion FROM autorizaciones_devoluciones
             WHERE id_autorizacion = ? AND estado_autorizacion = \'autorizada\' AND sku_articulo_devolucion = ?
             LIMIT 1'
        );
        if (!$stmt_auth) {
            throw new Exception('Error al validar autorización: ' . mysqli_error($conexion));
        }
        mysqli_stmt_bind_param($stmt_auth, 'is', $id_autorizacion_req, $sku_match);
        mysqli_stmt_execute($stmt_auth);
        $res_auth = mysqli_stmt_get_result($stmt_auth);
        $ok_auth = $res_auth && mysqli_fetch_assoc($res_auth);
        mysqli_stmt_close($stmt_auth);
        if (!$ok_auth) {
            mysqli_close($conexion);
            echo json_encode(['success' => false, 'message' => 'Autorización no válida o no coincide con el artículo.']);
            exit;
        }
    }

    $rel_id_empresa_dev = obtener_rel_id_empresa_sesion();
    $sql_venta = "SELECT v.id, v.cliente, v.rel_id_empresa, v.precio, v.tipo_pago
                  FROM ventas v
                  LEFT JOIN rel_articulos_venta r ON r.rel_id_venta = v.id
                  WHERE (r.sku_articulo = ? OR v.id = ?) AND v.estado = 'vendido'
                  LIMIT 1";
    $stmt_v = mysqli_prepare($conexion, $sql_venta);
    if (!$stmt_v) {
        throw new Exception('Error al preparar consulta de venta: ' . mysqli_error($conexion));
    }
    mysqli_stmt_bind_param($stmt_v, 'ii', $id_articulo, $id_articulo);
    mysqli_stmt_execute($stmt_v);
    $res_v = mysqli_stmt_get_result($stmt_v);
    $venta = mysqli_fetch_assoc($res_v);
    mysqli_stmt_close($stmt_v);

    if (!$venta) {
        echo json_encode(['success' => false, 'message' => 'No se encontró una venta para este artículo.']);
        exit;
    }

    $id_venta = (int)$venta['id'];
    $cliente = (int)$venta['cliente'];
    $rel_id_empresa_dev = (int) ($venta['rel_id_empresa'] ?? 0);
    if ($rel_id_empresa_dev <= 0) {
        $rel_id_empresa_dev = obtener_rel_id_empresa_sesion();
    }
    $importe = (float)$venta['precio'];
    $forma_pago = $venta['tipo_pago'] ?: '';
    $devolucion_web = 'false';
    $usuario_dev = isset($_SESSION['usuario_id']) ? (int) $_SESSION['usuario_id'] : 0;
    $estado_dev = 'creada';
    $codigo_auth = '';
    $estado_auth = 'nousada';

    $sql = "INSERT INTO devoluciones (
                id_venta_original,
                articulo_devolucion,
                motivo_devolucion,
                cliente_devolucion,
                rel_id_empresa,
                empresa_devolucion,
                importe_devolucion,
                forma_de_pago_devolucion,
                devolucion_web,
                usuario_devolucion,
                estado_devolucion,
                codigo_autorizacion,
                estado_autorizacion,
                fecha_devolucion,
                hora_devolucion
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, CURDATE(), CURTIME())";
    $stmt = mysqli_prepare($conexion, $sql);
    if (!$stmt) {
        throw new Exception('Error al preparar inserción: ' . mysqli_error($conexion));
    }
    mysqli_stmt_bind_param($stmt, 'iisiiidssisss',
        $id_venta,
        $id_articulo,
        $motivo,
        $cliente,
        $rel_id_empresa_dev,
        $rel_id_empresa_dev,
        $importe,
        $forma_pago,
        $devolucion_web,
        $usuario_dev,
        $estado_dev,
        $codigo_auth,
        $estado_auth
    );

    if (!mysqli_stmt_execute($stmt)) {
        throw new Exception('Error al insertar devolución: ' . mysqli_stmt_error($stmt));
    }
    $id_devolucion = mysqli_insert_id($conexion);
    mysqli_stmt_close($stmt);

    if ($id_autorizacion_req > 0 && $id_devolucion > 0) {
        $stmt_rel = mysqli_prepare(
            $conexion,
            'UPDATE autorizaciones_devoluciones SET rel_id_devolucion = ?, estado_autorizacion = \'usada\' WHERE id_autorizacion = ?'
        );
        if ($stmt_rel) {
            mysqli_stmt_bind_param($stmt_rel, 'ii', $id_devolucion, $id_autorizacion_req);
            mysqli_stmt_execute($stmt_rel);
            mysqli_stmt_close($stmt_rel);
        }
    }

    // Generar factura rectificativa / abono asociada a la devolución
    $factura_original = 0;
    $stmt_f = mysqli_prepare($conexion, "SELECT id_factura, facturado_por FROM facturas WHERE rel_id_venta = ? AND estado_factura != 'anulada' LIMIT 1");
    if ($stmt_f) {
        mysqli_stmt_bind_param($stmt_f, 'i', $id_venta);
        mysqli_stmt_execute($stmt_f);
        $res_f = mysqli_stmt_get_result($stmt_f);
        if ($row_f = mysqli_fetch_assoc($res_f)) {
            $factura_original = (int)$row_f['id_factura'];
        }
        mysqli_stmt_close($stmt_f);
    }
    $id_usuario = isset($_SESSION['usuario_id']) ? (int)$_SESSION['usuario_id'] : 0;
    if (!$id_usuario) {
        $ru = mysqli_query($conexion, "SELECT id_usuario FROM usuarios LIMIT 1");
        if ($ru && $row_u = mysqli_fetch_assoc($ru)) {
            $id_usuario = (int)$row_u['id_usuario'];
        }
    }
    if ($factura_original > 0) {
        try {
            $numero_rect = (int) obtenerNumeroFacturaRectificativa(0, 'articulos');
            $prefijo_rect = facturaConstruirPrefijoRectificativa(0, false, 'articulos');
            $total_abono = -abs($importe);
            crearFacturaRectificativa([
                'id_sucursal' => 0,
                'numero_factura' => $numero_rect,
                'cliente_factura' => $cliente,
                'facturado_por' => $id_usuario,
                'estado_factura' => 'pagada',
                'tipo_pago_factura' => $forma_pago,
                'total_factura' => $total_abono,
                'rel_id_venta' => $id_venta,
                'prefijo_factura' => $prefijo_rect,
                'tipo_factura' => 'articulos',
                'rel_id_empresa' => $rel_id_empresa_dev,
                'rel_id_factura' => $factura_original,
                'factura_original' => $factura_original,
                'motivo_rectificado' => $motivo,
            ]);
        } catch (Throwable $eRect) {
            insertErrorLog('insertar_devolucion: rectificativa no generada: ' . $eRect->getMessage());
        }
    }

    mysqli_close($conexion);

    echo json_encode([
        'success' => true,
        'message' => 'Devolución creada correctamente.',
        'id_devolucion' => $id_devolucion,
        'redirect' => 'devolucion.php?id=' . $id_devolucion
    ]);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
