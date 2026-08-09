<?php
/**
 * Server-side processing para DataTable de servicios
 */
require_once '../../../include/session.php';
require_once '../../../include/functions.php';

header('Content-Type: application/json');

function servicios_label_unidad($v)
{
    $map = [
        'hora' => 'Hora',
        'media_hora' => 'Media hora',
        'dia' => 'Día',
        'sesion' => 'Sesión',
    ];
    return $map[$v] ?? $v;
}

function servicios_label_tipo_fact($v)
{
    $map = [
        'por_hora' => 'Por hora',
        'precio_fijo' => 'Precio fijo',
        'por_sesion' => 'Por sesión',
    ];
    return $map[$v] ?? $v;
}

try {
    $conexion = conectar_bd();

    $draw = isset($_POST['draw']) ? (int)$_POST['draw'] : 1;
    $start = isset($_POST['start']) ? (int)$_POST['start'] : 0;
    $length = isset($_POST['length']) ? (int)$_POST['length'] : 10;
    $searchValue = isset($_POST['search']['value']) ? $_POST['search']['value'] : '';

    $filtro_empresa = isset($_POST['filtro_empresa']) ? trim($_POST['filtro_empresa']) : '';
    $filtro_activo = isset($_POST['filtro_activo']) ? trim($_POST['filtro_activo']) : '';
    $filtro_tipo_fact = isset($_POST['filtro_tipo_fact']) ? trim($_POST['filtro_tipo_fact']) : '';

    $whereConditions = [];
    $params = [];
    $types = '';

    if ($filtro_empresa !== '') {
        $whereConditions[] = 's.rel_id_empresa = ?';
        $params[] = (int)$filtro_empresa;
        $types .= 'i';
    }

    if ($filtro_activo !== '') {
        $whereConditions[] = 's.activo = ?';
        $params[] = (int)$filtro_activo;
        $types .= 'i';
    }

    if ($filtro_tipo_fact !== '') {
        $whereConditions[] = 's.tipo_facturacion = ?';
        $params[] = $filtro_tipo_fact;
        $types .= 's';
    }

    if (!empty($searchValue)) {
        $whereConditions[] = '(
            CAST(s.id AS CHAR) LIKE ? OR
            s.codigo LIKE ? OR
            s.nombre LIKE ? OR
            s.descripcion LIKE ? OR
            e.nombre_empresa LIKE ?
        )';
        $searchParam = '%' . $searchValue . '%';
        $params[] = $searchParam;
        $params[] = $searchParam;
        $params[] = $searchParam;
        $params[] = $searchParam;
        $params[] = $searchParam;
        $types .= 'sssss';
    }

    $whereClause = '';
    if (count($whereConditions) > 0) {
        $whereClause = 'WHERE ' . implode(' AND ', $whereConditions);
    }

    $query_total = 'SELECT COUNT(*) as total FROM servicios s';
    $result_total = mysqli_query($conexion, $query_total);
    $row_total = mysqli_fetch_assoc($result_total);
    $recordsTotal = (int)$row_total['total'];

    $query_filtered = "
        SELECT COUNT(*) as total
        FROM servicios s
        LEFT JOIN empresas e ON s.rel_id_empresa = e.id_empresa
        LEFT JOIN categorias c ON s.id_categoria = c.id_categoria
        $whereClause
    ";

    if (!empty($types)) {
        $stmt_filtered = mysqli_prepare($conexion, $query_filtered);
        mysqli_stmt_bind_param($stmt_filtered, $types, ...$params);
        mysqli_stmt_execute($stmt_filtered);
        $result_filtered = mysqli_stmt_get_result($stmt_filtered);
        $row_filtered = mysqli_fetch_assoc($result_filtered);
        $recordsFiltered = (int)$row_filtered['total'];
        mysqli_stmt_close($stmt_filtered);
    } else {
        $result_filtered = mysqli_query($conexion, $query_filtered);
        $row_filtered = mysqli_fetch_assoc($result_filtered);
        $recordsFiltered = (int)$row_filtered['total'];
    }

    $query = "
        SELECT
            s.id,
            s.codigo,
            s.nombre,
            s.activo,
            s.tipo_facturacion,
            s.precio_hora,
            s.precio_fijo,
            s.porcentaje_iva,
            s.unidad_tiempo,
            s.fecha_modificacion,
            e.nombre_empresa,
            c.nombre_categoria
        FROM servicios s
        LEFT JOIN empresas e ON s.rel_id_empresa = e.id_empresa
        LEFT JOIN categorias c ON s.id_categoria = c.id_categoria
        $whereClause
        ORDER BY s.id DESC
        LIMIT ?, ?
    ";

    $allParams = array_merge($params, [$start, $length]);
    $allTypes = $types . 'ii';

    $stmt = mysqli_prepare($conexion, $query);
    if (!empty($types)) {
        mysqli_stmt_bind_param($stmt, $allTypes, ...$allParams);
    } else {
        mysqli_stmt_bind_param($stmt, 'ii', $start, $length);
    }

    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);

    $data = [];
    while ($row = mysqli_fetch_assoc($result)) {
        $activo_badge = ((int)$row['activo'] === 1)
            ? '<span class="badge bg-label-success">Sí</span>'
            : '<span class="badge bg-label-secondary">No</span>';

        $data[] = [
            htmlspecialchars((string)$row['id']),
            htmlspecialchars($row['codigo'] ?? ''),
            htmlspecialchars($row['nombre'] ?? ''),
            htmlspecialchars($row['nombre_empresa'] ?? '—'),
            htmlspecialchars($row['nombre_categoria'] ?? '—'),
            $activo_badge,
            htmlspecialchars(servicios_label_tipo_fact($row['tipo_facturacion'] ?? '')),
            number_format((float)$row['precio_hora'], 2, ',', '.') . ' €',
            number_format((float)$row['precio_fijo'], 2, ',', '.') . ' €',
            number_format((float)$row['porcentaje_iva'], 2, ',', '.') . ' %',
            htmlspecialchars(servicios_label_unidad($row['unidad_tiempo'] ?? '')),
            !empty($row['fecha_modificacion']) ? date('d/m/Y H:i', strtotime($row['fecha_modificacion'])) : '—',
            (int)$row['id'],
        ];
    }

    mysqli_stmt_close($stmt);
    mysqli_close($conexion);

    echo json_encode([
        'draw' => $draw,
        'recordsTotal' => $recordsTotal,
        'recordsFiltered' => $recordsFiltered,
        'data' => $data,
    ]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}
