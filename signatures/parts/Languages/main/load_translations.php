<?php
// Verificar que se ejecute solo por AJAX
if (!isset($_SERVER['HTTP_X_REQUESTED_WITH']) || strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) !== 'xmlhttprequest') {
    http_response_code(403);
    exit('Acceso denegado');
}

// Incluir archivos necesarios
require_once '../../../include/session.php';
require_once '../../../include/functions.php';

// Verificar sesión
if (!isset($_SESSION['usuario_id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'No autorizado']);
    exit;
}

// Verificar que se pase el ID del language
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    http_response_code(400);
    echo json_encode(['error' => 'ID de language requerido']);
    exit;
}

$id_lang = (int)$_GET['id'];

// Establecer conexión
$conexion = conectar_bd();
if (!$conexion) {
    http_response_code(500);
    echo json_encode(['error' => 'Error de conexión a la base de datos']);
    exit;
}

// Parámetros de DataTables
$start = isset($_POST['start']) ? (int)$_POST['start'] : 0;
$length = isset($_POST['length']) ? (int)$_POST['length'] : 25;
$searchValue = isset($_POST['search']['value']) ? trim($_POST['search']['value']) : '';
$orderColumn = isset($_POST['order'][0]['column']) ? (int)$_POST['order'][0]['column'] : 0;
$orderDir = isset($_POST['order'][0]['dir']) ? $_POST['order'][0]['dir'] : 'asc';

// Validar parámetros
if ($start < 0) $start = 0;
if ($length < 1 || $length > 100) $length = 25;

// Mapeo de columnas para ordenamiento
$columnMap = [
    0 => 't.id_translations',
    1 => 't.entry_translate',
    2 => 't.exit_translate'
];

// Validar columna de ordenamiento
if (!isset($columnMap[$orderColumn])) {
    $orderColumn = 1; // Por defecto ordenar por entry_translate
}

$orderBy = $columnMap[$orderColumn];
$orderDirection = strtoupper($orderDir) === 'ASC' ? 'ASC' : 'DESC';

// Construir condiciones de búsqueda
$whereConditions = ["t.idLang = ?"];
$params = [$id_lang];
$paramTypes = 'i';

if (!empty($searchValue)) {
    $whereConditions[] = "(t.entry_translate LIKE ? OR t.exit_translate LIKE ?)";
    $searchParam = "%{$searchValue}%";
    $params[] = $searchParam;
    $params[] = $searchParam;
    $paramTypes .= 'ss';
}

$whereClause = implode(' AND ', $whereConditions);

// Consulta para contar total de registros
$countQuery = "SELECT COUNT(*) as total FROM Translations t WHERE {$whereClause}";
$countStmt = mysqli_prepare($conexion, $countQuery);
if (!$countStmt) {
    mysqli_close($conexion);
    http_response_code(500);
    echo json_encode(['error' => 'Error en consulta de conteo']);
    exit;
}

mysqli_stmt_bind_param($countStmt, $paramTypes, ...$params);
mysqli_stmt_execute($countStmt);
$countResult = mysqli_stmt_get_result($countStmt);
$totalRecords = mysqli_fetch_assoc($countResult)['total'];
mysqli_stmt_close($countStmt);

// Consulta principal con paginación
$query = "
    SELECT 
        t.id_translations,
        t.entry_translate,
        t.exit_translate
    FROM Translations t
    WHERE {$whereClause}
    ORDER BY {$orderBy} {$orderDirection}
    LIMIT ? OFFSET ?
";

$params[] = $length;
$params[] = $start;
$paramTypes .= 'ii';

$stmt = mysqli_prepare($conexion, $query);
if (!$stmt) {
    mysqli_close($conexion);
    http_response_code(500);
    echo json_encode(['error' => 'Error en consulta principal']);
    exit;
}

mysqli_stmt_bind_param($stmt, $paramTypes, ...$params);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

$data = [];
if ($result && mysqli_num_rows($result) > 0) {
    while ($row = mysqli_fetch_assoc($result)) {
        $data[] = [
            $row['id_translations'],
            htmlspecialchars($row['entry_translate']),
            htmlspecialchars($row['exit_translate'])
        ];
    }
}

mysqli_stmt_close($stmt);
mysqli_close($conexion);

// Respuesta para serverSide
$response = [
    'draw' => isset($_POST['draw']) ? (int)$_POST['draw'] : 1,
    'recordsTotal' => $totalRecords,
    'recordsFiltered' => $totalRecords,
    'data' => $data
];

// Configurar headers para JSON
header('Content-Type: application/json');
header('Cache-Control: no-cache, must-revalidate');

echo json_encode($response);
?>
