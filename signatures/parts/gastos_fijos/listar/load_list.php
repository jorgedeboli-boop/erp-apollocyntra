<?php
require_once '../../../include/session.php';

// Verificar versión de PHP
if (version_compare(PHP_VERSION, '7.0.0', '<')) {
    http_response_code(500);
    echo json_encode(['error' => 'Se requiere PHP 7.0 o superior']);
    exit;
}

ob_clean();
header('Content-Type: application/json');

try {
    $conexion = conectar_bd();

    // DataTables
    $start = isset($_POST['start']) ? (int)$_POST['start'] : 0;
    $length = isset($_POST['length']) ? (int)$_POST['length'] : 25;
    $searchValue = isset($_POST['search']['value']) ? trim($_POST['search']['value']) : '';
    $draw = isset($_POST['draw']) ? (int)$_POST['draw'] : 1;

    if ($start < 0) $start = 0;
    if ($length < 1 || $length > 100) $length = 25;

    // Filtros
    $filtroSucursal = isset($_POST['filtro_sucursal']) ? (int)$_POST['filtro_sucursal'] : 0;
    $filtroProveedor = isset($_POST['filtro_proveedor']) ? (int)$_POST['filtro_proveedor'] : 0;
    $filtroFormaPago = isset($_POST['filtro_forma_pago']) ? (int)$_POST['filtro_forma_pago'] : 0;
    $filtroTipoGasto = isset($_POST['filtro_tipo_gasto']) ? (int)$_POST['filtro_tipo_gasto'] : 0;
    $filtroPeriodo = isset($_POST['filtro_periodo']) ? trim($_POST['filtro_periodo']) : '';
    $filtroEstado = isset($_POST['filtro_estado']) ? trim($_POST['filtro_estado']) : '';
    $filtroFechaDesde = isset($_POST['filtro_fecha_desde']) ? trim($_POST['filtro_fecha_desde']) : '';
    $filtroFechaHasta = isset($_POST['filtro_fecha_hasta']) ? trim($_POST['filtro_fecha_hasta']) : '';

    $where = [];
    $params = [];
    $types = '';

    if ($searchValue !== '') {
        $where[] = "(gf.id_gasto_fijo = ? OR gf.descripcion_gasto_fijo LIKE ? OR p.nombre_proveedor LIKE ? OR s.nombre_sucursal LIKE ?)";
        $types .= 'isss';
        $params[] = (int)$searchValue;
        $like = '%' . $searchValue . '%';
        $params[] = $like;
        $params[] = $like;
        $params[] = $like;
    }

    if ($filtroSucursal > 0) {
        $where[] = "gf.sucursal_gasto_fijo = ?";
        $types .= 'i';
        $params[] = $filtroSucursal;
    }
    if ($filtroProveedor > 0) {
        $where[] = "gf.proveedor_gasto_fijo = ?";
        $types .= 'i';
        $params[] = $filtroProveedor;
    }
    if ($filtroFormaPago > 0) {
        $where[] = "gf.forma_pago_gasto_fijo = ?";
        $types .= 'i';
        $params[] = $filtroFormaPago;
    }
    if ($filtroTipoGasto > 0) {
        $where[] = "gf.tipo_de_gasto_fijo = ?";
        $types .= 'i';
        $params[] = $filtroTipoGasto;
    }
    if ($filtroPeriodo !== '') {
        $where[] = "gf.periodo_gasto_fijo = ?";
        $types .= 's';
        $params[] = $filtroPeriodo;
    }
    if ($filtroEstado === 'true' || $filtroEstado === 'false') {
        $where[] = "gf.estado_gasto_fijo = ?";
        $types .= 's';
        $params[] = $filtroEstado;
    }
    if ($filtroFechaDesde !== '' && $filtroFechaHasta !== '') {
        $where[] = "DATE(gf.fecha_inicio_gasto_fijo) BETWEEN ? AND ?";
        $types .= 'ss';
        $params[] = $filtroFechaDesde;
        $params[] = $filtroFechaHasta;
    }

    $whereSql = $where ? ('WHERE ' . implode(' AND ', $where)) : '';

    $baseFrom = "
        FROM gastos_fijos gf
        LEFT JOIN tipo_de_gasto tg ON gf.tipo_de_gasto_fijo = tg.id_tipo_gasto
        LEFT JOIN formas_de_pago fp ON gf.forma_pago_gasto_fijo = fp.id_forma_de_pago
        LEFT JOIN proveedores p ON gf.proveedor_gasto_fijo = p.id_proveedor
        LEFT JOIN sucursal s ON gf.sucursal_gasto_fijo = s.id_sucursal
    ";

    // Total sin filtros
    $rTotal = mysqli_query($conexion, "SELECT COUNT(*) AS c FROM gastos_fijos");
    $totalRecords = $rTotal ? (int)mysqli_fetch_assoc($rTotal)['c'] : 0;

    // Total con filtros
    $sqlFiltered = "SELECT COUNT(*) AS c {$baseFrom} {$whereSql}";
    $stmtF = mysqli_prepare($conexion, $sqlFiltered);
    if (!$stmtF) {
        throw new Exception("Error preparando count filtrado: " . mysqli_error($conexion));
    }
    if ($types !== '') {
        mysqli_stmt_bind_param($stmtF, $types, ...$params);
    }
    mysqli_stmt_execute($stmtF);
    $rF = mysqli_stmt_get_result($stmtF);
    $filteredRecords = $rF ? (int)mysqli_fetch_assoc($rF)['c'] : 0;
    mysqli_stmt_close($stmtF);

    // Datos
    $sqlData = "
        SELECT
            gf.id_gasto_fijo,
            gf.fecha_alta_gasto_fijo,
            gf.fecha_inicio_gasto_fijo,
            gf.periodo_gasto_fijo,
            gf.total_gasto_fijo,
            gf.descripcion_gasto_fijo,
            gf.estado_gasto_fijo,
            p.id_proveedor,
            p.nombre_proveedor,
            p.cif_proveedor,
            tg.nombre_tipo_gasto,
            fp.nombre_forma_de_pago,
            s.nombre_sucursal
        {$baseFrom}
        {$whereSql}
        ORDER BY gf.id_gasto_fijo DESC
        LIMIT ? OFFSET ?
    ";
    $stmt = mysqli_prepare($conexion, $sqlData);
    if (!$stmt) {
        throw new Exception("Error preparando datos: " . mysqli_error($conexion));
    }

    $typesData = $types . 'ii';
    $paramsData = $params;
    $paramsData[] = $length;
    $paramsData[] = $start;
    mysqli_stmt_bind_param($stmt, $typesData, ...$paramsData);

    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);

    $data = [];
    while ($row = $result ? mysqli_fetch_assoc($result) : null) {
        if (!$row) break;

        $data[] = [
            'id_gasto_fijo' => (int)$row['id_gasto_fijo'],
            'fecha_alta' => $row['fecha_alta_gasto_fijo'],
            'proveedor_id' => $row['id_proveedor'],
            'proveedor_nombre' => $row['nombre_proveedor'],
            'proveedor_cif' => $row['cif_proveedor'],
            'total' => $row['total_gasto_fijo'],
            'descripcion' => $row['descripcion_gasto_fijo'],
            'tipo_gasto' => $row['nombre_tipo_gasto'],
            'forma_pago' => $row['nombre_forma_de_pago'],
            'sucursal' => $row['nombre_sucursal'],
            'fecha_inicio' => $row['fecha_inicio_gasto_fijo'],
            'periodo' => $row['periodo_gasto_fijo'],
            'estado' => $row['estado_gasto_fijo'],
        ];
    }

    mysqli_stmt_close($stmt);
    mysqli_close($conexion);

    echo json_encode([
        'draw' => $draw,
        'recordsTotal' => $totalRecords,
        'recordsFiltered' => $filteredRecords,
        'data' => $data
    ]);
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode([
        'error' => true,
        'message' => $e->getMessage()
    ]);
}

