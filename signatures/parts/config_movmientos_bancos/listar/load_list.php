<?php
require_once '../../../include/session.php';
require_once '../../../include/functions.php';

header('Content-Type: application/json; charset=utf-8');

if (!isset($_SESSION['usuario_id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'No autorizado']);
    exit;
}

$tiposConfigLabels = [
    'transferencia_saliente' => 'Transferencia saliente',
    'transferencia_entrante' => 'Transferencia entrante',
    'cobro_tarjeta' => 'Cobro tarjeta',
    'pago_tarjeta' => 'Pago tarjeta',
    'retiro_tarjeta' => 'Retiro tarjeta',
    'retiro_cuenta' => 'Retiro cuenta',
    'ingreso_cuenta' => 'Ingreso cuenta',
];

$draw = isset($_POST['draw']) ? (int) $_POST['draw'] : 1;
$start = isset($_POST['start']) ? (int) $_POST['start'] : 0;
$length = isset($_POST['length']) ? (int) $_POST['length'] : 10;
$search = isset($_POST['search']['value']) ? trim((string) $_POST['search']['value']) : '';
$orderColumn = isset($_POST['order'][0]['column']) ? (int) $_POST['order'][0]['column'] : 1;
$orderDir = isset($_POST['order'][0]['dir']) ? (string) $_POST['order'][0]['dir'] : 'asc';
$filtro_tipo = isset($_POST['filtro_tipo']) ? trim((string) $_POST['filtro_tipo']) : '';
$filtro_estado = isset($_POST['filtro_estado']) ? trim((string) $_POST['filtro_estado']) : '';

$columnMap = [
    0 => 'c.id_config',
    1 => 'c.nombre_config',
    2 => 'g.nombre_grupo',
    3 => 'c.tipo_config',
    4 => 'c.estado_config',
    5 => 'c.fecha_creacion',
];
if (!isset($columnMap[$orderColumn])) {
    $orderColumn = 1;
}
$orderBy = $columnMap[$orderColumn];
$orderDirection = strtoupper($orderDir) === 'DESC' ? 'DESC' : 'ASC';

try {
    $conexion = conectar_bd();
    if (!$conexion) {
        throw new Exception('Error de conexión a la base de datos');
    }

    $queryBase = '
        FROM config_movimientos_bancos c
        LEFT JOIN grupos_movimientos g ON g.id_grupo = c.rel_id_tipo_movimiento
        WHERE 1=1
    ';
    $params = [];
    $types = '';

    if ($filtro_tipo !== '' && isset($tiposConfigLabels[$filtro_tipo])) {
        $queryBase .= ' AND c.tipo_config = ?';
        $params[] = $filtro_tipo;
        $types .= 's';
    }
    if ($filtro_estado === 'true' || $filtro_estado === 'false') {
        $queryBase .= ' AND c.estado_config = ?';
        $params[] = $filtro_estado;
        $types .= 's';
    }
    if ($search !== '') {
        $queryBase .= ' AND (
            CAST(c.id_config AS CHAR) LIKE ?
            OR c.nombre_config LIKE ?
            OR c.tipo_config LIKE ?
            OR IFNULL(g.nombre_grupo, \'\') LIKE ?
            OR IFNULL(g.tipo_grupo, \'\') LIKE ?
        )';
        $searchParam = '%' . $search . '%';
        for ($i = 0; $i < 5; $i++) {
            $params[] = $searchParam;
        }
        $types .= 'sssss';
    }

    $stmtCount = mysqli_prepare($conexion, 'SELECT COUNT(*) AS total ' . $queryBase);
    if (!$stmtCount) {
        throw new Exception(mysqli_error($conexion));
    }
    if ($types !== '') {
        mysqli_stmt_bind_param($stmtCount, $types, ...$params);
    }
    mysqli_stmt_execute($stmtCount);
    $rowCount = mysqli_fetch_assoc(mysqli_stmt_get_result($stmtCount));
    $totalRecords = (int) ($rowCount['total'] ?? 0);
    mysqli_stmt_close($stmtCount);

    $queryMain = '
        SELECT
            c.id_config,
            c.nombre_config,
            c.tipo_config,
            c.estado_config,
            c.fecha_creacion,
            c.rel_id_tipo_movimiento,
            IFNULL(g.nombre_grupo, CONCAT(\'ID \', c.rel_id_tipo_movimiento)) AS nombre_grupo
        ' . $queryBase . '
        ORDER BY ' . $orderBy . ' ' . $orderDirection . '
        LIMIT ? OFFSET ?
    ';
    $params[] = $length;
    $params[] = $start;
    $types .= 'ii';

    $stmtMain = mysqli_prepare($conexion, $queryMain);
    if (!$stmtMain) {
        throw new Exception(mysqli_error($conexion));
    }
    mysqli_stmt_bind_param($stmtMain, $types, ...$params);
    mysqli_stmt_execute($stmtMain);
    $resultMain = mysqli_stmt_get_result($stmtMain);

    $data = [];
    while ($row = mysqli_fetch_assoc($resultMain)) {
        $tipo = (string) ($row['tipo_config'] ?? '');
        $data[] = [
            (int) $row['id_config'],
            (string) $row['nombre_config'],
            (string) $row['nombre_grupo'],
            $tiposConfigLabels[$tipo] ?? $tipo,
            ((string) $row['estado_config'] === 'true') ? 'Activo' : 'Inactivo',
            (string) $row['fecha_creacion'],
        ];
    }
    mysqli_stmt_close($stmtMain);
    mysqli_close($conexion);

    echo json_encode([
        'draw' => $draw,
        'recordsTotal' => $totalRecords,
        'recordsFiltered' => $totalRecords,
        'data' => $data,
    ], JSON_UNESCAPED_UNICODE);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'error' => $e->getMessage(),
        'draw' => $draw,
        'recordsTotal' => 0,
        'recordsFiltered' => 0,
        'data' => [],
    ], JSON_UNESCAPED_UNICODE);
}
