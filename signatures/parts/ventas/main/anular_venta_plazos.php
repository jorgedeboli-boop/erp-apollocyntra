<?php
/**
 * Anula una venta a plazos en estado enfecha/vencido: reintegra cuotas pagadas en contado,
 * devuelve artículos a stock y marca la venta como anulada.
 */
require_once __DIR__ . '/../../../include/session.php';
require_once __DIR__ . '/../../../include/functions.php';

header('Content-Type: application/json; charset=utf-8');

$conexion = conectar_bd();
$txActiva = false;

try {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        http_response_code(405);
        echo json_encode(['success' => false, 'message' => 'Método no permitido']);
        exit;
    }

    if (!$conexion) {
        throw new Exception('Sin conexión');
    }

    $id_venta = isset($_POST['id_venta']) ? (int) $_POST['id_venta'] : 0;
    $motivo_anulacion = isset($_POST['motivo_anulacion']) ? trim((string) $_POST['motivo_anulacion']) : '';

    if ($id_venta <= 0) {
        throw new Exception('ID de venta no válido');
    }
    if ($motivo_anulacion === '') {
        throw new Exception('El motivo de la anulación es obligatorio');
    }

    $uid = (int) $usuario_id;
    if ($uid <= 0) {
        throw new Exception('Usuario no válido');
    }

    $stmtV = mysqli_prepare(
        $conexion,
        'SELECT id, estado, venta_plazos, id_sucursal, id_venta_sucursal, id_articulo_venta
         FROM ventas WHERE id = ? LIMIT 1'
    );
    if (!$stmtV) {
        throw new Exception(mysqli_error($conexion));
    }
    mysqli_stmt_bind_param($stmtV, 'i', $id_venta);
    mysqli_stmt_execute($stmtV);
    $rv = mysqli_stmt_get_result($stmtV);
    $venta = $rv ? mysqli_fetch_assoc($rv) : null;
    mysqli_stmt_close($stmtV);

    if (!$venta) {
        throw new Exception('Venta no encontrada');
    }

    if (strtolower((string) ($venta['venta_plazos'] ?? '')) !== 'si') {
        throw new Exception('La venta no es a plazos');
    }

    $est = strtolower((string) ($venta['estado'] ?? ''));
    if (!in_array($est, ['enfecha', 'vencido'], true)) {
        throw new Exception('Solo se puede anular una venta a plazos en fecha o vencida');
    }

    $id_sucursal = (int) ($venta['id_sucursal'] ?? 0);
    $id_venta_sucursal = (int) ($venta['id_venta_sucursal'] ?? 0);
    if ($id_sucursal <= 0) {
        throw new Exception('Sucursal de venta no válida');
    }

    $stmtPl = mysqli_prepare(
        $conexion,
        "SELECT vp.id, vp.importe, vp.metodo_pago, vp.cantidad_contado, vp.cantidad_transferencia, vp.cantidad_bizum, vp.cantidad_tarjeta,
                (SELECT COUNT(*) FROM ventas_plazos v2 WHERE v2.id_venta = vp.id_venta AND v2.id <= vp.id) AS numero_cuota
         FROM ventas_plazos vp
         WHERE vp.id_venta = ? AND vp.estado = 'Pagado'
         ORDER BY vp.id ASC"
    );
    if (!$stmtPl) {
        throw new Exception(mysqli_error($conexion));
    }
    mysqli_stmt_bind_param($stmtPl, 'i', $id_venta);
    mysqli_stmt_execute($stmtPl);
    $rpl = mysqli_stmt_get_result($stmtPl);
    $plazos_reintegro = [];
    if ($rpl) {
        while ($rowPl = mysqli_fetch_assoc($rpl)) {
            $plazos_reintegro[] = [
                'id' => (int) ($rowPl['id'] ?? 0),
                'importe' => (float) ($rowPl['importe'] ?? 0),
                'numero_cuota' => max(1, (int) ($rowPl['numero_cuota'] ?? 0)),
                'metodo_pago' => trim((string) ($rowPl['metodo_pago'] ?? '')),
                'cantidad_contado' => (float) ($rowPl['cantidad_contado'] ?? 0),
                'cantidad_transferencia' => (float) ($rowPl['cantidad_transferencia'] ?? 0),
                'cantidad_bizum' => (float) ($rowPl['cantidad_bizum'] ?? 0),
                'cantidad_tarjeta' => (float) ($rowPl['cantidad_tarjeta'] ?? 0),
            ];
        }
    }
    mysqli_stmt_close($stmtPl);

    $articulos_ids = [];
    $stmtArt = mysqli_prepare(
        $conexion,
        'SELECT sku_articulo FROM rel_articulos_venta WHERE rel_id_venta = ? AND sucursal_venta = ?'
    );
    if (!$stmtArt) {
        throw new Exception(mysqli_error($conexion));
    }
    mysqli_stmt_bind_param($stmtArt, 'ii', $id_venta, $id_sucursal);
    mysqli_stmt_execute($stmtArt);
    $rart = mysqli_stmt_get_result($stmtArt);
    if ($rart) {
        while ($rowArt = mysqli_fetch_assoc($rart)) {
            $sku = (int) ($rowArt['sku_articulo'] ?? 0);
            if ($sku > 0) {
                $articulos_ids[] = $sku;
            }
        }
    }
    mysqli_stmt_close($stmtArt);

    if (count($articulos_ids) === 0) {
        $id_art_fallback = (int) ($venta['id_articulo_venta'] ?? 0);
        if ($id_art_fallback > 0) {
            $articulos_ids[] = $id_art_fallback;
        }
    }
    $articulos_ids = array_values(array_unique($articulos_ids));

    if (count($articulos_ids) === 0) {
        throw new Exception('No se encontraron artículos asociados a la venta');
    }

    $nombre_sucursal_venta = '';
    $nomSucRaw = obtener_nombre_sucursal($id_sucursal);
    if ($nomSucRaw !== false) {
        $nombre_sucursal_venta = trim((string) $nomSucRaw);
    }
    if ($nombre_sucursal_venta !== '') {
        $nombre_sucursal_venta = ucfirst(mb_strtolower($nombre_sucursal_venta, 'UTF-8'));
    }

    mysqli_begin_transaction($conexion);
    $txActiva = true;

    $stmtUpAv = mysqli_prepare(
        $conexion,
        "UPDATE articulos_venta SET
            estado = 'enventa',
            fecha_en_venta = NOW(),
            fecha_vendido = '0000-00-00',
            hora_vendido = '00:00:00',
            last_id_venta = 0,
            id_venta_sucursal = 0,
            nombre_sucursal_venta = ?,
            update_register = CURDATE()
         WHERE id = ?"
    );
    if (!$stmtUpAv) {
        throw new Exception(mysqli_error($conexion));
    }

    $stmtUpRel = mysqli_prepare(
        $conexion,
        "UPDATE rel_articulos_estados SET
            estado_articulo = 'Stock',
            rel_id_sucursal_venta = 0,
            precio_venta = 0,
            fecha_venta = '0000-00-00',
            rel_id_venta = 0,
            rel_numero_semana_venta = 0,
            year_rel = 0
         WHERE rel_id_articulo_venta = ?"
    );
    if (!$stmtUpRel) {
        mysqli_stmt_close($stmtUpAv);
        throw new Exception(mysqli_error($conexion));
    }

    $comentario_traz = 'Artículo pasado a stock de la venta Nº ' . $id_venta_sucursal;

    foreach ($articulos_ids as $id_articulo) {
        quitar_articulo_venta_de_auditoria($conexion, $id_articulo);

        mysqli_stmt_bind_param($stmtUpAv, 'si', $nombre_sucursal_venta, $id_articulo);
        if (!mysqli_stmt_execute($stmtUpAv)) {
            throw new Exception('Error al actualizar artículo: ' . mysqli_stmt_error($stmtUpAv));
        }

        mysqli_stmt_bind_param($stmtUpRel, 'i', $id_articulo);
        if (!mysqli_stmt_execute($stmtUpRel)) {
            throw new Exception('Error al actualizar estado del artículo: ' . mysqli_stmt_error($stmtUpRel));
        }

        trazabilidad_articulos_venta(0, $uid, 'devuelto', $comentario_traz, $id_sucursal, $id_articulo, 0);
    }

    mysqli_stmt_close($stmtUpAv);
    mysqli_stmt_close($stmtUpRel);

    $stmtUpV = mysqli_prepare(
        $conexion,
        "UPDATE ventas SET
            anulado_por = ?,
            estado = 'anulado',
            fecha_anulacion = NOW(),
            motivo_anulacion = ?
         WHERE id = ? LIMIT 1"
    );
    if (!$stmtUpV) {
        throw new Exception(mysqli_error($conexion));
    }
    mysqli_stmt_bind_param($stmtUpV, 'isi', $uid, $motivo_anulacion, $id_venta);
    if (!mysqli_stmt_execute($stmtUpV)) {
        mysqli_stmt_close($stmtUpV);
        throw new Exception('Error al anular la venta: ' . mysqli_stmt_error($stmtUpV));
    }
    if (mysqli_stmt_affected_rows($stmtUpV) !== 1) {
        mysqli_stmt_close($stmtUpV);
        throw new Exception('No se pudo anular la venta');
    }
    mysqli_stmt_close($stmtUpV);

    if (!mysqli_commit($conexion)) {
        throw new Exception('No se pudo confirmar la operación');
    }

    $stmtU = mysqli_prepare(
        $conexion,
        "UPDATE ventas_plazos SET estado = 'Anulado', fecha_anulado = NOW()
         WHERE id_venta = ? AND id = (
             SELECT MAX(id) FROM (
                 SELECT id FROM ventas_plazos WHERE id_venta = ?
             ) AS plazos_venta
         )"
    );
    if (!$stmtU) {
        throw new Exception(mysqli_error($conexion));
    }
    mysqli_stmt_bind_param($stmtU, 'ii', $id_venta, $id_venta);
    mysqli_stmt_execute($stmtU);
    $af = mysqli_stmt_affected_rows($stmtU);
    mysqli_stmt_close($stmtU);
    if ($af !== 1) {
        throw new Exception('No se pudo actualizar la cuota (¿ya estaba cobrada?)');
    }

    $txActiva = false;

    $grupos_caja = 'Anulación de venta a plazos';
    $lista_skus = implode(',', array_map('strval', $articulos_ids));
    foreach ($plazos_reintegro as $plazo) {
        $id_cuota = (int) ($plazo['id'] ?? 0);
        $importe = (float) ($plazo['importe'] ?? 0);
        $cant_contado = (float) ($plazo['cantidad_contado'] ?? 0);
        $cant_transferencia = (float) ($plazo['cantidad_transferencia'] ?? 0);
        $cant_bizum = (float) ($plazo['cantidad_bizum'] ?? 0);
        $cant_tarjeta = (float) ($plazo['cantidad_tarjeta'] ?? 0);
        $forma_de_pago = $plazo['metodo_pago'];
        if ($id_cuota <= 0 || $importe <= 0) {
            continue;
        }

        $numero_cuota = max(1, (int) ($plazo['numero_cuota'] ?? 0));
        $concepto_caja = 'Reintegro del plazo de la venta anulada Nº '.$id_venta_sucursal.'  (Cuota Nº '.$numero_cuota.' ) (SKUs: '.$lista_skus.')';

        $accion_historico = 'Anulación de venta a plazos venta Nº '.$id_venta_sucursal.' (Cuota Nº '.$numero_cuota .')';
        $origen = "Sucursal";
        insertAccionPlazoVenta($id_sucursal, $id_cuota, $id_venta, $usuario_id, $accion_historico, $origen);

        if($forma_de_pago == "contado"){
            insertar_movimiento_caja($grupos_caja, $concepto_caja, 0, $cant_contado, $usuario_id, $id_sucursal);
        } else if($forma_de_pago == "tarjeta"){
            insertar_movimiento_tarjeta($id_sucursal, 0, $id_venta_sucursal, $concepto_caja, 0, $usuario_id, $grupos_caja, $cant_tarjeta);
        } else if($forma_de_pago == "bizum"){
            insertar_movimiento_bizum($id_sucursal, 0, $id_venta_sucursal, $concepto_caja, 0, $usuario_id, $grupos_caja, $cant_bizum);
        } else if($forma_de_pago == "transferencia"){
            insertar_movimiento_transferencia($id_sucursal, 0, $id_venta_sucursal, $concepto_caja, 0, $cant_transferencia, $usuario_id, $grupos_caja);
        } else if($forma_de_pago == "combinado"){
    
            if($cant_contado > 0){
                insertar_movimiento_caja($grupos_caja, $concepto_caja, 0, $cant_contado, $usuario_id, $id_sucursal);
            }
            if($cant_tarjeta > 0){
                insertar_movimiento_tarjeta($id_sucursal, 0, $id_venta_sucursal, $concepto_caja, 0, $usuario_id, $grupos_caja, $cant_tarjeta);
            }
            if($cant_bizum > 0){
                insertar_movimiento_bizum($id_sucursal, 0, $id_venta_sucursal, $concepto_caja, 0, $usuario_id, $grupos_caja, $cant_bizum);
            }
            if($cant_transferencia > 0){
                insertar_movimiento_transferencia($id_sucursal, 0, $id_venta_sucursal, $concepto_caja,  0, $cant_transferencia, $usuario_id, $grupos_caja);
            }
        }

    }
    

    mysqli_close($conexion);

    echo json_encode([
        'success' => true,
        'message' => 'Venta a plazos anulada correctamente',
    ], JSON_UNESCAPED_UNICODE);
} catch (Exception $e) {
    if ($txActiva && $conexion) {
        @mysqli_rollback($conexion);
    }
    if ($conexion) {
        mysqli_close($conexion);
    }
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => $e->getMessage()], JSON_UNESCAPED_UNICODE);
}
