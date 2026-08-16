<?php
/**
 * Búsqueda de artículos para líneas de presupuesto (empresa del usuario).
 */
require_once __DIR__ . '/../../../include/session.php';
require_once __DIR__ . '/../../../include/functions.php';

header('Content-Type: application/json; charset=utf-8');

try {
    $q = isset($_GET['q']) ? trim((string) $_GET['q']) : '';
    $rel_id_empresa = obtener_rel_id_empresa_sesion();
    if (isset($_GET['rel_id_empresa']) && (int) $_GET['rel_id_empresa'] > 0) {
        $rel_id_empresa = (int) $_GET['rel_id_empresa'];
    }

    if ($rel_id_empresa <= 0) {
        throw new Exception('Empresa no indicada');
    }
    if ($q === '') {
        throw new Exception('Indique SKU o texto de búsqueda');
    }

    $conexion = conectar_bd();
    $articulo = null;

    $selectArticulo = 'SELECT a.sku AS id, a.sku, a.descripcion, a.precio, a.estado
              FROM articulos a';

    if (ctype_digit($q)) {
        $idNum = (int) $q;
        $stmt = mysqli_prepare(
            $conexion,
            $selectArticulo . ' WHERE a.sku = ? AND a.empresa_id_rel = ? LIMIT 1'
        );
        mysqli_stmt_bind_param($stmt, 'ii', $idNum, $rel_id_empresa);
        mysqli_stmt_execute($stmt);
        $res = mysqli_stmt_get_result($stmt);
        $articulo = $res ? mysqli_fetch_assoc($res) : null;
        mysqli_stmt_close($stmt);
    }

    if (!$articulo && strlen($q) >= 3) {
        $like = '%' . $q . '%';
        $stmt = mysqli_prepare(
            $conexion,
            $selectArticulo . '
            WHERE a.empresa_id_rel = ?
            AND a.descripcion LIKE ?
            ORDER BY a.sku DESC
            LIMIT 1'
        );
        mysqli_stmt_bind_param($stmt, 'is', $rel_id_empresa, $like);
        mysqli_stmt_execute($stmt);
        $res = mysqli_stmt_get_result($stmt);
        $articulo = $res ? mysqli_fetch_assoc($res) : null;
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
        'message' => 'Artículo no encontrado para esta empresa',
    ]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
