<?php
/**
 * IDs de articulos_venta del ticket referenciado por ventas.id = $id_venta_ref.
 *
 * Prioridad:
 * 1. rel_articulos_venta.rel_id_venta (modelo actual, una fila ventas por ticket)
 * 2. Legacy multi-línea: varias filas ventas con mismo id_sucursal + id_venta_sucursal
 * 3. Legacy una fila: id_articulo_venta de la fila ventas.id = $id_venta_ref
 *
 * @return int[]
 */
function ventas_main_obtener_ids_articulo_venta_ticket(mysqli $conexion, int $id_venta_ref)
{
    if ($id_venta_ref <= 0) {
        return [];
    }

    $stmt = mysqli_prepare(
        $conexion,
        'SELECT id_sucursal, id_venta_sucursal FROM ventas WHERE id = ? LIMIT 1'
    );
    if (!$stmt) {
        return [];
    }
    mysqli_stmt_bind_param($stmt, 'i', $id_venta_ref);
    mysqli_stmt_execute($stmt);
    $res = mysqli_stmt_get_result($stmt);
    $row = $res ? mysqli_fetch_assoc($res) : null;
    mysqli_stmt_close($stmt);
    if (!$row) {
        return [];
    }

    $sid = (int) ($row['id_sucursal'] ?? 0);
    $nvs = (int) ($row['id_venta_sucursal'] ?? 0);
    if ($sid <= 0 || $nvs <= 0) {
        return [];
    }

    $ids = [];

    $stmtRel = mysqli_prepare(
        $conexion,
        'SELECT DISTINCT r.sku_articulo AS id_articulo_venta
         FROM rel_articulos_venta r
         WHERE r.sucursal_venta = ? AND r.rel_id_venta = ?'
    );
    if ($stmtRel) {
        mysqli_stmt_bind_param($stmtRel, 'ii', $sid, $id_venta_ref);
        mysqli_stmt_execute($stmtRel);
        $resRel = mysqli_stmt_get_result($stmtRel);
        if ($resRel) {
            while ($r = mysqli_fetch_assoc($resRel)) {
                $idAv = (int) ($r['id_articulo_venta'] ?? 0);
                if ($idAv > 0) {
                    $ids[] = $idAv;
                }
            }
        }
        mysqli_stmt_close($stmtRel);
    }

    if (count($ids) > 0) {
        return array_values(array_unique($ids));
    }

    $filasTicket = 0;
    $stmtCount = mysqli_prepare(
        $conexion,
        'SELECT COUNT(*) AS c FROM ventas WHERE id_sucursal = ? AND id_venta_sucursal = ?'
    );
    if ($stmtCount) {
        mysqli_stmt_bind_param($stmtCount, 'ii', $sid, $nvs);
        mysqli_stmt_execute($stmtCount);
        $resCount = mysqli_stmt_get_result($stmtCount);
        $rowCount = $resCount ? mysqli_fetch_assoc($resCount) : null;
        mysqli_stmt_close($stmtCount);
        $filasTicket = (int) ($rowCount['c'] ?? 0);
    }

    if ($filasTicket > 1) {
        $stmtLegacy = mysqli_prepare(
            $conexion,
            'SELECT DISTINCT id_articulo_venta FROM ventas
             WHERE id_sucursal = ? AND id_venta_sucursal = ?
               AND id_articulo_venta IS NOT NULL AND id_articulo_venta > 0'
        );
        if ($stmtLegacy) {
            mysqli_stmt_bind_param($stmtLegacy, 'ii', $sid, $nvs);
            mysqli_stmt_execute($stmtLegacy);
            $resLegacy = mysqli_stmt_get_result($stmtLegacy);
            if ($resLegacy) {
                while ($r = mysqli_fetch_assoc($resLegacy)) {
                    $ids[] = (int) $r['id_articulo_venta'];
                }
            }
            mysqli_stmt_close($stmtLegacy);
        }

        return array_values(array_unique($ids));
    }

    $stmtOne = mysqli_prepare(
        $conexion,
        'SELECT id_articulo_venta FROM ventas
         WHERE id = ? AND id_articulo_venta IS NOT NULL AND id_articulo_venta > 0
         LIMIT 1'
    );
    if ($stmtOne) {
        mysqli_stmt_bind_param($stmtOne, 'i', $id_venta_ref);
        mysqli_stmt_execute($stmtOne);
        $resOne = mysqli_stmt_get_result($stmtOne);
        $rowOne = $resOne ? mysqli_fetch_assoc($resOne) : null;
        mysqli_stmt_close($stmtOne);
        if ($rowOne) {
            $ids[] = (int) $rowOne['id_articulo_venta'];
        }
    }

    return array_values(array_unique($ids));
}
