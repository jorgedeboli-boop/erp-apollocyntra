<?php
/**
 * Server-side processing para DataTable de facturas_rectificativas
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
    $filtro_sucursal = isset($_POST['filtro_sucursal']) ? trim($_POST['filtro_sucursal']) : '';
    
    $whereConditions = array();
    $params = array();
    $types = '';
    
    if (!empty($filtro_sucursal)) {
        $whereConditions[] = "s.nombre_sucursal = ?";
        $params[] = $filtro_sucursal;
        $types .= 's';
    }
    if (!empty($searchValue)) {
        $whereConditions[] = "(
            CAST(f.id_factura AS CHAR) LIKE ? OR
            CAST(f.numero_factura AS CHAR) LIKE ? OR
            CAST(f.fecha_factura AS CHAR) LIKE ? OR
            CAST(f.total_factura AS CHAR) LIKE ? OR
            f.estado_factura LIKE ? OR
            f.tipo_pago_factura LIKE ? OR
            CAST(f.factura_original AS CHAR) LIKE ? OR
            s.nombre_sucursal LIKE ? OR
            CONCAT(c.nombre, ' ', c.apellido) LIKE ?
        )";
        $searchParam = '%' . $searchValue . '%';
        for ($i = 0; $i < 9; $i++) $params[] = $searchParam;
        $types .= str_repeat('s', 9);
    }
    
    $whereClause = count($whereConditions) > 0 ? 'WHERE ' . implode(' AND ', $whereConditions) : '';
    
    $queryBase = "
        FROM facturas_rectificativas f
        LEFT JOIN sucursal s ON f.id_sucursal = s.id_sucursal
        LEFT JOIN clientes c ON f.cliente_factura = c.id_cliente
        LEFT JOIN usuarios u ON f.facturado_por = u.id_usuario
        $whereClause
    ";
    
    $query_total = "SELECT COUNT(*) as total FROM facturas_rectificativas";
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
    
    $orderColumn = isset($_POST['order'][0]['column']) ? (int)$_POST['order'][0]['column'] : 1;
    $orderDir = isset($_POST['order'][0]['dir']) ? $_POST['order'][0]['dir'] : 'desc';
    // Índices DataTable: 0 Nº, 1 fecha, 2 hora, 3 cliente, 4 sucursal, 5 total, 6 estado, 7 tipo pago, 8 original, 9 fiskaly, 10 acciones
    $columnMap = [
        0 => 'f.id_factura',
        1 => 'f.fecha_factura',
        2 => 'f.hora_factura',
        3 => 'CLIENTEDATA',
        4 => 's.nombre_sucursal',
        5 => 'f.total_factura',
        6 => 'f.estado_factura',
        7 => 'f.tipo_pago_factura',
        8 => 'f.factura_original',
        9 => 'f.id_rel_factura_fiskaly',
        10 => 'f.id_factura',
    ];
    $allowedColumns = array_values($columnMap);
    $orderBy = isset($columnMap[$orderColumn]) && in_array($columnMap[$orderColumn], $allowedColumns) ? $columnMap[$orderColumn] : 'f.fecha_factura';
    $orderDir = strtoupper($orderDir) === 'ASC' ? 'ASC' : 'DESC';
    
    $query = "SELECT 
                f.id_factura,
                f.id_sucursal,
                f.numero_factura,
                f.fecha_factura,
                f.hora_factura,
                f.cliente_factura,
                f.total_factura,
                f.estado_factura,
                f.tipo_pago_factura,
                f.factura_original,
                f.prefijo_factura,
                f.prefijo_factura_original,
                f.rel_id_factura,
                f.factura_regimen,
                f.id_rel_factura_fiskaly,
                f.rel_id_empresa,
                s.nombre_sucursal,
                s.empresa_id,
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

    $rows = array();
    $idsPorEmpresa = array();
    while ($row = mysqli_fetch_assoc($result)) {
        $idFiskaly = (int) ($row['id_rel_factura_fiskaly'] ?? 0);
        $idEmpresa = (int) ($row['rel_id_empresa'] ?? 0);
        if ($idEmpresa <= 0) {
            $idEmpresa = (int) ($row['empresa_id'] ?? 0);
        }
        $row['_id_empresa_fiskaly'] = $idEmpresa;
        if ($idFiskaly > 0 && $idEmpresa > 0) {
            if (!isset($idsPorEmpresa[$idEmpresa])) {
                $idsPorEmpresa[$idEmpresa] = array();
            }
            $idsPorEmpresa[$idEmpresa][] = $idFiskaly;
        }
        $rows[] = $row;
    }
    mysqli_stmt_close($stmt);

    $mapaEstados = function_exists('fiskalyObtenerEstadosCacheMapa')
        ? fiskalyObtenerEstadosCacheMapa($idsPorEmpresa)
        : array();
    
    $data = array();
    foreach ($rows as $row) {
        $idFiskaly = (int) ($row['id_rel_factura_fiskaly'] ?? 0);
        $estadoFiskaly = '—';
        if ($idFiskaly > 0) {
            $estadoFiskaly = isset($mapaEstados[$idFiskaly]) && $mapaEstados[$idFiskaly] !== ''
                ? $mapaEstados[$idFiskaly]
                : 'sin_cache';
        }

        $urlImpresion = fiskalyUrlImpresionFacturaRectificativa(
            (int) $row['id_factura'],
            (string) ($row['factura_regimen'] ?? 'false'),
            $idFiskaly,
            false
        );

        $puedeReenviar = ($idFiskaly > 0 && !in_array($estadoFiskaly, array('aceptada'), true)) ? 1 : 0;

        $data[] = [
            $row['id_factura'],
            $row['prefijo_factura'] . ' ' . $row['numero_factura'],
            $row['fecha_factura'] ?: '-',
            (trim((string) ($row['hora_factura'] ?? '')) !== '' ? substr(trim((string) $row['hora_factura']), 0, 8) : '-'),
            $row['CLIENTEDATA'] ?: '-',
            $row['nombre_sucursal'] ?: '-',
            number_format($row['total_factura'], 2, ',', '.') . ' €',
            $row['estado_factura'],
            $row['tipo_pago_factura'] ?: '-',
            $row['prefijo_factura_original'] . ' ' . $row['factura_original'],
            $row['rel_id_factura'],
            $urlImpresion,
            $estadoFiskaly,
            $idFiskaly,
            (int) ($row['id_sucursal'] ?? 0),
            $puedeReenviar,
        ];
    }
    
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
