<?php
/**
 * Lista fotos del ticket en articulos_venta_imagenes (todas las filas cuyo id_articulo_venta
 * pertenece a alguna línea de ventas del mismo id_venta_sucursal + id_venta_sucursal).
 */
require_once __DIR__ . '/../../../include/session.php';
require_once __DIR__ . '/../../../include/functions.php';
require_once __DIR__ . '/_ticket_articulos_ids.php';

header('Content-Type: application/json; charset=utf-8');

try {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        http_response_code(405);
        echo json_encode(['success' => false, 'error' => 'Método no permitido']);
        exit;
    }

    $id_venta = isset($_POST['id_venta']) ? (int) $_POST['id_venta'] : 0;
    if ($id_venta <= 0) {
        throw new Exception('ID de venta no válido');
    }

    $conexion = conectar_bd();
    $ids = ventas_main_obtener_ids_articulo_venta_ticket($conexion, $id_venta);
    if (count($ids) === 0) {
        mysqli_close($conexion);
        echo json_encode(['success' => true, 'imagenes' => [], 'total' => 0]);
        exit;
    }

    $placeholders = implode(',', array_fill(0, count($ids), '?'));
    $types = str_repeat('i', count($ids));
    $sql = 'SELECT id, src, id_articulo_venta FROM articulos_venta_imagenes
            WHERE id_articulo_venta IN (' . $placeholders . ') ORDER BY id DESC';

    $stmt = mysqli_prepare($conexion, $sql);
    if (!$stmt) {
        throw new Exception(mysqli_error($conexion));
    }
    mysqli_stmt_bind_param($stmt, $types, ...$ids);
    mysqli_stmt_execute($stmt);
    $res = mysqli_stmt_get_result($stmt);

    $imagenes = [];
    if ($res) {
        while ($row = mysqli_fetch_assoc($res)) {
            $src = (string) ($row['src'] ?? '');
            if ($src === '') {
                continue;
            }
            $ruta = __DIR__ . '/../../../photos/' . $src;
            if (is_file($ruta)) {
                $imagenes[] = [
                    'id_foto' => (int) $row['id'],
                    'foto' => $src,
                    'id_articulo_venta' => (int) $row['id_articulo_venta'],
                ];
            }
        }
    }
    mysqli_stmt_close($stmt);
    mysqli_close($conexion);

    echo json_encode([
        'success' => true,
        'imagenes' => $imagenes,
        'total' => count($imagenes),
    ]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
