<?php
require_once '../../../include/session.php';
require_once '../../../include/functions.php';

header('Content-Type: application/json; charset=utf-8');

if (!isset($_SESSION['usuario_id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'No autorizado']);
    exit;
}

$draw = isset($_POST['draw']) ? (int) $_POST['draw'] : 1;
$start = isset($_POST['start']) ? (int) $_POST['start'] : 0;
$length = isset($_POST['length']) ? (int) $_POST['length'] : 10;
$search = isset($_POST['search']['value']) ? trim((string) $_POST['search']['value']) : '';
$orderColumn = isset($_POST['order'][0]['column']) ? (int) $_POST['order'][0]['column'] : 0;
$orderDir = isset($_POST['order'][0]['dir']) ? (string) $_POST['order'][0]['dir'] : 'desc';

if ($start < 0) {
    $start = 0;
}
if ($length < 1 || $length > 100) {
    $length = 25;
}

$columnMap = [
    0 => 's.id_sello',
    1 => 's.nombre_sello',
    2 => 's.sello_logotipo',
    3 => 's.fecha_creacion',
    4 => 'u.usuario',
];

if (!isset($columnMap[$orderColumn])) {
    $orderColumn = 0;
}

$orderBy = $columnMap[$orderColumn];
$orderDirection = strtoupper($orderDir) === 'ASC' ? 'ASC' : 'DESC';

try {
    $conexion = conectar_bd();
    if (!$conexion) {
        throw new Exception('Error de conexión a la base de datos');
    }

    $queryBase = '
        FROM sellos s
        LEFT JOIN usuarios u ON s.creado_por = u.id_usuario
        WHERE 1=1
    ';

    $params = [];
    $types = '';

    if ($search !== '') {
        $queryBase .= ' AND (
            CAST(s.id_sello AS CHAR) LIKE ?
            OR s.nombre_sello LIKE ?
            OR IFNULL(u.usuario, \'\') LIKE ?
            OR DATE_FORMAT(s.fecha_creacion, \'%d-%m-%Y\') LIKE ?
        )';
        $searchParam = '%' . $search . '%';
        for ($i = 0; $i < 4; $i++) {
            $params[] = $searchParam;
        }
        $types .= 'ssss';
    }

    $queryCount = 'SELECT COUNT(*) AS total ' . $queryBase;
    $stmtCount = mysqli_prepare($conexion, $queryCount);
    if (!$stmtCount) {
        throw new Exception(mysqli_error($conexion));
    }
    if ($types !== '') {
        mysqli_stmt_bind_param($stmtCount, $types, ...$params);
    }
    mysqli_stmt_execute($stmtCount);
    $resultCount = mysqli_stmt_get_result($stmtCount);
    $rowCount = $resultCount ? mysqli_fetch_assoc($resultCount) : null;
    $totalRecords = (int) ($rowCount['total'] ?? 0);
    mysqli_stmt_close($stmtCount);

    $queryMain = '
        SELECT
            s.id_sello,
            s.nombre_sello,
            s.sello_logotipo,
            s.fecha_creacion,
            IFNULL(u.usuario, \'\') AS creado_por_nombre
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
        $fecha = '';
        if (!empty($row['fecha_creacion']) && $row['fecha_creacion'] !== '0000-00-00' && $row['fecha_creacion'] !== '0000-00-00 00:00:00') {
            $ts = strtotime($row['fecha_creacion']);
            if ($ts !== false) {
                $fecha = date('d-m-Y', $ts);
            }
        }

        $data[] = [
            (int) $row['id_sello'],
            (string) $row['nombre_sello'],
            ((string) $row['sello_logotipo'] === 'true') ? 'SI' : 'NO',
            $fecha,
            (string) $row['creado_por_nombre'],
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
