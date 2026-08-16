<?php
/**
 * Lista imágenes/documentos del artículo (tabla articulos_venta_imagenes).
 */
require_once __DIR__ . '/../../../include/session.php';
require_once __DIR__ . '/../../../include/functions.php';

header('Content-Type: application/json; charset=utf-8');

try {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        http_response_code(405);
        echo json_encode(array('error' => 'Método no permitido'));
        exit;
    }

    $id_articulo = isset($_POST['id_articulo']) ? (int) $_POST['id_articulo'] : 0;
    if ($id_articulo <= 0) {
        throw new Exception('ID de artículo no válido');
    }

    $conexion = conectar_bd();

    $stArt = mysqli_prepare($conexion, 'SELECT sku FROM articulos WHERE sku = ? LIMIT 1');
    if (!$stArt) {
        throw new Exception(mysqli_error($conexion));
    }
    mysqli_stmt_bind_param($stArt, 'i', $id_articulo);
    mysqli_stmt_execute($stArt);
    $rArt = mysqli_stmt_get_result($stArt);
    if (!$rArt || !mysqli_fetch_assoc($rArt)) {
        mysqli_stmt_close($stArt);
        mysqli_close($conexion);
        throw new Exception('Artículo no encontrado');
    }
    mysqli_stmt_close($stArt);

    $query = 'SELECT id, src FROM articulos_venta_imagenes WHERE rel_sku_articulo = ? ORDER BY id DESC';
    $stmt = mysqli_prepare($conexion, $query);
    if (!$stmt) {
        throw new Exception(mysqli_error($conexion));
    }
    mysqli_stmt_bind_param($stmt, 'i', $id_articulo);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);

    $imagenes = array();
    if ($result) {
        $base = __DIR__ . '/../../../photos/';
        while ($row = mysqli_fetch_assoc($result)) {
            $src = isset($row['src']) ? $row['src'] : '';
            if ($src === '') {
                continue;
            }
            $ruta = $base . $src;
            if (file_exists($ruta)) {
                $imagenes[] = array(
                    'id_foto' => (int) $row['id'],
                    'foto' => $src,
                );
            }
        }
    }
    mysqli_stmt_close($stmt);
    mysqli_close($conexion);

    echo json_encode(array(
        'success' => true,
        'imagenes' => $imagenes,
        'total' => count($imagenes),
    ));
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(array(
        'success' => false,
        'error' => $e->getMessage(),
    ));
}
