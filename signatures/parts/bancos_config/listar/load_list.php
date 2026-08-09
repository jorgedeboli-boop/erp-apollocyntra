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
$filtro_estado = isset($_POST['filtro_estado']) ? trim((string) $_POST['filtro_estado']) : '';

$columnMap = [
    0 => 'b.id_banco',
    1 => 'b.nombre_banco',
    2 => 'b.contacto_banco',
    3 => 'b.telefono_banco',
    4 => 'b.email_banco',
    5 => 'pob.poblacion',
    6 => 'prov.nombreProvince',
    7 => 'b.estado_banco',
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
        FROM bancos_config b
        LEFT JOIN countrys c ON c.id_country = b.pais_banco
        LEFT JOIN provincias prov ON prov.id_province = b.provincia_banco
        LEFT JOIN poblacion pob ON pob.idpoblacion = b.poblacion_banco
        WHERE 1=1
    ';

    $params = [];
    $types = '';

    if ($filtro_estado === 'true' || $filtro_estado === 'false') {
        $queryBase .= ' AND b.estado_banco = ?';
        $params[] = $filtro_estado;
        $types .= 's';
    }

    if ($search !== '') {
        $queryBase .= ' AND (
            CAST(b.id_banco AS CHAR) LIKE ?
            OR b.nombre_banco LIKE ?
            OR b.contacto_banco LIKE ?
            OR b.telefono_banco LIKE ?
            OR b.email_banco LIKE ?
            OR b.direccion_banco LIKE ?
            OR IFNULL(pob.poblacion, \'\') LIKE ?
            OR IFNULL(prov.nombreProvince, \'\') LIKE ?
            OR IFNULL(c.name_spanish, \'\') LIKE ?
        )';
        $searchParam = '%' . $search . '%';
        for ($i = 0; $i < 9; $i++) {
            $params[] = $searchParam;
        }
        $types .= str_repeat('s', 9);
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
            b.id_banco,
            b.nombre_banco,
            b.contacto_banco,
            b.telefono_banco,
            b.email_banco,
            IFNULL(pob.poblacion, \'\') AS nombre_poblacion,
            IFNULL(prov.nombreProvince, \'\') AS nombre_provincia,
            b.estado_banco
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
            (int) $row['id_banco'],
            (string) $row['nombre_banco'],
            (string) $row['contacto_banco'],
            (string) $row['telefono_banco'],
            (string) $row['email_banco'],
            (string) $row['nombre_poblacion'],
            (string) $row['nombre_provincia'],
            ((string) $row['estado_banco'] === 'true') ? 'Activo' : 'Inactivo',
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
