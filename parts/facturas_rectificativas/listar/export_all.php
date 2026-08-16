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
    
    $whereConditions = array();
    $params = array();
    $types = '';
    
    if (!empty($searchValue)) {
        $whereConditions[] = "(
            CAST(f.id_factura AS CHAR) LIKE ? OR
            CAST(f.numero_factura AS CHAR) LIKE ? OR
            CAST(f.fecha_factura AS CHAR) LIKE ? OR
            CAST(f.total_factura AS CHAR) LIKE ? OR
            f.estado_factura LIKE ? OR
            f.tipo_pago_factura LIKE ? OR
            CAST(f.factura_original AS CHAR) LIKE ? OR
            CONCAT(c.nombre, ' ', c.apellido) LIKE ?
        )";
        $searchParam = '%' . $searchValue . '%';
        for ($i = 0; $i < 8; $i++) $params[] = $searchParam;
        $types .= str_repeat('s', 8);
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
                f.factura_original,
                f.prefijo_factura_original,
                f.id_rel_factura_fiskaly,
                f.rel_id_empresa,
                CONCAT(c.nombre, ' ', c.apellido) AS CLIENTEDATA
              FROM facturas_rectificativas f
              LEFT JOIN clientes c ON f.cliente_factura = c.id_cliente
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

    $rows = array();
    $idsPorEmpresa = array();
    while ($row = mysqli_fetch_assoc($result)) {
        $idFiskaly = (int) ($row['id_rel_factura_fiskaly'] ?? 0);
        $idEmpresa = (int) ($row['rel_id_empresa'] ?? 0);
        if ($idFiskaly > 0 && $idEmpresa > 0) {
            if (!isset($idsPorEmpresa[$idEmpresa])) {
                $idsPorEmpresa[$idEmpresa] = array();
            }
            $idsPorEmpresa[$idEmpresa][] = $idFiskaly;
        }
        $rows[] = $row;
    }

    $mapaEstados = function_exists('fiskalyObtenerEstadosCacheMapa')
        ? fiskalyObtenerEstadosCacheMapa($idsPorEmpresa)
        : array();
    
    $data = [];
    foreach ($rows as $row) {
        $idFiskaly = (int) ($row['id_rel_factura_fiskaly'] ?? 0);
        $estadoFiskaly = '—';
        if ($idFiskaly > 0) {
            $estadoFiskaly = isset($mapaEstados[$idFiskaly]) && $mapaEstados[$idFiskaly] !== ''
                ? $mapaEstados[$idFiskaly]
                : 'sin_cache';
        }

        $data[] = [
            $row['id_factura'],
            trim(($row['prefijo_factura'] ?? '') . ' ' . ($row['numero_factura'] ?? '')),
            $row['fecha_factura'] ?: '-',
            (trim((string) ($row['hora_factura'] ?? '')) !== '' ? substr(trim((string) $row['hora_factura']), 0, 8) : '-'),
            $row['CLIENTEDATA'] ?: '-',
            number_format($row['total_factura'], 2, ',', '.') . ' €',
            $row['estado_factura'],
            $row['tipo_pago_factura'] ?: '-',
            trim(($row['prefijo_factura_original'] ?? '') . ' ' . ($row['factura_original'] ?? '')),
            $estadoFiskaly
        ];
    }
    mysqli_close($conexion);
    
    echo json_encode(['success' => true, 'data' => $data, 'total' => count($data)]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
?>
