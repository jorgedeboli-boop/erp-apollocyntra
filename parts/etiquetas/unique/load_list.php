<?php
/**
 * Server-side processing para DataTable de etiquetas pendientes.
 */

require_once '../../../include/session.php';
require_once '../../../include/functions.php';
require_once __DIR__ . '/etiquetas_filtros.php';

header('Content-Type: application/json');

try {
    $conexion = conectar_bd();

    $draw = isset($_POST['draw']) ? (int) $_POST['draw'] : 1;
    $start = isset($_POST['start']) ? (int) $_POST['start'] : 0;
    $length = isset($_POST['length']) ? (int) $_POST['length'] : 10;
    $searchValue = isset($_POST['search']['value']) ? trim((string) $_POST['search']['value']) : '';

    $filtros = array(
        'periodo' => isset($_POST['filtro_periodo']) ? trim((string) $_POST['filtro_periodo']) : '',
        'fecha_desde' => isset($_POST['filtro_fecha_desde']) ? trim((string) $_POST['filtro_fecha_desde']) : '',
        'fecha_hasta' => isset($_POST['filtro_fecha_hasta']) ? trim((string) $_POST['filtro_fecha_hasta']) : '',
        'search' => $searchValue,
    );

    $built = etiquetas_build_where($conexion, $filtros);
    $whereClause = $built['where'];
    $params = $built['params'];
    $types = $built['types'];

    $filtrosBase = array(
        'periodo' => '',
        'fecha_desde' => '',
        'fecha_hasta' => '',
        'search' => '',
    );
    $builtBase = etiquetas_build_where($conexion, $filtrosBase);
    $whereBase = $builtBase['where'];
    $paramsBase = $builtBase['params'];
    $typesBase = $builtBase['types'];

    $fromJoin = '
        FROM articulos_venta av
        LEFT JOIN usuarios u ON av.creado_por = u.id_usuario
    ';

    $query_total = "SELECT COUNT(*) AS total $fromJoin $whereBase";
    if ($typesBase !== '') {
        $stmt_total = mysqli_prepare($conexion, $query_total);
        etiquetas_mysqli_bind_params($stmt_total, $typesBase, $paramsBase);
        mysqli_stmt_execute($stmt_total);
        $result_total = mysqli_stmt_get_result($stmt_total);
        $row_total = mysqli_fetch_assoc($result_total);
        mysqli_stmt_close($stmt_total);
    } else {
        $result_total = mysqli_query($conexion, $query_total);
        $row_total = mysqli_fetch_assoc($result_total);
    }
    $recordsTotal = (int) ($row_total['total'] ?? 0);

    $query_filtered = "SELECT COUNT(*) AS total $fromJoin $whereClause";
    if ($types !== '') {
        $stmt_filtered = mysqli_prepare($conexion, $query_filtered);
        etiquetas_mysqli_bind_params($stmt_filtered, $types, $params);
        mysqli_stmt_execute($stmt_filtered);
        $result_filtered = mysqli_stmt_get_result($stmt_filtered);
        $row_filtered = mysqli_fetch_assoc($result_filtered);
        mysqli_stmt_close($stmt_filtered);
    } else {
        $result_filtered = mysqli_query($conexion, $query_filtered);
        $row_filtered = mysqli_fetch_assoc($result_filtered);
    }
    $recordsFiltered = (int) ($row_filtered['total'] ?? 0);

    $query = "
        SELECT
            av.id AS id_articulo,
            av.descripcion,
            av.origen_articulo,
            av.fecha_alta,
            av.precio,
            av.peso,
            av.tipo,
            u.nombre_usuario
        $fromJoin
        $whereClause
        ORDER BY av.id DESC
        LIMIT ?, ?
    ";

    $allParams = array_merge($params, array($start, $length));
    $allTypes = $types . 'ii';

    $stmt = mysqli_prepare($conexion, $query);
    etiquetas_mysqli_bind_params($stmt, $allTypes, $allParams);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);

    $data = array();
    while ($row = mysqli_fetch_assoc($result)) {
        $sku = (int) ($row['id_articulo'] ?? 0);
        $skuHtml = $sku > 0 ? (string) $sku : '-';

        $data[] = array(
            $skuHtml,
            htmlspecialchars($row['descripcion'] ?? ''),
            etiquetas_format_origen($row['origen_articulo'] ?? ''),
            etiquetas_format_fecha($row['fecha_alta'] ?? ''),
            number_format((float) ($row['precio'] ?? 0), 0, ',', '.') . ' €',
            number_format((float) ($row['peso'] ?? 0), 2, ',', '.') . ' grs',
            etiquetas_format_tipo($row['tipo'] ?? ''),
            htmlspecialchars($row['nombre_usuario'] ?: '---'),
            '<span class="fw-semibold">1</span>',
            etiquetas_boton_imprimir($sku),
        );
    }

    mysqli_stmt_close($stmt);
    mysqli_close($conexion);

    echo json_encode(array(
        'draw' => $draw,
        'recordsTotal' => $recordsTotal,
        'recordsFiltered' => $recordsFiltered,
        'data' => $data,
    ));
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(array('error' => $e->getMessage()));
}
