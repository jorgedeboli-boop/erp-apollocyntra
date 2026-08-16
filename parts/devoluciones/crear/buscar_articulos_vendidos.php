<?php
/**
 * Búsqueda asíncrona de artículos vendidos por SKU (o descripción).
 * Solo artículos con estado 'vendido' o 'vendido_web'.
 * A partir de 3 caracteres.
 */

require_once '../../../include/session.php';
require_once '../../../include/functions.php';

header('Content-Type: application/json; charset=utf-8');

try {
    $q = isset($_GET['q']) ? trim($_GET['q']) : '';
    if (strlen($q) < 3) {
        echo json_encode(['success' => true, 'data' => []]);
        exit;
    }

    $conexion = conectar_bd();
    $pattern = '%' . $q . '%';

    $query = "
        SELECT 
            av.id,
            av.id as sku,
            av.descripcion,
            av.peso,
            av.precio,
            av.estado
        FROM articulos_venta av
        WHERE av.estado IN ('vendido', 'vendido_web')
        AND (CAST(av.id AS CHAR) LIKE ? OR av.descripcion LIKE ?)
        ORDER BY av.id DESC
        LIMIT 25
    ";
    $stmt = mysqli_prepare($conexion, $query);
    if (!$stmt) {
        throw new Exception('Error preparando consulta: ' . mysqli_error($conexion));
    }
    mysqli_stmt_bind_param($stmt, 'ss', $pattern, $pattern);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);

    $data = [];
    while ($row = mysqli_fetch_assoc($result)) {
        $data[] = [
            'id' => (int)$row['id'],
            'sku' => (int)$row['sku'],
            'descripcion' => $row['descripcion'] ?: '',
            'peso' => $row['peso'],
            'precio' => $row['precio'],
            'estado' => $row['estado'],
        ];
    }
    mysqli_stmt_close($stmt);
    mysqli_close($conexion);

    echo json_encode(['success' => true, 'data' => $data]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => $e->getMessage(), 'data' => []]);
}
