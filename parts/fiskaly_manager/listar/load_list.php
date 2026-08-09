<?php
require_once '../../../include/session.php';
require_once '../../../include/functions.php';
/**
 * Archivo para cargar la lista de empresas via AJAX
 */

if (version_compare(PHP_VERSION, '7.0.0', '<')) {
    http_response_code(500);
    echo json_encode(['error' => 'Se requiere PHP 7.0 o superior']);
    exit;
}

if (ob_get_length()) {
    ob_clean();
}

header('Content-Type: application/json; charset=utf-8');

try {
    $conexion = conectar_bd();
    if (!$conexion) {
        throw new Exception('Error de conexión a la base de datos');
    }

    $draw = isset($_POST['draw']) ? (int) $_POST['draw'] : 1;
    $start = isset($_POST['start']) ? (int) $_POST['start'] : 0;
    $length = isset($_POST['length']) ? (int) $_POST['length'] : 25;
    $searchValue = isset($_POST['search']['value']) ? trim((string) $_POST['search']['value']) : '';

    $orderColumn = isset($_POST['order'][0]['column']) ? (int) $_POST['order'][0]['column'] : 1;
    $orderDir = isset($_POST['order'][0]['dir']) ? (string) $_POST['order'][0]['dir'] : 'asc';

    $filtroEmpresa = isset($_POST['filtro_empresa']) ? trim((string) $_POST['filtro_empresa']) : '';
    $filtroPoblacion = isset($_POST['filtro_poblacion']) ? trim((string) $_POST['filtro_poblacion']) : '';
    $filtroProvincia = isset($_POST['filtro_provincia']) ? trim((string) $_POST['filtro_provincia']) : '';
    $filtroFacturaDigital = isset($_POST['filtro_factura_digital']) ? trim((string) $_POST['filtro_factura_digital']) : '';
    $filtroRegionRegimen = isset($_POST['filtro_region_regimen']) ? trim((string) $_POST['filtro_region_regimen']) : '';
    $filtroTipoApi = isset($_POST['filtro_tipo_api']) ? trim((string) $_POST['filtro_tipo_api']) : '';

    if ($start < 0) {
        $start = 0;
    }
    if ($length < 1 || $length > 100) {
        $length = 25;
    }

    $columnMap = [
        0 => 'e.id_empresa',
        1 => 'e.nombre_empresa',
        2 => 'e.direccion_empresa',
        3 => 'e.poblacion_empresa',
        4 => 'e.provincia_empresa',
        5 => 'e.telefono_empresa',
        6 => 'e.cif_empresa',
        7 => 'e.factura_digital',
        8 => 'e.region_regimen',
        9 => 'e.tipo_api',
    ];

    if (!isset($columnMap[$orderColumn])) {
        $orderColumn = 1;
    }

    $orderBy = $columnMap[$orderColumn];
    $orderDirection = strtoupper($orderDir) === 'DESC' ? 'DESC' : 'ASC';

    $whereConditions = ["e.empresa_app = 'false'"];
    $params = [];
    $types = '';

    if ($searchValue !== '') {
        $whereConditions[] = "(CAST(e.id_empresa AS CHAR) = ? OR e.nombre_empresa LIKE ? OR e.direccion_empresa LIKE ? OR e.poblacion_empresa LIKE ? OR e.provincia_empresa LIKE ? OR e.telefono_empresa LIKE ? OR e.cif_empresa LIKE ? OR e.factura_digital LIKE ? OR e.region_regimen LIKE ? OR e.tipo_api LIKE ?)";
        $searchTerm = '%' . $searchValue . '%';
        $params[] = $searchValue;
        for ($i = 0; $i < 9; $i++) {
            $params[] = $searchTerm;
        }
        $types .= str_repeat('s', 10);
    }

    if ($filtroEmpresa !== '') {
        $whereConditions[] = 'e.nombre_empresa LIKE ?';
        $params[] = '%' . $filtroEmpresa . '%';
        $types .= 's';
    }

    if ($filtroPoblacion !== '') {
        $whereConditions[] = 'e.poblacion_empresa LIKE ?';
        $params[] = '%' . $filtroPoblacion . '%';
        $types .= 's';
    }

    if ($filtroProvincia !== '') {
        $whereConditions[] = 'e.provincia_empresa LIKE ?';
        $params[] = '%' . $filtroProvincia . '%';
        $types .= 's';
    }

    if ($filtroFacturaDigital !== '') {
        if ($filtroFacturaDigital === 'true') {
            $whereConditions[] = "(e.factura_digital = 'true' OR e.factura_digital = '1')";
        } else {
            $whereConditions[] = "(e.factura_digital = 'false' OR e.factura_digital IS NULL OR e.factura_digital = '' OR e.factura_digital = '0')";
        }
    }

    $regionesValidas = ['General', 'Verifactu', 'TicketBAIBizkaia', 'TicketBAIAlava', 'TicketBAIGipuzkua'];
    if ($filtroRegionRegimen !== '') {
        if ($filtroRegionRegimen === 'false') {
            $whereConditions[] = "(e.region_regimen = 'false' OR e.region_regimen IS NULL OR e.region_regimen = '')";
        } elseif (in_array($filtroRegionRegimen, $regionesValidas, true)) {
            $whereConditions[] = 'e.region_regimen = ?';
            $params[] = $filtroRegionRegimen;
            $types .= 's';
        }
    }

    $tiposApiValidos = ['test', 'produccion'];
    if ($filtroTipoApi !== '' && in_array($filtroTipoApi, $tiposApiValidos, true)) {
        $whereConditions[] = 'e.tipo_api = ?';
        $params[] = $filtroTipoApi;
        $types .= 's';
    }

    $whereClause = 'WHERE ' . implode(' AND ', $whereConditions);

    $queryCountTotal = "SELECT COUNT(*) AS total FROM empresas e WHERE e.empresa_app = 'false'";
    $resultTotal = mysqli_query($conexion, $queryCountTotal);
    if (!$resultTotal) {
        throw new Exception('Error en conteo total: ' . mysqli_error($conexion));
    }
    $recordsTotal = (int) (mysqli_fetch_assoc($resultTotal)['total'] ?? 0);
    mysqli_free_result($resultTotal);

    $queryCount = "SELECT COUNT(*) AS total FROM empresas e $whereClause";
    $stmtCount = mysqli_prepare($conexion, $queryCount);
    if (!$stmtCount) {
        throw new Exception('Error preparando conteo: ' . mysqli_error($conexion));
    }
    if ($types !== '') {
        mysqli_stmt_bind_param($stmtCount, $types, ...$params);
    }
    mysqli_stmt_execute($stmtCount);
    $resultCount = mysqli_stmt_get_result($stmtCount);
    $recordsFiltered = (int) (mysqli_fetch_assoc($resultCount)['total'] ?? 0);
    mysqli_stmt_close($stmtCount);

    $query = "
        SELECT
            e.id_empresa,
            e.nombre_empresa,
            e.direccion_empresa,
            e.poblacion_empresa,
            e.provincia_empresa,
            e.telefono_empresa,
            e.cif_empresa,
            e.factura_digital,
            e.region_regimen,
            e.tipo_api
        FROM empresas e
        $whereClause
        ORDER BY $orderBy $orderDirection
        LIMIT ?, ?
    ";

    $paramsData = $params;
    $typesData = $types . 'ii';
    $paramsData[] = $start;
    $paramsData[] = $length;

    $stmt = mysqli_prepare($conexion, $query);
    if (!$stmt) {
        throw new Exception('Error preparando consulta: ' . mysqli_error($conexion));
    }
    mysqli_stmt_bind_param($stmt, $typesData, ...$paramsData);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);

    $data = [];
    while ($row = mysqli_fetch_assoc($result)) {
        $data[] = [
            (int) $row['id_empresa'],
            $row['nombre_empresa'],
            $row['direccion_empresa'],
            $row['poblacion_empresa'],
            $row['provincia_empresa'],
            $row['telefono_empresa'],
            $row['cif_empresa'],
            $row['factura_digital'] ?: 'false',
            $row['region_regimen'] ?: '—',
            $row['tipo_api'] ?: '—',
        ];
    }
    mysqli_stmt_close($stmt);
    mysqli_close($conexion);

    echo json_encode([
        'draw' => $draw,
        'recordsTotal' => $recordsTotal,
        'recordsFiltered' => $recordsFiltered,
        'data' => $data,
    ]);
} catch (Exception $e) {
    if (isset($conexion) && $conexion) {
        mysqli_close($conexion);
    }
    http_response_code(500);
    echo json_encode([
        'error' => $e->getMessage(),
        'draw' => isset($_POST['draw']) ? (int) $_POST['draw'] : 1,
        'recordsTotal' => 0,
        'recordsFiltered' => 0,
        'data' => [],
    ]);
}
