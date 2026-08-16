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

    // Obtener la venta asociada a este artículo (ventas tiene una fila por artículo vendido)
    $sql_venta = "SELECT id, cliente, id_sucursal, precio, tipo_pago, venta_web 
                  FROM ventas 
                  WHERE id_articulo_venta = ? AND estado = 'vendido' 
                  LIMIT 1";
    $stmt_v = mysqli_prepare($conexion, $sql_venta);
    if (!$stmt_v) {
        throw new Exception('Error al preparar consulta de venta: ' . mysqli_error($conexion));
    }
    mysqli_stmt_bind_param($stmt_v, 'i', $id_articulo);
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
    $sucursal = isset($venta['id_sucursal']) ? (int)$venta['id_sucursal'] : 0;
    $importe = (float)$venta['precio'];
    $forma_pago = $venta['tipo_pago'] ?: '';
    $devolucion_web = ($venta['venta_web'] === 'true') ? 'true' : 'false';

    $sql = "INSERT INTO devoluciones (
                id_venta_original,
                articulo_devolucion,
                motivo_devolucion,
                cliente_devolucion,
                sucursal_devolucion,
                importe_devolucion,
                forma_de_pago_devolucion,
                devolucion_web,
                fecha_devolucion
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW())";
    $stmt = mysqli_prepare($conexion, $sql);
    if (!$stmt) {
        throw new Exception('Error al preparar inserción: ' . mysqli_error($conexion));
    }
    mysqli_stmt_bind_param($stmt, 'iisiiiss',
        $id_venta,
        $id_articulo,
        $motivo,
        $cliente,
        $sucursal,
        $importe,
        $forma_pago,
        $devolucion_web
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
    $res_num = mysqli_query($conexion, "SELECT COALESCE(MAX(numero_factura), 0) + 1 AS siguiente FROM facturas_rectificativas WHERE id_sucursal = " . $sucursal);
    $row_num = mysqli_fetch_assoc($res_num);
    $numero_rect = (int)($row_num['siguiente'] ?? 1);
    $total_abono = -abs($importe);
    $stmt_rect = mysqli_prepare($conexion, "INSERT INTO facturas_rectificativas (
        id_sucursal, numero_factura, cliente_factura, facturado_por, estado_factura, tipo_pago_factura, total_factura, fecha_factura, hora_factura, rel_id_venta, factura_original
    ) VALUES (?, ?, ?, ?, 'pagada', ?, ?, CURDATE(), CURTIME(), ?, ?)");
    if ($stmt_rect) {
        mysqli_stmt_bind_param($stmt_rect, 'iiisdsii', $sucursal, $numero_rect, $cliente, $id_usuario, $forma_pago, $total_abono, $id_venta, $factura_original);
        mysqli_stmt_execute($stmt_rect);
        mysqli_stmt_close($stmt_rect);
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
