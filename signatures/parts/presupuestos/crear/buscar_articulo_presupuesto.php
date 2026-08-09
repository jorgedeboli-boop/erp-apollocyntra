<?php
/**
 * Búsqueda de artículos en venta para líneas de presupuesto (por empresa).
 * A diferencia de parts/ventas/crear/buscar_articulo.php, no exige que el stock
 * esté en la sucursal mostrada: basta con que el artículo pertenezca a la misma empresa.
 */
require_once __DIR__ . '/../../../include/session.php';
require_once __DIR__ . '/../../../include/functions.php';

header('Content-Type: application/json; charset=utf-8');

try {
    $q = isset($_GET['q']) ? trim((string)$_GET['q']) : '';
    $rel_id_empresa = isset($_GET['rel_id_empresa']) ? (int)$_GET['rel_id_empresa'] : 0;
    $id_sucursal = isset($_GET['id_sucursal']) ? (int)$_GET['id_sucursal'] : 0;

    if ($rel_id_empresa <= 0) {
        throw new Exception('Empresa no indicada');
    }
    if ($q === '') {
        throw new Exception('Indique ID o texto de búsqueda');
    }

    $conexion = conectar_bd();

    $selectArticulo = 'SELECT av.id, av.id AS sku, av.descripcion, av.peso, av.precio, av.tipo AS tipo
              FROM articulos_venta av';

    $articulo = null;

    if (ctype_digit($q)) {
        $idNum = (int)$q;

        if ($id_sucursal > 0) {
            $stmt = mysqli_prepare(
                $conexion,
                $selectArticulo . ' WHERE av.id = ? AND av.id_sucursal_destino = ? AND av.estado = \'enventa\' LIMIT 1'
            );
            mysqli_stmt_bind_param($stmt, 'ii', $idNum, $id_sucursal);
            mysqli_stmt_execute($stmt);
            $res = mysqli_stmt_get_result($stmt);
            $articulo = mysqli_fetch_assoc($res);
            mysqli_stmt_close($stmt);

            if (!$articulo) {
                $stmt = mysqli_prepare(
                    $conexion,
                    $selectArticulo . ' WHERE av.id_articulo_sucursal = ? AND av.id_sucursal_destino = ? AND av.estado = \'enventa\' LIMIT 1'
                );
                mysqli_stmt_bind_param($stmt, 'ii', $idNum, $id_sucursal);
                mysqli_stmt_execute($stmt);
                $res = mysqli_stmt_get_result($stmt);
                $articulo = mysqli_fetch_assoc($res);
                mysqli_stmt_close($stmt);
            }
        }

        if (!$articulo) {
            $stmt = mysqli_prepare(
                $conexion,
                $selectArticulo . '
                INNER JOIN sucursal s ON s.id_sucursal = av.id_sucursal_destino
                WHERE av.id = ? AND s.empresa_id = ? AND av.estado = \'enventa\'
                LIMIT 1'
            );
            mysqli_stmt_bind_param($stmt, 'ii', $idNum, $rel_id_empresa);
            mysqli_stmt_execute($stmt);
            $res = mysqli_stmt_get_result($stmt);
            $articulo = mysqli_fetch_assoc($res);
            mysqli_stmt_close($stmt);
        }

        if (!$articulo && $id_sucursal > 0) {
            $stmt = mysqli_prepare(
                $conexion,
                $selectArticulo . '
                INNER JOIN sucursal s ON s.id_sucursal = av.id_sucursal_destino
                WHERE av.id_articulo_sucursal = ? AND s.empresa_id = ? AND av.estado = \'enventa\'
                AND av.id_sucursal_destino = ?
                LIMIT 1'
            );
            mysqli_stmt_bind_param($stmt, 'iii', $idNum, $rel_id_empresa, $id_sucursal);
            mysqli_stmt_execute($stmt);
            $res = mysqli_stmt_get_result($stmt);
            $articulo = mysqli_fetch_assoc($res);
            mysqli_stmt_close($stmt);
        }
    }

    if (!$articulo && strlen($q) >= 3) {
        $like = '%' . $q . '%';
        $stmt = mysqli_prepare(
            $conexion,
            $selectArticulo . '
            INNER JOIN sucursal s ON s.id_sucursal = av.id_sucursal_destino
            WHERE s.empresa_id = ? AND av.estado = \'enventa\'
            AND av.descripcion LIKE ?
            ORDER BY av.id DESC
            LIMIT 1'
        );
        mysqli_stmt_bind_param($stmt, 'is', $rel_id_empresa, $like);
        mysqli_stmt_execute($stmt);
        $res = mysqli_stmt_get_result($stmt);
        $articulo = mysqli_fetch_assoc($res);
        mysqli_stmt_close($stmt);
    }

    mysqli_close($conexion);

    if ($articulo) {
        echo json_encode([
            'success' => true,
            'encontrado' => true,
            'articulo' => $articulo,
        ]);
        return;
    }

    echo json_encode([
        'success' => true,
        'encontrado' => false,
        'message' => 'Artículo no encontrado para esta empresa o no está en venta',
    ]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
