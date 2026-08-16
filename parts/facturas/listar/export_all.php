<?php
require_once '../../../include/session.php';
require_once '../../../include/functions.php';

if (!function_exists('usuario_autenticado') || !usuario_autenticado()) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'No autorizado']);
    exit;
}

header('Content-Type: application/json');

try {
    $conexion = conectar_bd();
    $searchValue = isset($_POST['search']) ? trim($_POST['search']) : '';
    $filtro_empresa = isset($_POST['filtro_empresa']) ? (int)$_POST['filtro_empresa'] : 0;
    $filtro_tipo_pago = isset($_POST['filtro_tipo_pago']) ? trim($_POST['filtro_tipo_pago']) : '';
    $filtro_estado_factura = isset($_POST['filtro_estado_factura']) ? trim($_POST['filtro_estado_factura']) : '';
    $filtro_periodo = isset($_POST['filtro_periodo']) ? trim($_POST['filtro_periodo']) : 'todos';
    $filtro_fecha_desde = isset($_POST['filtro_fecha_desde']) ? trim($_POST['filtro_fecha_desde']) : '';
    $filtro_fecha_hasta = isset($_POST['filtro_fecha_hasta']) ? trim($_POST['filtro_fecha_hasta']) : '';
    
    $whereConditions = array();
    $params = array();
    $types = '';
    
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
    
    $query = "SELECT 
                f.id_factura,
                f.numero_factura,
                f.prefijo_factura,
                f.fecha_factura,
                f.hora_factura,
                f.total_factura,
                f.estado_factura,
                f.tipo_pago_factura,
                f.tipo_factura,
                f.factura_regimen,
                empr.nombre_empresa AS nombre_empresa,
                CONCAT(c.nombre, ' ', c.apellido) AS CLIENTEDATA
              FROM facturas f
              LEFT JOIN clientes c ON f.cliente_factura = c.id_cliente
              LEFT JOIN empresas empr ON f.rel_id_empresa = empr.id_empresa
              $whereClause
              ORDER BY f.fecha_factura DESC, f.hora_factura DESC";
    
    if (!empty($types)) {
        $stmt = mysqli_prepare($conexion, $query);
        mysqli_stmt_bind_param($stmt, $types, ...$params);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        mysqli_stmt_close($stmt);
    } else {
        $result = mysqli_query($conexion, $query);
    }
    
    if (!$result) throw new Exception(mysqli_error($conexion));
    
    $data = [];
    while ($row = mysqli_fetch_assoc($result)) {
        $numero_completo = $row['prefijo_factura'] ? $row['prefijo_factura'] . '-' . $row['numero_factura'] : $row['numero_factura'];

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
            $regimen_txt
        ];
    }
    mysqli_close($conexion);
    
    echo json_encode(['success' => true, 'data' => $data, 'total' => count($data)]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
?>
