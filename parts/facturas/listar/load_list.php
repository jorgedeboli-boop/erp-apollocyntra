<?php
/**
 * Server-side processing para DataTable de facturas
 */

require_once '../../../include/session.php';
require_once '../../../include/functions.php';

header('Content-Type: application/json');

try {
    $conexion = conectar_bd();
    
    $draw = isset($_POST['draw']) ? (int)$_POST['draw'] : 1;
    $start = isset($_POST['start']) ? (int)$_POST['start'] : 0;
    $length = isset($_POST['length']) ? (int)$_POST['length'] : 10;
    $searchValue = isset($_POST['search']['value']) ? trim($_POST['search']['value']) : '';
    $filtro_empresa = isset($_POST['filtro_empresa']) ? (int)$_POST['filtro_empresa'] : 0;
    $filtro_tipo_pago = isset($_POST['filtro_tipo_pago']) ? trim($_POST['filtro_tipo_pago']) : '';
    $filtro_estado_factura = isset($_POST['filtro_estado_factura']) ? trim($_POST['filtro_estado_factura']) : '';
    $filtro_periodo = isset($_POST['filtro_periodo']) ? trim($_POST['filtro_periodo']) : 'todos';
    $filtro_fecha_desde = isset($_POST['filtro_fecha_desde']) ? trim($_POST['filtro_fecha_desde']) : '';
    $filtro_fecha_hasta = isset($_POST['filtro_fecha_hasta']) ? trim($_POST['filtro_fecha_hasta']) : '';
    
    $whereConditions = array();
    $params = array();
    $types = '';

    // Listado de facturas completas: excluir simplificadas unificadas
    $whereConditions[] = "(f.factura_simplificada = 'false' OR f.factura_simplificada IS NULL OR f.factura_simplificada = '')";

    if ($filtro_empresa > 0) {
        $whereConditions[] = "f.rel_id_empresa = ?";
        $params[] = $filtro_empresa;
        $types .= 'i';
    }
    
    $estados_factura_ok = ['nopagada', 'pagada', 'anulada'];
    if ($filtro_estado_factura !== '' && in_array($filtro_estado_factura, $estados_factura_ok, true)) {
        $whereConditions[] = "f.estado_factura = ?";
        $params[] = $filtro_estado_factura;
        $types .= 's';
    }
    
    if ($filtro_tipo_pago !== '') {
        $whereConditions[] = "f.tipo_pago_factura = ?";
        $params[] = $filtro_tipo_pago;
        $types .= 's';
    }
    
    $campoFecha = 'f.fecha_factura';
    if ($filtro_fecha_desde !== '' && $filtro_fecha_hasta !== '') {
        $whereConditions[] = "$campoFecha BETWEEN ? AND ?";
        $params[] = $filtro_fecha_desde;
        $params[] = $filtro_fecha_hasta;
        $types .= 'ss';
    } elseif ($filtro_fecha_desde !== '') {
        $whereConditions[] = "$campoFecha >= ?";
        $params[] = $filtro_fecha_desde;
        $types .= 's';
    } elseif ($filtro_fecha_hasta !== '') {
        $whereConditions[] = "$campoFecha <= ?";
        $params[] = $filtro_fecha_hasta;
        $types .= 's';
    } elseif ($filtro_periodo === 'dia') {
        $whereConditions[] = "$campoFecha = CURDATE()";
    } elseif ($filtro_periodo === 'mes') {
        $whereConditions[] = "YEAR($campoFecha) = YEAR(CURDATE()) AND MONTH($campoFecha) = MONTH(CURDATE())";
    } elseif ($filtro_periodo === 'todos' && $filtro_fecha_desde === '' && $filtro_fecha_hasta === '') {
        $anioActual = (int)date('Y');
        $anioInicio = $anioActual - 2;
        $whereConditions[] = "YEAR($campoFecha) >= ?";
        $params[] = $anioInicio;
        $types .= 'i';
    }
    
    if (!empty($searchValue)) {
        $whereConditions[] = "(
            CAST(f.id_factura AS CHAR) LIKE ? OR
            CAST(f.numero_factura AS CHAR) LIKE ? OR
            f.prefijo_factura LIKE ? OR
            CAST(f.fecha_factura AS CHAR) LIKE ? OR
            CAST(f.total_factura AS CHAR) LIKE ? OR
            f.estado_factura LIKE ? OR
            f.tipo_pago_factura LIKE ? OR
            f.tipo_factura LIKE ? OR
            IFNULL(empr.nombre_empresa, '') LIKE ? OR
            CONCAT(c.nombre, ' ', c.apellido) LIKE ?
        )";
        $searchParam = '%' . $searchValue . '%';
        for ($i = 0; $i < 10; $i++) {
            $params[] = $searchParam;
        }
        $types .= str_repeat('s', 10);
    }
    
    $whereClause = count($whereConditions) > 0 ? 'WHERE ' . implode(' AND ', $whereConditions) : '';
    
    $queryBase = "
        FROM facturas f
        LEFT JOIN clientes c ON f.cliente_factura = c.id_cliente
        LEFT JOIN empresas empr ON f.rel_id_empresa = empr.id_empresa
        LEFT JOIN usuarios u ON f.facturado_por = u.id_usuario
        $whereClause
    ";
    
    $query_total = "SELECT COUNT(*) as total FROM facturas f WHERE (f.factura_simplificada = 'false' OR f.factura_simplificada IS NULL OR f.factura_simplificada = '')";
    $result_total = mysqli_query($conexion, $query_total);
    $row_total = mysqli_fetch_assoc($result_total);
    $recordsTotal = isset($row_total['total']) ? (int)$row_total['total'] : 0;
    
    $query_filtered = "SELECT COUNT(*) as total " . $queryBase;
    if (!empty($types)) {
        $stmt_filtered = mysqli_prepare($conexion, $query_filtered);
        mysqli_stmt_bind_param($stmt_filtered, $types, ...$params);
        mysqli_stmt_execute($stmt_filtered);
        $result_filtered = mysqli_stmt_get_result($stmt_filtered);
        $row_filtered = mysqli_fetch_assoc($result_filtered);
        $recordsFiltered = isset($row_filtered['total']) ? (int)$row_filtered['total'] : 0;
        mysqli_stmt_close($stmt_filtered);
    } else {
        $result_filtered = mysqli_query($conexion, $query_filtered);
        $row_filtered = mysqli_fetch_assoc($result_filtered);
        $recordsFiltered = isset($row_filtered['total']) ? (int)$row_filtered['total'] : 0;
    }
    
    $orderColumn = isset($_POST['order'][0]['column']) ? (int)$_POST['order'][0]['column'] : 2;
    $orderDir = isset($_POST['order'][0]['dir']) ? $_POST['order'][0]['dir'] : 'desc';
    $columnMap = [
        0 => 'f.id_factura',
        1 => 'f.numero_factura',
        2 => 'f.fecha_factura',
        3 => 'f.hora_factura',
        4 => 'CLIENTEDATA',
        5 => 'empr.nombre_empresa',
        6 => 'f.total_factura',
        7 => 'f.estado_factura',
        8 => 'f.tipo_pago_factura',
        9 => 'f.tipo_factura',
        10 => 'f.factura_regimen'
    ];
    $allowedColumns = array_values($columnMap);
    $orderBy = isset($columnMap[$orderColumn]) && in_array($columnMap[$orderColumn], $allowedColumns) ? $columnMap[$orderColumn] : 'f.fecha_factura';
    $orderDir = strtoupper($orderDir) === 'ASC' ? 'ASC' : 'DESC';
    
    $query = "SELECT 
                f.id_factura,
                f.numero_factura,
                f.prefijo_factura,
                f.fecha_factura,
                f.hora_factura,
                f.cliente_factura,
                f.total_factura,
                f.estado_factura,
                f.tipo_pago_factura,
                f.tipo_factura,
                f.factura_regimen,
                f.id_rel_factura_fiskaly,
                empr.nombre_empresa AS nombre_empresa,
                CONCAT(c.nombre, ' ', c.apellido) AS CLIENTEDATA
              " . $queryBase . "
              ORDER BY $orderBy $orderDir
              LIMIT ? OFFSET ?";
    
    $params[] = $length;
    $params[] = $start;
    $types .= 'ii';
    
    $stmt = mysqli_prepare($conexion, $query);
    if (!$stmt) throw new Exception('Error al preparar la consulta: ' . mysqli_error($conexion));
    if (!empty($types)) mysqli_stmt_bind_param($stmt, $types, ...$params);
    if (!mysqli_stmt_execute($stmt)) {
        mysqli_stmt_close($stmt);
        throw new Exception('Error al ejecutar la consulta: ' . mysqli_stmt_error($stmt));
    }
    $result = mysqli_stmt_get_result($stmt);
    if (!$result) {
        mysqli_stmt_close($stmt);
        throw new Exception('Error al obtener el resultado: ' . mysqli_error($conexion));
    }
    
    $data = array();
    while ($row = mysqli_fetch_assoc($result)) {
        $numero_completo = $row['prefijo_factura'] ? $row['prefijo_factura'] . '-' . $row['numero_factura'] : $row['numero_factura'];
        $urlImpresion = fiskalyUrlImpresionFacturaListado(
            (int) $row['id_factura'],
            (string) ($row['factura_regimen'] ?? 'false'),
            (int) ($row['id_rel_factura_fiskaly'] ?? 0),
            (string) ($row['tipo_factura'] ?? 'articulos'),
            false
        );

        $regimen_raw = isset($row['factura_regimen']) ? trim((string) $row['factura_regimen']) : 'false';
        if ($regimen_raw === '' || $regimen_raw === 'false') {
            $regimen_txt = 'Regimen general';
        } elseif (in_array($regimen_raw, array('Verifactu', 'TicketBAIBizkaia', 'TicketBAIAlava', 'TicketBAIGipuzkua', 'General'), true)) {
            $regimen_txt = $regimen_raw;
        } else {
            $regimen_txt = 'Regimen general';
        }

        $data[] = [
            $row['id_factura'],
            $numero_completo,
            $row['fecha_factura'] ?: '-',
            (trim((string) ($row['hora_factura'] ?? '')) !== '' ? substr(trim((string) $row['hora_factura']), 0, 8) : '-'),
            $row['CLIENTEDATA'] ?: '-',
            $row['nombre_empresa'] ?: '-',
            number_format($row['total_factura'], 2, ',', '.') . ' €',
            $row['estado_factura'],
            $row['tipo_pago_factura'] ?: '-',
            $row['tipo_factura'] ?: '-',
            $regimen_txt,
            $row['id_factura'],
            $urlImpresion,
        ];
    }
    mysqli_stmt_close($stmt);
    
    echo json_encode([
        'draw' => $draw,
        'recordsTotal' => $recordsTotal,
        'recordsFiltered' => $recordsFiltered,
        'data' => $data
    ]);
    
} catch (Exception $e) {
    if (ob_get_length()) ob_clean();
    http_response_code(500);
    header('Content-Type: application/json');
    echo json_encode([
        'draw' => isset($draw) ? $draw : 1,
        'recordsTotal' => 0,
        'recordsFiltered' => 0,
        'data' => [],
        'error' => 'Error al cargar los datos: ' . $e->getMessage()
    ]);
    exit;
}
?>
