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
    0 => 'c.id_cuenta_banco',
    1 => 'c.numerocuenta',
    2 => 'b.nombre_banco',
    3 => 'e.nombre_empresa',
    4 => 'c.por_defecto',
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
        FROM cuentas_banco_empresas c
        LEFT JOIN bancos_config b ON b.id_banco = CAST(c.banco_cuenta AS UNSIGNED)
        LEFT JOIN empresas e ON e.id_empresa = c.empresa_cuenta_id
        WHERE 1=1
    ';
    $params = [];
    $types = '';

    if ($filtro_banco > 0) {
        $queryBase .= ' AND CAST(c.banco_cuenta AS UNSIGNED) = ?';
        $params[] = $filtro_banco;
        $types .= 'i';
    }
    if ($filtro_empresa > 0) {
        $queryBase .= ' AND c.empresa_cuenta_id = ?';
        $params[] = $filtro_empresa;
        $types .= 'i';
    }
    if ($search !== '') {
        $queryBase .= ' AND (
            CAST(c.id_cuenta_banco AS CHAR) LIKE ?
            OR c.numerocuenta LIKE ?
            OR IFNULL(b.nombre_banco, \'\') LIKE ?
            OR IFNULL(e.nombre_empresa, \'\') LIKE ?
            OR c.banco_cuenta LIKE ?
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
            c.id_cuenta_banco,
            c.numerocuenta,
            IFNULL(b.nombre_banco, CONCAT(\'ID \', c.banco_cuenta)) AS nombre_banco,
            IFNULL(e.nombre_empresa, \'—\') AS nombre_empresa,
            c.por_defecto,
            c.fecha_creacion,
            CAST(c.banco_cuenta AS UNSIGNED) AS id_banco
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
            (int) $row['id_cuenta_banco'],
            (string) $row['numerocuenta'],
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
