<?php
/**
 * Lista comprobantes de venta (tabla ventas_imagenes, archivos en /photos).
 */
require_once __DIR__ . '/../../../include/session.php';
require_once __DIR__ . '/../../../include/functions.php';

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
    $stmt = mysqli_prepare($conexion, 'SELECT id FROM ventas WHERE id = ? LIMIT 1');
    if (!$stmt) {
        throw new Exception(mysqli_error($conexion));
    }
    mysqli_stmt_bind_param($stmt, 'i', $id_venta);
    mysqli_stmt_execute($stmt);
    $res = mysqli_stmt_get_result($stmt);
    $ok = $res && mysqli_fetch_assoc($res);
    mysqli_stmt_close($stmt);
    if (!$ok) {
        mysqli_close($conexion);
        echo json_encode(['success' => true, 'imagenes' => [], 'total' => 0]);
        exit;
    }

    $stmt2 = mysqli_prepare(
        $conexion,
        'SELECT id, src FROM ventas_imagenes WHERE id_venta = ? ORDER BY id DESC'
    );
    if (!$stmt2) {
        throw new Exception(mysqli_error($conexion));
    }
    mysqli_stmt_bind_param($stmt2, 'i', $id_venta);
    mysqli_stmt_execute($stmt2);
    $res2 = mysqli_stmt_get_result($stmt2);

    $imagenes = [];
    if ($res2) {
        while ($row = mysqli_fetch_assoc($res2)) {
            $src = (string) ($row['src'] ?? '');
            if ($src === '') {
                continue;
            }
            $ruta = __DIR__ . '/../../../photos/' . $src;
            if (is_file($ruta)) {
                $imagenes[] = [
                    'id_foto' => (int) $row['id'],
                    'foto' => $src,
                ];
            }
        }
    }
    mysqli_stmt_close($stmt2);
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
