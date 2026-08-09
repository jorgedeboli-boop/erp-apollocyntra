<?php
require_once '../../../include/session.php';
require_once '../../../include/functions.php';

header('Content-Type: application/json; charset=utf-8');

if (!isset($_SESSION['usuario_id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'No autorizado']);
    exit;
}

$mysqli_bind_params = function ($stmt, $types, array $params) {
    if ($types === '' || empty($params)) {
        return true;
    }
    $bind_names = [$types];
    for ($i = 0; $i < count($params); $i++) {
        $bind_names[] = &$params[$i];
    }
    return call_user_func_array([$stmt, 'bind_param'], $bind_names);
};

$draw = isset($_POST['draw']) ? (int) $_POST['draw'] : 1;
$start = isset($_POST['start']) ? (int) $_POST['start'] : 0;
$length = isset($_POST['length']) ? (int) $_POST['length'] : 25;
$search = isset($_POST['search']['value']) ? trim($_POST['search']['value']) : '';

$orderColumn = isset($_POST['order'][0]['column']) ? (int) $_POST['order'][0]['column'] : 0;
$orderDir = isset($_POST['order'][0]['dir']) ? $_POST['order'][0]['dir'] : 'desc';

$filtro_sucursal = isset($_POST['filtro_sucursal']) ? trim($_POST['filtro_sucursal']) : '';
$filtro_tipo = isset($_POST['filtro_tipo']) ? trim($_POST['filtro_tipo']) : '';
$filtro_plazos = isset($_POST['filtro_plazos']) ? trim($_POST['filtro_plazos']) : '';
$filtro_plazos_pendientes = isset($_POST['filtro_plazos_pendientes']) ? trim($_POST['filtro_plazos_pendientes']) : '';
$filtro_tipo_pago = isset($_POST['filtro_tipo_pago']) ? trim($_POST['filtro_tipo_pago']) : '';
$filtro_fecha_desde = isset($_POST['filtro_fecha_desde']) ? trim($_POST['filtro_fecha_desde']) : '';
$filtro_fecha_hasta = isset($_POST['filtro_fecha_hasta']) ? trim($_POST['filtro_fecha_hasta']) : '';
$filtro_periodo = isset($_POST['filtro_periodo']) ? trim($_POST['filtro_periodo']) : 'todos';

$columnMap = [
    0 => 'rv.id_articulo',
    1 => 'rv.descripcion_articulo',
    2 => 'rv.nombre_sucursal_venta',
    3 => 'rv.fecha_venta',
    4 => 'rv.id_venta_rel',
    5 => 'rv.numero_factura_venta',
    6 => 'rv.coste_articulo_venta',
    7 => 'rv.precio_articulo',
    8 => 'rv.peso_articulo',
    9 => 'rv.articulo_web',
    10 => 'rv.tipo_metal_articulo',
    11 => 'rv.venta_plazos',
    12 => 'rv.numero_plazos',
    13 => 'rv.tipo_pago',
    14 => 'rv.cantidad_contado',
];

if (!isset($columnMap[$orderColumn])) {
    $orderColumn = 0;
}

$orderBy = $columnMap[$orderColumn];
$orderDirection = strtoupper($orderDir) === 'ASC' ? 'ASC' : 'DESC';

if ($start < 0) {
    $start = 0;
}
if ($length < 1 || $length > 500) {
    $length = 25;
}

if ($filtro_periodo === 'personalizado') {
    $filtro_periodo = 'fecha';
}

try {
    $conexion = conectar_bd();
    if (!$conexion) {
        throw new Exception('Error de conexión a la base de datos');
    }

    $joins = "
        FROM reporte_ventas rv
        LEFT JOIN (
            SELECT id_venta, COUNT(*) AS conteo_plazos
            FROM ventas_plazos
            WHERE estado = 'Pagado'
            GROUP BY id_venta
        ) vp ON rv.identificador_venta = vp.id_venta
    ";

    $whereConditions = [];
    $params = [];
    $types = '';

    $tiene_filtro_fecha_explicito = (
        $filtro_periodo === 'dia'
        || $filtro_periodo === 'mes'
        || $filtro_periodo === 'fecha'
        || $filtro_fecha_desde !== ''
        || $filtro_fecha_hasta !== ''
    );

    if (!$tiene_filtro_fecha_explicito && ($filtro_periodo === '' || $filtro_periodo === 'todos')) {
        $whereConditions[] = 'rv.fecha_venta BETWEEN DATE_SUB(NOW(), INTERVAL 2 YEAR) AND NOW()';
    }

    if ($filtro_sucursal !== '') {
        $whereConditions[] = 'rv.id_sucursal_venta = ?';
        $params[] = (int) $filtro_sucursal;
        $types .= 'i';
    }

    if ($filtro_tipo !== '') {
        $tipo_norm = strtolower($filtro_tipo);
        if ($tipo_norm === 'oro' || $tipo_norm === 'plata') {
            $whereConditions[] = 'LOWER(rv.tipo_metal_articulo) LIKE ?';
            $params[] = '%' . $tipo_norm . '%';
            $types .= 's';
        }
    }

    if ($filtro_plazos === 'si') {
        $whereConditions[] = "rv.venta_plazos = 'si'";
    } elseif ($filtro_plazos === 'no') {
        $whereConditions[] = "rv.venta_plazos = 'no'";
    }

    if ($filtro_plazos_pendientes === 'si') {
        $whereConditions[] = "rv.venta_plazos = 'si' AND rv.numero_plazos > COALESCE(vp.conteo_plazos, 0)";
    } elseif ($filtro_plazos_pendientes === 'no') {
        $whereConditions[] = "(rv.venta_plazos = 'no' OR rv.numero_plazos <= COALESCE(vp.conteo_plazos, 0))";
    }

    if ($filtro_tipo_pago !== '') {
        $whereConditions[] = 'rv.tipo_pago = ?';
        $params[] = $filtro_tipo_pago;
        $types .= 's';
    }

    if ($filtro_periodo === 'dia') {
        $hoy = date('Y-m-d');
        $whereConditions[] = 'DATE(rv.fecha_venta) = ?';
        $params[] = $hoy;
        $types .= 's';
    } elseif ($filtro_periodo === 'mes') {
        $whereConditions[] = 'MONTH(rv.fecha_venta) = MONTH(CURRENT_DATE()) AND YEAR(rv.fecha_venta) = YEAR(CURRENT_DATE())';
    } elseif ($filtro_periodo === 'fecha' || $filtro_fecha_desde !== '' || $filtro_fecha_hasta !== '') {
        if ($filtro_fecha_desde !== '' && $filtro_fecha_hasta !== '') {
            $whereConditions[] = 'DATE(rv.fecha_venta) BETWEEN ? AND ?';
            $params[] = $filtro_fecha_desde;
            $params[] = $filtro_fecha_hasta;
            $types .= 'ss';
        } elseif ($filtro_fecha_desde !== '') {
            $whereConditions[] = 'DATE(rv.fecha_venta) >= ?';
            $params[] = $filtro_fecha_desde;
            $types .= 's';
        } elseif ($filtro_fecha_hasta !== '') {
            $whereConditions[] = 'DATE(rv.fecha_venta) <= ?';
            $params[] = $filtro_fecha_hasta;
            $types .= 's';
        }
    }

    if ($search !== '') {
        $whereConditions[] = "(
            CAST(rv.id_articulo AS CHAR) LIKE ?
            OR rv.descripcion_articulo LIKE ?
            OR rv.nombre_sucursal_venta LIKE ?
            OR CAST(rv.id_venta_rel AS CHAR) LIKE ?
            OR CAST(rv.numero_factura_venta AS CHAR) LIKE ?
            OR rv.tipo_pago LIKE ?
            OR rv.usuario_venta LIKE ?
        )";
        $search_param = '%' . $search . '%';
        for ($i = 0; $i < 7; $i++) {
            $params[] = $search_param;
        }
        $types .= 'sssssss';
    }

    $whereClause = count($whereConditions) > 0
        ? 'WHERE ' . implode(' AND ', $whereConditions)
        : '';

    $count_query = 'SELECT COUNT(*) AS total ' . $joins . ' ' . $whereClause;
    $stmt_count = mysqli_prepare($conexion, $count_query);
    if (!$stmt_count) {
        throw new Exception('Error al contar registros: ' . mysqli_error($conexion));
    }
    if ($types !== '') {
        $mysqli_bind_params($stmt_count, $types, $params);
    }
    if (!mysqli_stmt_execute($stmt_count)) {
        throw new Exception('Error al ejecutar conteo: ' . mysqli_stmt_error($stmt_count));
    }
    $result_count = mysqli_stmt_get_result($stmt_count);
    $total_records = (int) (mysqli_fetch_assoc($result_count)['total'] ?? 0);
    mysqli_stmt_close($stmt_count);

    $result_total = mysqli_query(
        $conexion,
        'SELECT COUNT(*) AS total FROM reporte_ventas
         WHERE fecha_venta BETWEEN DATE_SUB(NOW(), INTERVAL 2 YEAR) AND NOW()'
    );
    if (!$result_total) {
        throw new Exception('Error al contar total: ' . mysqli_error($conexion));
    }
    $recordsTotal = (int) (mysqli_fetch_assoc($result_total)['total'] ?? 0);

    $params_data = $params;
    $types_data = $types . 'ii';
    $params_data[] = $start;
    $params_data[] = $length;

    $data_query = "
        SELECT
            rv.id_articulo,
            rv.id_sucursal_venta,
            rv.nombre_sucursal_venta,
            rv.descripcion_articulo,
            rv.id_venta_rel,
            rv.identificador_venta,
            rv.precio_articulo,
            rv.peso_articulo,
            rv.articulo_web,
            rv.tipo_metal_articulo,
            rv.venta_plazos,
            rv.numero_plazos,
            rv.tipo_pago,
            rv.cantidad_contado,
            rv.cantidad_tarjeta,
            rv.cantidad_transferencia,
            rv.cantidad_bizum,
            rv.fecha_venta,
            rv.coste_articulo_venta,
            rv.factura_id_rel,
            rv.prefijo_factura,
            rv.numero_factura_venta,
            COALESCE(vp.conteo_plazos, 0) AS plazos_pagados
        " . $joins . ' ' . $whereClause . "
        ORDER BY {$orderBy} {$orderDirection}
        LIMIT ?, ?
    ";

    $stmt_data = mysqli_prepare($conexion, $data_query);
    if (!$stmt_data) {
        throw new Exception('Error al preparar consulta: ' . mysqli_error($conexion));
    }
    $mysqli_bind_params($stmt_data, $types_data, $params_data);
    if (!mysqli_stmt_execute($stmt_data)) {
        throw new Exception('Error al ejecutar consulta: ' . mysqli_stmt_error($stmt_data));
    }
    $result_data = mysqli_stmt_get_result($stmt_data);
    if ($result_data === false) {
        throw new Exception('Error al obtener resultados: ' . mysqli_error($conexion));
    }

    $data = [];
    while ($row = mysqli_fetch_assoc($result_data)) {
        $data[] = [
            (int) ($row['id_articulo'] ?? 0),
            $row['descripcion_articulo'] ?? '',
            $row['nombre_sucursal_venta'] ?? '',
            $row['fecha_venta'] ?? '',
            (int) ($row['id_venta_rel'] ?? 0),
            $row['numero_factura_venta'] ?? '',
            $row['coste_articulo_venta'] ?? 0,
            $row['precio_articulo'] ?? 0,
            $row['peso_articulo'] ?? 0,
            $row['articulo_web'] ?? 'false',
            $row['tipo_metal_articulo'] ?? '',
            $row['venta_plazos'] ?? 'no',
            (int) ($row['numero_plazos'] ?? 0),
            $row['tipo_pago'] ?? '',
            $row['cantidad_contado'] ?? 0,
            (int) ($row['id_sucursal_venta'] ?? 0),
            (int) ($row['identificador_venta'] ?? 0),
            $row['cantidad_tarjeta'] ?? 0,
            $row['cantidad_transferencia'] ?? 0,
            $row['cantidad_bizum'] ?? 0,
            (int) ($row['plazos_pagados'] ?? 0),
            (int) ($row['factura_id_rel'] ?? 0),
            $row['prefijo_factura'] ?? '',
        ];
    }

    mysqli_stmt_close($stmt_data);
    mysqli_close($conexion);

    echo json_encode([
        'draw' => $draw,
        'recordsTotal' => $recordsTotal,
        'recordsFiltered' => $total_records,
        'data' => $data,
    ], JSON_UNESCAPED_UNICODE);
} catch (Exception $e) {
    if (function_exists('insertErrorLog')) {
        insertErrorLog('reportes_ventas/load_list.php: ' . $e->getMessage());
    }

    http_response_code(500);
    echo json_encode([
        'error' => $e->getMessage(),
        'draw' => $draw,
        'recordsTotal' => 0,
        'recordsFiltered' => 0,
        'data' => [],
    ], JSON_UNESCAPED_UNICODE);
}
