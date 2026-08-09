<?php
/**
 * Edita el importe de una cuota (Pagado, Pendiente o Vencido).
 * Si está pagada y la venta no tiene factura, ajusta cajas según método de pago.
 * Con venta vendida y factura: no toca caja; si todos los plazos están pagados, precio = suma de plazos.
 */
require_once __DIR__ . '/../../../include/session.php';
require_once __DIR__ . '/../../../include/functions.php';

header('Content-Type: application/json; charset=utf-8');

$conexion = conectar_bd();

/**
 * @param float $diff Positivo = cobro adicional; negativo = reintegro parcial
 */
function editar_plazo_ajustar_caja($forma_de_pago, $cant_contado, $cant_tarjeta, $cant_bizum, $cant_transferencia, $diff, $id_sucursal, $id_venta_sucursal, $uid, $grupos_caja, $concepto)
{
    if (abs($diff) < 0.005) {
        return;
    }

    if ($forma_de_pago === 'contado') {
        if ($diff > 0) {
            insertar_movimiento_caja($grupos_caja, $concepto, $diff, 0, $uid, $id_sucursal);
        } else {
            insertar_movimiento_caja($grupos_caja, $concepto, 0, abs($diff), $uid, $id_sucursal);
        }
        return;
    }

    if ($forma_de_pago === 'tarjeta') {
        if ($diff > 0) {
            insertar_movimiento_tarjeta($id_sucursal, 0, $id_venta_sucursal, $concepto, $diff, $uid, $grupos_caja);
        } else {
            insertar_movimiento_tarjeta($id_sucursal, 0, $id_venta_sucursal, $concepto, 0, $uid, $grupos_caja, abs($diff));
        }
        return;
    }

    if ($forma_de_pago === 'bizum') {
        if ($diff > 0) {
            insertar_movimiento_bizum($id_sucursal, 0, $id_venta_sucursal, $concepto, $diff, $uid, $grupos_caja);
        } else {
            insertar_movimiento_bizum($id_sucursal, 0, $id_venta_sucursal, $concepto, 0, $uid, $grupos_caja, abs($diff));
        }
        return;
    }

    if ($forma_de_pago === 'transferencia') {
        if ($diff > 0) {
            insertar_movimiento_transferencia($id_sucursal, 0, $id_venta_sucursal, $concepto, $diff, 0, $uid, $grupos_caja);
        } else {
            insertar_movimiento_transferencia($id_sucursal, 0, $id_venta_sucursal, $concepto, 0, abs($diff), $uid, $grupos_caja);
        }
        return;
    }

    if ($forma_de_pago === 'combinado') {
        $total = $cant_contado + $cant_tarjeta + $cant_bizum + $cant_transferencia;
        if ($total <= 0) {
            insertar_movimiento_caja($grupos_caja, $concepto, max(0, $diff), max(0, -$diff), $uid, $id_sucursal);
            return;
        }
        $partes = [
            ['tipo' => 'contado', 'old' => $cant_contado],
            ['tipo' => 'tarjeta', 'old' => $cant_tarjeta],
            ['tipo' => 'bizum', 'old' => $cant_bizum],
            ['tipo' => 'transferencia', 'old' => $cant_transferencia],
        ];
        foreach ($partes as $parte) {
            if ($parte['old'] <= 0) {
                continue;
            }
            $parte_diff = round($diff * ($parte['old'] / $total), 2);
            if (abs($parte_diff) < 0.005) {
                continue;
            }
            if ($parte['tipo'] === 'contado') {
                if ($parte_diff > 0) {
                    insertar_movimiento_caja($grupos_caja, $concepto, $parte_diff, 0, $uid, $id_sucursal);
                } else {
                    insertar_movimiento_caja($grupos_caja, $concepto, 0, abs($parte_diff), $uid, $id_sucursal);
                }
            } elseif ($parte['tipo'] === 'tarjeta') {
                if ($parte_diff > 0) {
                    insertar_movimiento_tarjeta($id_sucursal, 0, $id_venta_sucursal, $concepto, $parte_diff, $uid, $grupos_caja);
                } else {
                    insertar_movimiento_tarjeta($id_sucursal, 0, $id_venta_sucursal, $concepto, 0, $uid, $grupos_caja, abs($parte_diff));
                }
            } elseif ($parte['tipo'] === 'bizum') {
                if ($parte_diff > 0) {
                    insertar_movimiento_bizum($id_sucursal, 0, $id_venta_sucursal, $concepto, $parte_diff, $uid, $grupos_caja);
                } else {
                    insertar_movimiento_bizum($id_sucursal, 0, $id_venta_sucursal, $concepto, 0, $uid, $grupos_caja, abs($parte_diff));
                }
            } elseif ($parte['tipo'] === 'transferencia') {
                if ($parte_diff > 0) {
                    insertar_movimiento_transferencia($id_sucursal, 0, $id_venta_sucursal, $concepto, $parte_diff, 0, $uid, $grupos_caja);
                } else {
                    insertar_movimiento_transferencia($id_sucursal, 0, $id_venta_sucursal, $concepto, 0, abs($parte_diff), $uid, $grupos_caja);
                }
            }
        }
    }
}

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
    $nuevo_importe = isset($_POST['importe']) ? round((float) $_POST['importe'], 2) : 0;
    $uid = (int) $usuario_id;

    if ($id_venta <= 0 || $id_plazo <= 0 || $uid <= 0 || $nuevo_importe <= 0) {
        throw new Exception('Datos no válidos');
    }

    $item_modulo = basename(dirname(__DIR__));
    if (!usuario_puede_acceder_crud_tipo($usuario_privilegio_id, crud_id_listar_modulo($item_modulo), 'editar')) {
        throw new Exception('No tiene permisos para esta acción');
    }

    $stmtV = mysqli_prepare(
        $conexion,
        'SELECT id, estado, venta_plazos, id_sucursal, id_venta_sucursal, precio FROM ventas WHERE id = ? LIMIT 1'
    );
    if (!$stmtV) {
        throw new Exception(mysqli_error($conexion));
    }
    mysqli_stmt_bind_param($stmtV, 'i', $id_venta);
    mysqli_stmt_execute($stmtV);
    $rv = mysqli_stmt_get_result($stmtV);
    $venta = $rv ? mysqli_fetch_assoc($rv) : null;
    mysqli_stmt_close($stmtV);

    if (!$venta || strtolower((string) ($venta['venta_plazos'] ?? '')) !== 'si') {
        throw new Exception('Venta no encontrada o no es a plazos');
    }

    $estVenta = strtolower((string) ($venta['estado'] ?? ''));
    if ($estVenta === 'anulado' || $estVenta === 'anulada') {
        throw new Exception('La venta está anulada');
    }

    $id_sucursal = (int) ($venta['id_sucursal'] ?? 0);
    $id_venta_sucursal = (int) ($venta['id_venta_sucursal'] ?? 0);
    $edicion_solo_importe = (
        $estVenta === 'vendido'
        && venta_plazos_tiene_factura_generada($conexion, $id_venta, $id_sucursal)
    );

    $stmtPl = mysqli_prepare(
        $conexion,
        "SELECT vp.id, vp.estado, vp.importe, vp.metodo_pago, vp.fecha_vencimiento,
                vp.cantidad_contado, vp.cantidad_transferencia, vp.cantidad_bizum, vp.cantidad_tarjeta,
                (SELECT COUNT(*) FROM ventas_plazos v2 WHERE v2.id_venta = vp.id_venta AND v2.id <= vp.id) AS numero_cuota
         FROM ventas_plazos vp
         WHERE vp.id = ? AND vp.id_venta = ?
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
        throw new Exception('Plazo no encontrado');
    }

    $estPl = (string) ($plazo['estado'] ?? '');
    if (!in_array($estPl, ['Pagado', 'Pendiente', 'Vencido'], true)) {
        throw new Exception('Este plazo no se puede editar');
    }

    $importe_viejo = round((float) ($plazo['importe'] ?? 0), 2);
    if (abs($nuevo_importe - $importe_viejo) < 0.005) {
        throw new Exception('El importe no ha cambiado');
    }

    $numero_cuota = max(1, (int) ($plazo['numero_cuota'] ?? 0));
    $diff = round($nuevo_importe - $importe_viejo, 2);

    // Mirar qué columna cantidad_* tiene valor y actualizar esa con el nuevo importe
    $cc = (float) ($plazo['cantidad_contado'] ?? 0);
    $ct = (float) ($plazo['cantidad_tarjeta'] ?? 0);
    $cb = (float) ($plazo['cantidad_bizum'] ?? 0);
    $ctr = (float) ($plazo['cantidad_transferencia'] ?? 0);

    $campo_cantidad = '';
    $forma_de_pago = '';
    if ($ct > 0) {
        $campo_cantidad = 'cantidad_tarjeta';
        $forma_de_pago = 'tarjeta';
    } elseif ($cb > 0) {
        $campo_cantidad = 'cantidad_bizum';
        $forma_de_pago = 'bizum';
    } elseif ($ctr > 0) {
        $campo_cantidad = 'cantidad_transferencia';
        $forma_de_pago = 'transferencia';
    } elseif ($cc > 0) {
        $campo_cantidad = 'cantidad_contado';
        $forma_de_pago = 'contado';
    } else {
        throw new Exception('El plazo no tiene ninguna cantidad de pago registrada');
    }

    $cant_contado = 0.0;
    $cant_tarjeta = 0.0;
    $cant_bizum = 0.0;
    $cant_transferencia = 0.0;
    if ($campo_cantidad === 'cantidad_contado') {
        $cant_contado = $nuevo_importe;
    } elseif ($campo_cantidad === 'cantidad_tarjeta') {
        $cant_tarjeta = $nuevo_importe;
    } elseif ($campo_cantidad === 'cantidad_bizum') {
        $cant_bizum = $nuevo_importe;
    } elseif ($campo_cantidad === 'cantidad_transferencia') {
        $cant_transferencia = $nuevo_importe;
    }

    if (!$edicion_solo_importe && $estPl === 'Pagado') {
        $grupos_caja = 'Edición importe plazo venta';
        $concepto_caja = 'Ajuste por edición del plazo Nº ' . $numero_cuota . ' de la venta Nº ' . $id_venta_sucursal;
        editar_plazo_ajustar_caja(
            $forma_de_pago,
            $cant_contado,
            $cant_tarjeta,
            $cant_bizum,
            $cant_transferencia,
            $diff,
            $id_sucursal,
            $id_venta_sucursal,
            $uid,
            $grupos_caja,
            $concepto_caja
        );
    }

    $sql_update = '';
    if ($campo_cantidad === 'cantidad_tarjeta') {
        $sql_update = "UPDATE ventas_plazos SET importe = ?, cantidad_contado = 0, cantidad_transferencia = 0, cantidad_bizum = 0, cantidad_tarjeta = ? WHERE id = ? AND id_venta = ? AND estado IN ('Pagado', 'Pendiente', 'Vencido')";
    } elseif ($campo_cantidad === 'cantidad_bizum') {
        $sql_update = "UPDATE ventas_plazos SET importe = ?, cantidad_contado = 0, cantidad_transferencia = 0, cantidad_bizum = ?, cantidad_tarjeta = 0 WHERE id = ? AND id_venta = ? AND estado IN ('Pagado', 'Pendiente', 'Vencido')";
    } elseif ($campo_cantidad === 'cantidad_transferencia') {
        $sql_update = "UPDATE ventas_plazos SET importe = ?, cantidad_contado = 0, cantidad_transferencia = ?, cantidad_bizum = 0, cantidad_tarjeta = 0 WHERE id = ? AND id_venta = ? AND estado IN ('Pagado', 'Pendiente', 'Vencido')";
    } else {
        $sql_update = "UPDATE ventas_plazos SET importe = ?, cantidad_contado = ?, cantidad_transferencia = 0, cantidad_bizum = 0, cantidad_tarjeta = 0 WHERE id = ? AND id_venta = ? AND estado IN ('Pagado', 'Pendiente', 'Vencido')";
    }

    $stmtU = mysqli_prepare($conexion, $sql_update);
    if (!$stmtU) {
        throw new Exception(mysqli_error($conexion));
    }

    mysqli_stmt_bind_param($stmtU, 'ddii', $nuevo_importe, $nuevo_importe, $id_plazo, $id_venta);

    if (!mysqli_stmt_execute($stmtU)) {
        $err = mysqli_stmt_error($stmtU);
        mysqli_stmt_close($stmtU);
        throw new Exception($err !== '' ? $err : 'Error al ejecutar la actualización del plazo');
    }
    if (mysqli_stmt_affected_rows($stmtU) !== 1) {
        mysqli_stmt_close($stmtU);
        throw new Exception('No se pudo actualizar el plazo');
    }
    mysqli_stmt_close($stmtU);

    $stmtPend = mysqli_prepare(
        $conexion,
        "SELECT COUNT(id) AS c FROM ventas_plazos WHERE id_venta = ? AND estado <> 'Pagado'"
    );
    if (!$stmtPend) {
        throw new Exception(mysqli_error($conexion));
    }
    mysqli_stmt_bind_param($stmtPend, 'i', $id_venta);
    mysqli_stmt_execute($stmtPend);
    $rPend = mysqli_stmt_get_result($stmtPend);
    $rowPend = $rPend ? mysqli_fetch_assoc($rPend) : null;
    mysqli_stmt_close($stmtPend);
    $plazos_pendientes = (int) ($rowPend['c'] ?? 0);
    $todos_plazos_pagados = ($plazos_pendientes === 0);

    if ($todos_plazos_pagados) {
        $stmtSum = mysqli_prepare(
            $conexion,
            'SELECT COALESCE(SUM(importe), 0) AS total FROM ventas_plazos WHERE id_venta = ?'
        );
        if (!$stmtSum) {
            throw new Exception(mysqli_error($conexion));
        }
        mysqli_stmt_bind_param($stmtSum, 'i', $id_venta);
        mysqli_stmt_execute($stmtSum);
        $rSum = mysqli_stmt_get_result($stmtSum);
        $rowSum = $rSum ? mysqli_fetch_assoc($rSum) : null;
        mysqli_stmt_close($stmtSum);
        $total_plazos = round((float) ($rowSum['total'] ?? 0), 2);

        $stmtPrecio = mysqli_prepare($conexion, 'UPDATE ventas SET precio = ? WHERE id = ? LIMIT 1');
        if (!$stmtPrecio) {
            throw new Exception(mysqli_error($conexion));
        }
        mysqli_stmt_bind_param($stmtPrecio, 'di', $total_plazos, $id_venta);
        mysqli_stmt_execute($stmtPrecio);
        mysqli_stmt_close($stmtPrecio);
    } elseif (!$edicion_solo_importe) {
        $stmtPrecio = mysqli_prepare($conexion, 'UPDATE ventas SET precio = precio + ? WHERE id = ? LIMIT 1');
        if ($stmtPrecio) {
            mysqli_stmt_bind_param($stmtPrecio, 'di', $diff, $id_venta);
            mysqli_stmt_execute($stmtPrecio);
            mysqli_stmt_close($stmtPrecio);
        }
    }

    $accion_historico = 'Edición del importe del plazo Nº ' . $numero_cuota
        . ' de la venta Nº ' . $id_venta_sucursal
        . ' (' . number_format($importe_viejo, 2, ',', '.') . ' € → '
        . number_format($nuevo_importe, 2, ',', '.') . ' €; '
        . $campo_cantidad . ' actualizado)';
    if ($todos_plazos_pagados) {
        $accion_historico .= ' (precio venta recalculado: suma de plazos)';
    } elseif ($edicion_solo_importe) {
        $accion_historico .= ' (venta con factura, sin modificar total ni caja)';
    }
    insertAccionPlazoVenta($id_sucursal, $id_plazo, $id_venta, $uid, $accion_historico, 'Central');

    mysqli_close($conexion);

    echo json_encode([
        'success' => true,
        'message' => 'Plazo actualizado correctamente',
    ], JSON_UNESCAPED_UNICODE);
} catch (Exception $e) {
    if ($conexion) {
        mysqli_close($conexion);
    }
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => $e->getMessage()], JSON_UNESCAPED_UNICODE);
}
