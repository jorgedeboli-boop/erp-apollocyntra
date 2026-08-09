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
$orderColumn = isset($_POST['order'][0]['column']) ? (int) $_POST['order'][0]['column'] : 1;
$orderDir = isset($_POST['order'][0]['dir']) ? (string) $_POST['order'][0]['dir'] : 'asc';
$filtro_banco = isset($_POST['filtro_banco']) ? (int) $_POST['filtro_banco'] : 0;
$filtro_empresa = isset($_POST['filtro_empresa']) ? (int) $_POST['filtro_empresa'] : 0;

$columnMap = [
    0 => 't.id_tarjeta_banco',
    1 => 't.numerotarjeta',
    2 => 'b.nombre_banco',
    3 => 'e.nombre_empresa',
    4 => 't.por_defecto',
    5 => 't.fecha_creacion',
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
        FROM tarjetas_banco_empresas t
        LEFT JOIN bancos_config b ON b.id_banco = CAST(t.banco_tarjeta AS UNSIGNED)
        LEFT JOIN empresas e ON e.id_empresa = t.empresa_tarjeta_id
        WHERE 1=1
    ';
    $params = [];
    $types = '';

    if ($filtro_banco > 0) {
        $queryBase .= ' AND CAST(t.banco_tarjeta AS UNSIGNED) = ?';
        $params[] = $filtro_banco;
        $types .= 'i';
    }
    if ($filtro_empresa > 0) {
        $queryBase .= ' AND t.empresa_tarjeta_id = ?';
        $params[] = $filtro_empresa;
        $types .= 'i';
    }
    if ($search !== '') {
        $queryBase .= ' AND (
            CAST(t.id_tarjeta_banco AS CHAR) LIKE ?
            OR t.numerotarjeta LIKE ?
            OR IFNULL(b.nombre_banco, \'\') LIKE ?
            OR IFNULL(e.nombre_empresa, \'\') LIKE ?
            OR t.banco_tarjeta LIKE ?
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
            t.id_tarjeta_banco,
            t.numerotarjeta,
            IFNULL(b.nombre_banco, CONCAT(\'ID \', t.banco_tarjeta)) AS nombre_banco,
            IFNULL(e.nombre_empresa, \'—\') AS nombre_empresa,
            t.por_defecto,
            t.fecha_creacion,
            CAST(t.banco_tarjeta AS UNSIGNED) AS id_banco
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
        $data[] = [
            (int) $row['id_tarjeta_banco'],
            (string) $row['numerotarjeta'],
            (string) $row['nombre_banco'],
            (string) $row['nombre_empresa'],
            ((string) $row['por_defecto'] === 'true') ? 'Sí' : 'No',
            (string) $row['fecha_creacion'],
            (int) $row['id_banco'],
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
