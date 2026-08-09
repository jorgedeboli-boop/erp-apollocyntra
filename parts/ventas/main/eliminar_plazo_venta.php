<?php
/**
 * Elimina un plazo pendiente o vencido.
 * Plazos del contrato: solo venta en enfecha/vencido.
 * Plazos extra (posición > numero_plazos): permitido aunque la venta esté vendida; no modifica el precio.
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
        'SELECT id, estado, venta_plazos, id_sucursal, id_venta_sucursal, numero_plazos, precio
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

    $estVenta = strtolower(trim((string) ($venta['estado'] ?? '')));
    if (in_array($estVenta, ['anulado', 'anulada'], true)) {
        throw new Exception('No se puede eliminar plazos de una venta anulada');
    }

    $id_sucursal = (int) ($venta['id_sucursal'] ?? 0);
    $id_venta_sucursal = (int) ($venta['id_venta_sucursal'] ?? 0);
    $numero_plazos = (int) ($venta['numero_plazos'] ?? 0);

    $stmtPl = mysqli_prepare(
        $conexion,
        "SELECT vp.id, vp.estado, vp.fecha_vencimiento, vp.importe,
                (SELECT COUNT(*) FROM ventas_plazos v2 WHERE v2.id_venta = vp.id_venta AND v2.id <= vp.id) AS numero_cuota
         FROM ventas_plazos vp
         WHERE vp.id = ? AND vp.id_venta = ? AND vp.estado IN ('Pendiente', 'Vencido')
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
        throw new Exception('Plazo no encontrado o no se puede eliminar');
    }

    $numero_cuota = max(1, (int) ($plazo['numero_cuota'] ?? 0));
    $es_plazo_superior_contrato = ($numero_plazos > 0 && $numero_cuota > $numero_plazos);
    $importe_plazo = round((float) ($plazo['importe'] ?? 0), 2);

    if (!$es_plazo_superior_contrato) {
        if (!in_array($estVenta, ['enfecha', 'vencido'], true)) {
            throw new Exception('La venta no admite eliminar plazos en su estado actual');
        }
        if ($estVenta === 'vendido' && venta_plazos_tiene_factura_generada($conexion, $id_venta, $id_sucursal)) {
            throw new Exception('No se puede eliminar plazos: la venta ya tiene factura generada');
        }
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
    if (!$es_plazo_superior_contrato && (int) ($rowMin['min_id'] ?? 0) === $id_plazo) {
        throw new Exception('No se puede eliminar el primer plazo de la venta');
    }

    $estPl = (string) ($plazo['estado'] ?? '');
    $fecha_venc = trim((string) ($plazo['fecha_vencimiento'] ?? ''));
    if (
        !$es_plazo_superior_contrato
        && $estPl === 'Vencido'
        && $fecha_venc !== ''
        && substr($fecha_venc, 0, 10) !== '0000-00-00'
    ) {
        $tsVenc = strtotime(substr($fecha_venc, 0, 10));
        if ($tsVenc !== false && $tsVenc < strtotime('today')) {
            throw new Exception('No se puede eliminar un plazo vencido cuya fecha de vencimiento es anterior a hoy');
        }
    }

    $stmtMax = mysqli_prepare(
        $conexion,
        'SELECT MAX(id) AS max_id FROM ventas_plazos WHERE id_venta = ?'
    );
    if (!$stmtMax) {
        throw new Exception(mysqli_error($conexion));
    }
    mysqli_stmt_bind_param($stmtMax, 'i', $id_venta);
    mysqli_stmt_execute($stmtMax);
    $rmax = mysqli_stmt_get_result($stmtMax);
    $rowMax = $rmax ? mysqli_fetch_assoc($rmax) : null;
    mysqli_stmt_close($stmtMax);
    if ((int) ($rowMax['max_id'] ?? 0) !== $id_plazo) {
        throw new Exception('Solo se puede eliminar el último plazo generado');
    }

    $stmtDel = mysqli_prepare(
        $conexion,
        "DELETE FROM ventas_plazos WHERE id = ? AND id_venta = ? AND estado IN ('Pendiente', 'Vencido')"
    );
    if (!$stmtDel) {
        throw new Exception(mysqli_error($conexion));
    }
    mysqli_stmt_bind_param($stmtDel, 'ii', $id_plazo, $id_venta);
    mysqli_stmt_execute($stmtDel);
    if (mysqli_stmt_affected_rows($stmtDel) !== 1) {
        mysqli_stmt_close($stmtDel);
        throw new Exception('No se pudo eliminar el plazo');
    }
    mysqli_stmt_close($stmtDel);

    // Plazos extra (posición > numero_plazos): eliminar sin tocar el precio de la venta
    if (!$es_plazo_superior_contrato && $importe_plazo > 0) {
        $stmtPrecio = mysqli_prepare(
            $conexion,
            'UPDATE ventas SET precio = GREATEST(0, precio - ?) WHERE id = ? LIMIT 1'
        );
        if (!$stmtPrecio) {
            throw new Exception(mysqli_error($conexion));
        }
        mysqli_stmt_bind_param($stmtPrecio, 'di', $importe_plazo, $id_venta);
        if (!mysqli_stmt_execute($stmtPrecio)) {
            mysqli_stmt_close($stmtPrecio);
            throw new Exception(mysqli_stmt_error($stmtPrecio));
        }
        mysqli_stmt_close($stmtPrecio);
    }

    if ($estVenta === 'vencido') {
        $stmtVen = mysqli_prepare(
            $conexion,
            "SELECT COUNT(id) AS c FROM ventas_plazos WHERE id_venta = ? AND estado = 'Vencido'"
        );
        if ($stmtVen) {
            mysqli_stmt_bind_param($stmtVen, 'i', $id_venta);
            mysqli_stmt_execute($stmtVen);
            $rven = mysqli_stmt_get_result($stmtVen);
            $rowVen = $rven ? mysqli_fetch_assoc($rven) : null;
            mysqli_stmt_close($stmtVen);
            $vencidos = (int) ($rowVen['c'] ?? 0);
            if ($vencidos === 0) {
                $nuevo_estado = 'enfecha';
                $stmtUpV = mysqli_prepare($conexion, 'UPDATE ventas SET estado = ? WHERE id = ? LIMIT 1');
                if ($stmtUpV) {
                    mysqli_stmt_bind_param($stmtUpV, 'si', $nuevo_estado, $id_venta);
                    mysqli_stmt_execute($stmtUpV);
                    mysqli_stmt_close($stmtUpV);
                }
            }
        }
    }

    $accion_historico = 'Eliminación del plazo Nº ' . $numero_cuota . ' de la venta Nº ' . $id_venta_sucursal;
    if ($es_plazo_superior_contrato) {
        $accion_historico .= ' (plazo extra, sin modificar el total de la venta)';
    } elseif ($importe_plazo > 0) {
        $accion_historico .= ' (total venta reducido en ' . number_format($importe_plazo, 2, ',', '.') . ' €)';
    }
    insertAccionPlazoVenta($id_sucursal, $id_plazo, $id_venta, $uid, $accion_historico, 'Central');

    mysqli_close($conexion);

    echo json_encode([
        'success' => true,
        'message' => 'Plazo eliminado correctamente',
    ], JSON_UNESCAPED_UNICODE);
} catch (Exception $e) {
    if ($conexion) {
        mysqli_close($conexion);
    }
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => $e->getMessage()], JSON_UNESCAPED_UNICODE);
}
