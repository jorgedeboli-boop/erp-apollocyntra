<?php
/**
 * Server-side processing para DataTable de facturas_rectificativas
 */

require_once '../../../include/session.php';
require_once '../../../include/functions.php';

header('Content-Type: application/json; charset=utf-8');

$draw = isset($_POST['draw']) ? (int) $_POST['draw'] : 1;

function facturas_rect_json($payload)
{
    $flags = JSON_UNESCAPED_UNICODE;
    if (defined('JSON_INVALID_UTF8_SUBSTITUTE')) {
        $flags |= JSON_INVALID_UTF8_SUBSTITUTE;
    }
    echo json_encode($payload, $flags);
}

try {
    $conexion = conectar_bd();

    $start = isset($_POST['start']) ? (int) $_POST['start'] : 0;
    $length = isset($_POST['length']) ? (int) $_POST['length'] : 10;
    if ($length < 1) {
        $length = 25;
    }
    if ($start < 0) {
        $start = 0;
    }
    $searchValue = isset($_POST['search']['value']) ? trim($_POST['search']['value']) : '';

    $columnas = array();
    $resCols = mysqli_query($conexion, 'SHOW COLUMNS FROM facturas_rectificativas');
    if (!$resCols) {
        throw new Exception('No se pudo leer facturas_rectificativas: ' . mysqli_error($conexion));
    }
    while ($col = mysqli_fetch_assoc($resCols)) {
        $columnas[$col['Field']] = true;
    }
    $tiene = static function ($nombre) use ($columnas) {
        return isset($columnas[$nombre]);
    };

    $selectCampos = array('f.id_factura');
    $candidatos = array(
        'numero_factura',
        'fecha_factura',
        'hora_factura',
        'cliente_factura',
        'total_factura',
        'estado_factura',
        'tipo_pago_factura',
        'factura_original',
        'prefijo_factura',
        'prefijo_factura_original',
        'rel_id_factura',
        'factura_regimen',
        'id_rel_factura_fiskaly',
        'rel_id_empresa',
    );
    foreach ($candidatos as $campo) {
        if ($tiene($campo)) {
            $selectCampos[] = 'f.' . $campo;
        }
    }

    $whereConditions = array();
    $params = array();
    $types = '';

    if ($searchValue !== '') {
        $searchParts = array('CAST(f.id_factura AS CHAR) LIKE ?');
        $params[] = '%' . $searchValue . '%';
        $types .= 's';
        if ($tiene('numero_factura')) {
            $searchParts[] = 'CAST(f.numero_factura AS CHAR) LIKE ?';
            $params[] = '%' . $searchValue . '%';
            $types .= 's';
        }
        if ($tiene('fecha_factura')) {
            $searchParts[] = 'CAST(f.fecha_factura AS CHAR) LIKE ?';
            $params[] = '%' . $searchValue . '%';
            $types .= 's';
        }
        if ($tiene('total_factura')) {
            $searchParts[] = 'CAST(f.total_factura AS CHAR) LIKE ?';
            $params[] = '%' . $searchValue . '%';
            $types .= 's';
        }
        if ($tiene('estado_factura')) {
            $searchParts[] = 'f.estado_factura LIKE ?';
            $params[] = '%' . $searchValue . '%';
            $types .= 's';
        }
        if ($tiene('tipo_pago_factura')) {
            $searchParts[] = 'f.tipo_pago_factura LIKE ?';
            $params[] = '%' . $searchValue . '%';
            $types .= 's';
        }
        if ($tiene('factura_original')) {
            $searchParts[] = 'CAST(f.factura_original AS CHAR) LIKE ?';
            $params[] = '%' . $searchValue . '%';
            $types .= 's';
        }
        $searchParts[] = "CONCAT(IFNULL(c.nombre, ''), ' ', IFNULL(c.apellido, '')) LIKE ?";
        $params[] = '%' . $searchValue . '%';
        $types .= 's';
        $whereConditions[] = '(' . implode(' OR ', $searchParts) . ')';
    }

    $whereClause = count($whereConditions) > 0 ? 'WHERE ' . implode(' AND ', $whereConditions) : '';

    $queryBase = "
        FROM facturas_rectificativas f
        LEFT JOIN clientes c ON f.cliente_factura = c.id_cliente
        $whereClause
    ";

    $query_total = 'SELECT COUNT(*) as total FROM facturas_rectificativas';
    $result_total = mysqli_query($conexion, $query_total);
    if (!$result_total) {
        throw new Exception('Error al contar facturas rectificativas: ' . mysqli_error($conexion));
    }
    $row_total = mysqli_fetch_assoc($result_total);
    $recordsTotal = isset($row_total['total']) ? (int) $row_total['total'] : 0;

    $query_filtered = 'SELECT COUNT(*) as total ' . $queryBase;
    if ($types !== '') {
        $stmt_filtered = mysqli_prepare($conexion, $query_filtered);
        if (!$stmt_filtered) {
            throw new Exception('Error al preparar el conteo: ' . mysqli_error($conexion));
        }
        mysqli_stmt_bind_param($stmt_filtered, $types, ...$params);
        mysqli_stmt_execute($stmt_filtered);
        $result_filtered = mysqli_stmt_get_result($stmt_filtered);
        if (!$result_filtered) {
            mysqli_stmt_close($stmt_filtered);
            throw new Exception('Error al contar filtrados: ' . mysqli_error($conexion));
        }
        $row_filtered = mysqli_fetch_assoc($result_filtered);
        $recordsFiltered = isset($row_filtered['total']) ? (int) $row_filtered['total'] : 0;
        mysqli_stmt_close($stmt_filtered);
    } else {
        $result_filtered = mysqli_query($conexion, $query_filtered);
        if (!$result_filtered) {
            throw new Exception('Error al contar filtrados: ' . mysqli_error($conexion));
        }
        $row_filtered = mysqli_fetch_assoc($result_filtered);
        $recordsFiltered = isset($row_filtered['total']) ? (int) $row_filtered['total'] : 0;
    }

    $orderColumn = isset($_POST['order'][0]['column']) ? (int) $_POST['order'][0]['column'] : 1;
    $orderDir = isset($_POST['order'][0]['dir']) ? $_POST['order'][0]['dir'] : 'desc';
    $columnMap = [
        0 => 'f.id_factura',
        1 => $tiene('fecha_factura') ? 'f.fecha_factura' : 'f.id_factura',
        2 => $tiene('hora_factura') ? 'f.hora_factura' : 'f.id_factura',
        3 => 'CLIENTEDATA',
        4 => $tiene('total_factura') ? 'f.total_factura' : 'f.id_factura',
        5 => $tiene('estado_factura') ? 'f.estado_factura' : 'f.id_factura',
        6 => $tiene('tipo_pago_factura') ? 'f.tipo_pago_factura' : 'f.id_factura',
        7 => $tiene('factura_original') ? 'f.factura_original' : 'f.id_factura',
        8 => $tiene('id_rel_factura_fiskaly') ? 'f.id_rel_factura_fiskaly' : 'f.id_factura',
        9 => 'f.id_factura',
    ];
    $allowedColumns = array_values($columnMap);
    $orderBy = isset($columnMap[$orderColumn]) ? $columnMap[$orderColumn] : 'f.id_factura';
    if (!in_array($orderBy, $allowedColumns, true)) {
        $orderBy = $tiene('fecha_factura') ? 'f.fecha_factura' : 'f.id_factura';
    }
    $orderDir = strtoupper($orderDir) === 'ASC' ? 'ASC' : 'DESC';

    $query = 'SELECT ' . implode(', ', $selectCampos) . ",
                CONCAT(IFNULL(c.nombre, ''), ' ', IFNULL(c.apellido, '')) AS CLIENTEDATA
              " . $queryBase . "
              ORDER BY $orderBy $orderDir
              LIMIT ? OFFSET ?";

    $params[] = $length;
    $params[] = $start;
    $types .= 'ii';

    $stmt = mysqli_prepare($conexion, $query);
    if (!$stmt) {
        throw new Exception('Error al preparar la consulta: ' . mysqli_error($conexion));
    }
    mysqli_stmt_bind_param($stmt, $types, ...$params);
    if (!mysqli_stmt_execute($stmt)) {
        $err = mysqli_stmt_error($stmt);
        mysqli_stmt_close($stmt);
        throw new Exception('Error al ejecutar la consulta: ' . $err);
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
        if ($idFiskaly > 0 && $idEmpresa > 0) {
            if (!isset($idsPorEmpresa[$idEmpresa])) {
                $idsPorEmpresa[$idEmpresa] = array();
            }
            $idsPorEmpresa[$idEmpresa][] = $idFiskaly;
        }
        $rows[] = $row;
    }
    mysqli_stmt_close($stmt);

    $mapaEstados = array();
    if (function_exists('fiskalyObtenerEstadosCacheMapa') && !empty($idsPorEmpresa)) {
        try {
            $mapaEstados = fiskalyObtenerEstadosCacheMapa($idsPorEmpresa);
            if (!is_array($mapaEstados)) {
                $mapaEstados = array();
            }
        } catch (Throwable $eFiskaly) {
            $mapaEstados = array();
        }
    }

    $data = array();
    foreach ($rows as $row) {
        $idFiskaly = (int) ($row['id_rel_factura_fiskaly'] ?? 0);
        $estadoFiskaly = '—';
        if ($idFiskaly > 0) {
            $estadoFiskaly = isset($mapaEstados[$idFiskaly]) && $mapaEstados[$idFiskaly] !== ''
                ? $mapaEstados[$idFiskaly]
                : 'sin_cache';
        }

        $urlImpresion = 'Impresiones/Facturas/factura_rectificativa.php?id_factura=' . (int) $row['id_factura'];
        if (function_exists('fiskalyUrlImpresionFacturaRectificativa')) {
            try {
                $urlImpresion = fiskalyUrlImpresionFacturaRectificativa(
                    (int) $row['id_factura'],
                    (string) ($row['factura_regimen'] ?? 'false'),
                    $idFiskaly,
                    false
                );
            } catch (Throwable $eUrl) {
                // se mantiene la URL clásica
            }
        }

        $puedeReenviar = ($idFiskaly > 0 && !in_array($estadoFiskaly, array('aceptada'), true)) ? 1 : 0;
        $prefijo = trim((string) ($row['prefijo_factura'] ?? ''));
        $numero = (string) ($row['numero_factura'] ?? '');
        $prefijoOrig = trim((string) ($row['prefijo_factura_original'] ?? ''));
        $facturaOrig = (string) ($row['factura_original'] ?? '');
        $total = isset($row['total_factura']) ? (float) $row['total_factura'] : 0;
        $cliente = trim((string) ($row['CLIENTEDATA'] ?? ''));

        $data[] = [
            $row['id_factura'],
            trim($prefijo . ' ' . $numero),
            !empty($row['fecha_factura']) ? $row['fecha_factura'] : '-',
            (trim((string) ($row['hora_factura'] ?? '')) !== '' ? substr(trim((string) $row['hora_factura']), 0, 8) : '-'),
            $cliente !== '' ? $cliente : '-',
            number_format($total, 2, ',', '.') . ' €',
            $row['estado_factura'] ?? '-',
            $row['tipo_pago_factura'] ?? '-',
            trim($prefijoOrig . ' ' . $facturaOrig),
            $row['rel_id_factura'] ?? 0,
            $urlImpresion,
            $estadoFiskaly,
            $idFiskaly,
            0,
            $puedeReenviar,
        ];
    }

    facturas_rect_json([
        'draw' => $draw,
        'recordsTotal' => $recordsTotal,
        'recordsFiltered' => $recordsFiltered,
        'data' => $data
    ]);
} catch (Throwable $e) {
    if (ob_get_length()) {
        ob_clean();
    }
    header('Content-Type: application/json; charset=utf-8');
    facturas_rect_json([
        'draw' => $draw,
        'recordsTotal' => 0,
        'recordsFiltered' => 0,
        'data' => [],
        'error' => 'Error al cargar los datos: ' . $e->getMessage()
    ]);
}
