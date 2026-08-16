<?php
/**
 * Total de etiquetas pendientes según filtros activos.
 */

require_once '../../../include/session.php';
require_once '../../../include/functions.php';
require_once __DIR__ . '/etiquetas_filtros.php';

header('Content-Type: application/json');

try {
    $conexion = conectar_bd();

    $filtros = array(
        'periodo' => isset($_POST['filtro_periodo']) ? trim((string) $_POST['filtro_periodo']) : '',
        'fecha_desde' => isset($_POST['filtro_fecha_desde']) ? trim((string) $_POST['filtro_fecha_desde']) : '',
        'fecha_hasta' => isset($_POST['filtro_fecha_hasta']) ? trim((string) $_POST['filtro_fecha_hasta']) : '',
        'search' => isset($_POST['search']) ? trim((string) $_POST['search']) : '',
    );

    $built = etiquetas_build_where($conexion, $filtros);
    $whereClause = $built['where'];
    $params = $built['params'];
    $types = $built['types'];

    $query = "
        SELECT COUNT(*) AS total
        FROM articulos_venta av
        LEFT JOIN usuarios u ON av.creado_por = u.id_usuario
        $whereClause
    ";

    if ($types !== '') {
        $stmt = mysqli_prepare($conexion, $query);
        etiquetas_mysqli_bind_params($stmt, $types, $params);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        $row = mysqli_fetch_assoc($result);
        mysqli_stmt_close($stmt);
    } else {
        $result = mysqli_query($conexion, $query);
        $row = mysqli_fetch_assoc($result);
    }

    mysqli_close($conexion);

    echo json_encode(array(
        'success' => true,
        'total' => (int) ($row['total'] ?? 0),
    ));
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(array(
        'success' => false,
        'error' => $e->getMessage(),
        'total' => 0,
    ));
}
