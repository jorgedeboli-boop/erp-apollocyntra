<?php
/**
 * Recupera un plazo cobrado: revierte el cobro y deja la cuota pendiente o vencida.
 */
require_once __DIR__ . '/../../../include/session.php';
require_once __DIR__ . '/../../../include/functions.php';

header('Content-Type: application/json; charset=utf-8');

$conexion = conectar_bd();

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
    $id_plazo = isset($_POST['id_plazo']) ? (int) $_POST['id_plazo'] : 0;
    $uid = (int) $usuario_id;

    if ($id_venta <= 0 || $id_plazo <= 0 || $uid <= 0) {
        throw new Exception('Datos no válidos');
    }

    $item_modulo = basename(dirname(__DIR__));
    if (!usuario_puede_acceder_crud_tipo($usuario_privilegio_id, crud_id_listar_modulo($item_modulo), 'editar')) {
        throw new Exception('No tiene permisos para esta acción');
    }

    $stmtV = mysqli_prepare(
        $conexion,
        'SELECT id, estado, venta_plazos, id_sucursal, id_venta_sucursal FROM ventas WHERE id = ? LIMIT 1'
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

    $estVenta = strtolower((string) ($venta['estado'] ?? ''));
    if (!in_array($estVenta, ['enfecha', 'vencido', 'vendido'], true)) {
        throw new Exception('La venta no admite recuperar plazos en su estado actual');
    }

    $id_sucursal = (int) ($venta['id_sucursal'] ?? 0);
    $id_venta_sucursal = (int) ($venta['id_venta_sucursal'] ?? 0);

    if ($estVenta === 'vendido' && venta_plazos_tiene_factura_generada($conexion, $id_venta, $id_sucursal)) {
        throw new Exception('No se puede recuperar plazos: la venta ya tiene factura generada');
    }

    $stmtMin = mysqli_prepare(
        $conexion,
        'SELECT MIN(id) AS min_id FROM ventas_plazos WHERE id_venta = ?'
    );
    if (!$stmtMin) {
        throw new Exception(mysqli_error($conexion));
    }
    mysqli_stmt_bind_param($stmtMin, 'i', $id_venta);
    mysqli_stmt_execute($stmtMin);
    $rmin = mysqli_stmt_get_result($stmtMin);
    $rowMin = $rmin ? mysqli_fetch_assoc($rmin) : null;
    mysqli_stmt_close($stmtMin);
    if ((int) ($rowMin['min_id'] ?? 0) === $id_plazo) {
        throw new Exception('No se puede recuperar el primer plazo de la venta');
    }

    $stmtMax = mysqli_prepare(
        $conexion,
        "SELECT MAX(id) AS max_id FROM ventas_plazos WHERE id_venta = ? AND estado = 'Pagado'"
    );
    if (!$stmtMax) {
        throw new Exception(mysqli_error($conexion));
    }
    mysqli_stmt_bind_param($stmtMax, 'i', $id_venta);
    mysqli_stmt_execute($stmtMax);
    $rmax = mysqli_stmt_get_result($stmtMax);
    $rowMax = $rmax ? mysqli_fetch_assoc($rmax) : null;
    mysqli_stmt_close($stmtMax);
    $max_pagado = (int) ($rowMax['max_id'] ?? 0);
    if ($max_pagado <= 0 || $max_pagado !== $id_plazo) {
        throw new Exception('Solo se puede recuperar el último plazo cobrado');
    }

    $stmtPl = mysqli_prepare(
        $conexion,
        "SELECT vp.id, vp.estado, vp.importe, vp.metodo_pago, vp.fecha_vencimiento,
                vp.cantidad_contado, vp.cantidad_transferencia, vp.cantidad_bizum, vp.cantidad_tarjeta,
                (SELECT COUNT(*) FROM ventas_plazos v2 WHERE v2.id_venta = vp.id_venta AND v2.id <= vp.id) AS numero_cuota
         FROM ventas_plazos vp
         WHERE vp.id = ? AND vp.id_venta = ? AND vp.estado = 'Pagado'
         LIMIT 1"
    );
    if (!$stmtPl) {
        throw new Exception(mysqli_error($conexion));
    }
    mysqli_stmt_bind_param($stmtPl, 'ii', $id_plazo, $id_venta);
    mysqli_stmt_execute($stmtPl);
    $rp = mysqli_stmt_get_result($stmtPl);
    $plazo = $rp ? mysqli_fetch_assoc($rp) : null;
    mysqli_stmt_close($stmtPl);

    if (!$plazo) {
        throw new Exception('Plazo no encontrado o no está cobrado');
    }

    $numero_cuota = max(1, (int) ($plazo['numero_cuota'] ?? 0));
    $forma_de_pago = trim((string) ($plazo['metodo_pago'] ?? ''));
    $cant_contado = (float) ($plazo['cantidad_contado'] ?? 0);
    $cant_transferencia = (float) ($plazo['cantidad_transferencia'] ?? 0);
    $cant_bizum = (float) ($plazo['cantidad_bizum'] ?? 0);
    $cant_tarjeta = (float) ($plazo['cantidad_tarjeta'] ?? 0);
    $importe = (float) ($plazo['importe'] ?? 0);

    if ($forma_de_pago === '' && $importe > 0) {
        $forma_de_pago = 'contado';
        $cant_contado = $importe;
    }

    $fecha_venc = trim((string) ($plazo['fecha_vencimiento'] ?? ''));
    $nuevo_estado = 'Pendiente';
    if ($fecha_venc !== '' && substr($fecha_venc, 0, 10) !== '0000-00-00') {
        $tsVenc = strtotime(substr($fecha_venc, 0, 10));
        if ($tsVenc !== false && $tsVenc < strtotime('today')) {
            $nuevo_estado = 'Vencido';
        }
    }

    $stmtU = mysqli_prepare(
        $conexion,
        "UPDATE ventas_plazos SET
            estado = ?,
            fecha_cobrado = NULL,
            metodo_pago = '',
            comprobante_plazo = '',
            cantidad_contado = 0,
            cantidad_transferencia = 0,
            cantidad_bizum = 0,
            cantidad_tarjeta = 0,
            fecha_vencido = CASE WHEN ? = 'Vencido' THEN COALESCE(NULLIF(fecha_vencido, '0000-00-00 00:00:00'), CURDATE()) ELSE NULL END
         WHERE id = ? AND id_venta = ? AND estado = 'Pagado'"
    );
    if (!$stmtU) {
        throw new Exception(mysqli_error($conexion));
    }
    mysqli_stmt_bind_param($stmtU, 'ssii', $nuevo_estado, $nuevo_estado, $id_plazo, $id_venta);
    mysqli_stmt_execute($stmtU);
    if (mysqli_stmt_affected_rows($stmtU) !== 1) {
        mysqli_stmt_close($stmtU);
        throw new Exception('No se pudo recuperar el plazo');
    }
    mysqli_stmt_close($stmtU);

    if ($estVenta === 'vendido') {
        $estado_venta = ($nuevo_estado === 'Vencido') ? 'vencido' : 'enfecha';
        $stmtVenta = mysqli_prepare($conexion, "UPDATE ventas SET estado = ? WHERE id = ? LIMIT 1");
        if ($stmtVenta) {
            mysqli_stmt_bind_param($stmtVenta, 'si', $estado_venta, $id_venta);
            mysqli_stmt_execute($stmtVenta);
            mysqli_stmt_close($stmtVenta);
        }
    }

    $grupos_caja = 'Recuperación de plazo venta';
    $concepto_caja = 'Reintegro por recuperación del plazo Nº ' . $numero_cuota . ' de la venta Nº ' . $id_venta_sucursal;

    if ($forma_de_pago === 'contado') {
        insertar_movimiento_caja($grupos_caja, $concepto_caja, 0, $cant_contado, $uid, $id_sucursal);
    } elseif ($forma_de_pago === 'tarjeta') {
        insertar_movimiento_tarjeta($id_sucursal, 0, $id_venta_sucursal, $concepto_caja, 0, $uid, $grupos_caja, $cant_tarjeta);
    } elseif ($forma_de_pago === 'bizum') {
        insertar_movimiento_bizum($id_sucursal, 0, $id_venta_sucursal, $concepto_caja, 0, $uid, $grupos_caja, $cant_bizum);
    } elseif ($forma_de_pago === 'transferencia') {
        insertar_movimiento_transferencia($id_sucursal, 0, $id_venta_sucursal, $concepto_caja, 0, $cant_transferencia, $uid, $grupos_caja);
    } elseif ($forma_de_pago === 'combinado') {
        if ($cant_contado > 0) {
            insertar_movimiento_caja($grupos_caja, $concepto_caja, 0, $cant_contado, $uid, $id_sucursal);
        }
        if ($cant_tarjeta > 0) {
            insertar_movimiento_tarjeta($id_sucursal, 0, $id_venta_sucursal, $concepto_caja, 0, $uid, $grupos_caja, $cant_tarjeta);
        }
        if ($cant_bizum > 0) {
            insertar_movimiento_bizum($id_sucursal, 0, $id_venta_sucursal, $concepto_caja, 0, $uid, $grupos_caja, $cant_bizum);
        }
        if ($cant_transferencia > 0) {
            insertar_movimiento_transferencia($id_sucursal, 0, $id_venta_sucursal, $concepto_caja, 0, $cant_transferencia, $uid, $grupos_caja);
        }
    }

    $accion_historico = 'Recuperación del plazo Nº ' . $numero_cuota . ' de la venta Nº ' . $id_venta_sucursal;
    insertAccionPlazoVenta($id_sucursal, $id_plazo, $id_venta, $uid, $accion_historico, 'Central');

    mysqli_close($conexion);

    echo json_encode([
        'success' => true,
        'message' => 'Plazo recuperado correctamente',
    ], JSON_UNESCAPED_UNICODE);
} catch (Exception $e) {
    if ($conexion) {
        mysqli_close($conexion);
    }
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => $e->getMessage()], JSON_UNESCAPED_UNICODE);
}
