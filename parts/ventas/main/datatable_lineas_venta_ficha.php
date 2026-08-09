<?php
/**
 * JSON de líneas (artículos) de una venta — DataTable AJAX.
 */
require_once __DIR__ . '/../../../include/session.php';
require_once __DIR__ . '/../../../include/functions.php';

header('Content-Type: application/json; charset=utf-8');

$id_venta = isset($_POST['id_venta']) ? (int) $_POST['id_venta'] : 0;
if ($id_venta <= 0) {
    echo json_encode(['success' => false, 'message' => 'id_venta no válido', 'data' => []]);
    exit;
}

$conexion = conectar_bd();
if (!$conexion) {
    echo json_encode(['success' => false, 'message' => 'Sin conexión', 'data' => []]);
    exit;
}

$stmtV = mysqli_prepare(
    $conexion,
    'SELECT id, id_sucursal, id_venta_sucursal FROM ventas WHERE id = ? LIMIT 1'
);
if (!$stmtV) {
    echo json_encode(['success' => false, 'message' => 'Error al consultar venta', 'data' => []]);
    mysqli_close($conexion);
    exit;
}
mysqli_stmt_bind_param($stmtV, 'i', $id_venta);
mysqli_stmt_execute($stmtV);
$resV = mysqli_stmt_get_result($stmtV);
$venta = $resV ? mysqli_fetch_assoc($resV) : null;
mysqli_stmt_close($stmtV);

if (!$venta) {
    echo json_encode(['success' => false, 'message' => 'Venta no encontrada', 'data' => []]);
    mysqli_close($conexion);
    exit;
}

$sid = (int) $venta['id_sucursal'];
$nvs = (int) $venta['id_venta_sucursal'];
$lineas = [];

$stmtL = mysqli_prepare(
    $conexion,
    'SELECT r.id_rel_art_venta AS id,
            r.sku_articulo AS id_articulo_venta,
            r.precio_venta AS precio,
            av.descripcion, av.peso, av.tipo, r.estado_rel_Articulo AS estado_articulo_av
     FROM rel_articulos_venta r
     INNER JOIN articulos_venta av ON av.id = r.sku_articulo
     WHERE r.sucursal_venta = ? AND r.rel_id_venta = ?
     ORDER BY r.id_rel_art_venta ASC'
);
if ($stmtL) {
    mysqli_stmt_bind_param($stmtL, 'ii', $sid, $id_venta);
    mysqli_stmt_execute($stmtL);
    $resL = mysqli_stmt_get_result($stmtL);
    if ($resL) {
        while ($lv = mysqli_fetch_assoc($resL)) {
            $lineas[] = [
                'id_articulo_venta' => (int) ($lv['id_articulo_venta'] ?? 0),
                'descripcion' => (string) ($lv['descripcion'] ?? ''),
                'tipo' => (string) ($lv['tipo'] ?? ''),
                'peso' => (float) ($lv['peso'] ?? 0),
                'precio' => (float) ($lv['precio'] ?? 0),
                'estado_articulo_av' => (string) ($lv['estado_articulo_av'] ?? ''),
            ];
        }
    }
    mysqli_stmt_close($stmtL);
}

if (count($lineas) === 0) {
    $stmtL2 = mysqli_prepare(
        $conexion,
        'SELECT v.*, av.descripcion, av.peso, av.tipo, rav.estado_rel_Articulo AS estado_articulo_av
         FROM ventas v
         LEFT JOIN articulos_venta av ON v.id_articulo_venta = av.id
         LEFT JOIN rel_articulos_venta rav ON rav.sku_articulo = v.id_articulo_venta AND rav.rel_id_venta = v.id AND rav.sucursal_venta = v.id_sucursal
         WHERE v.id_sucursal = ? AND v.id_venta_sucursal = ?
         ORDER BY v.id ASC'
    );
    if ($stmtL2) {
        mysqli_stmt_bind_param($stmtL2, 'ii', $sid, $nvs);
        mysqli_stmt_execute($stmtL2);
        $resL2 = mysqli_stmt_get_result($stmtL2);
        if ($resL2) {
            while ($lv = mysqli_fetch_assoc($resL2)) {
                $lineas[] = [
                    'id_articulo_venta' => (int) ($lv['id_articulo_venta'] ?? 0),
                    'descripcion' => (string) ($lv['descripcion'] ?? ''),
                    'tipo' => (string) ($lv['tipo'] ?? ''),
                    'peso' => (float) ($lv['peso'] ?? 0),
                    'precio' => (float) ($lv['precio'] ?? 0),
                    'estado_articulo_av' => (string) ($lv['estado_articulo_av'] ?? ''),
                ];
            }
        }
        mysqli_stmt_close($stmtL2);
    }
}

mysqli_close($conexion);

echo json_encode(['success' => true, 'data' => $lineas], JSON_UNESCAPED_UNICODE);
