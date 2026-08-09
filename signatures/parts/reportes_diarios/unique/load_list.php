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
$length = isset($_POST['length']) ? (int) $_POST['length'] : 25;
$search = isset($_POST['search']['value']) ? trim($_POST['search']['value']) : '';

$orderColumn = isset($_POST['order'][0]['column']) ? (int) $_POST['order'][0]['column'] : 0;
$orderDir = isset($_POST['order'][0]['dir']) ? $_POST['order'][0]['dir'] : 'desc';

$filtro_sucursal = isset($_POST['filtro_sucursal']) ? trim($_POST['filtro_sucursal']) : '';
$filtro_fecha_desde = isset($_POST['filtro_fecha_desde']) ? trim($_POST['filtro_fecha_desde']) : '';
$filtro_fecha_hasta = isset($_POST['filtro_fecha_hasta']) ? trim($_POST['filtro_fecha_hasta']) : '';
$filtro_periodo = isset($_POST['filtro_periodo']) ? trim($_POST['filtro_periodo']) : 'todos';

if ($filtro_periodo === 'personalizado') {
    $filtro_periodo = 'fecha';
}

$columnMap = [
    0 => 'av.fecha_informe',
    1 => 'av.total_euros_lotes_compra_oro',
    2 => 'av.total_gramos_compra_oro',
    3 => 'av.total_euros_lotes_compra_plata',
    4 => 'av.total_gramos_compra_plata',
    5 => 'av.total_euros_lotes_empenios',
    6 => 'av.total_gramos_empenios_oro',
    7 => 'av.total_euros_renovaciones',
    8 => 'av.total_euros_ventas',
    9 => 'av.total_gastos',
    10 => 'av.stock_valorizado_eruo',
    11 => 'sucursal.nombre_sucursal'
];

if (!isset($columnMap[$orderColumn])) {
    $orderColumn = 0;
}

$orderBy = $columnMap[$orderColumn];
$orderDirection = strtoupper($orderDir) === 'ASC' ? 'ASC' : 'DESC';

if ($start < 0) {
    $start = 0;
}
if ($length < 1 || $length > 500) {
    $length = 25;
}

function formatear_euro_reporte_diario($valor)
{
    return number_format((float) $valor, 2, ',', '.') . ' €';
}

function formatear_gramos_reporte_diario($valor)
{
    return number_format((float) $valor, 2, ',', '.') . ' gr';
}

function formatear_fecha_reporte_diario($fecha)
{
    if (empty($fecha) || $fecha === '0000-00-00') {
        return '-';
    }

    return date('d/m/Y', strtotime($fecha));
}

try {
    $conexion = conectar_bd();
    if (!$conexion) {
        throw new Exception('Error de conexión a la base de datos');
    }

    $query_base = "
        FROM informe_diario AS av
        LEFT JOIN sucursal ON av.sucursal_informe = sucursal.id_sucursal
        WHERE 1=1
    ";

    $params = [];
    $types = '';

    if ($filtro_sucursal !== '') {
        $query_base .= " AND av.sucursal_informe = ?";
        $params[] = (int) $filtro_sucursal;
        $types .= 'i';
    }

    if ($filtro_periodo === 'dia') {
        $hoy = date('Y-m-d');
        $query_base .= " AND DATE(av.fecha_informe) = ?";
        $params[] = $hoy;
        $types .= 's';
    } elseif ($filtro_periodo === 'mes') {
        $query_base .= " AND MONTH(av.fecha_informe) = MONTH(CURRENT_DATE()) AND YEAR(av.fecha_informe) = YEAR(CURRENT_DATE())";
    } elseif ($filtro_periodo === 'fecha' || $filtro_fecha_desde !== '' || $filtro_fecha_hasta !== '') {
        if ($filtro_fecha_desde !== '' && $filtro_fecha_hasta !== '') {
            $query_base .= " AND DATE(av.fecha_informe) BETWEEN ? AND ?";
            $params[] = $filtro_fecha_desde;
            $params[] = $filtro_fecha_hasta;
            $types .= 'ss';
        } elseif ($filtro_fecha_desde !== '') {
            $query_base .= " AND DATE(av.fecha_informe) >= ?";
            $params[] = $filtro_fecha_desde;
            $types .= 's';
        } elseif ($filtro_fecha_hasta !== '') {
            $query_base .= " AND DATE(av.fecha_informe) <= ?";
            $params[] = $filtro_fecha_hasta;
            $types .= 's';
        }
    }

    if ($search !== '') {
        $query_base .= " AND (
            sucursal.nombre_sucursal LIKE ?
            OR DATE_FORMAT(av.fecha_informe, '%d/%m/%Y') LIKE ?
        )";
        $search_param = '%' . $search . '%';
        $params[] = $search_param;
        $params[] = $search_param;
        $types .= 'ss';
    }

    $count_query = "SELECT COUNT(*) AS total " . $query_base;
    $stmt_count = mysqli_prepare($conexion, $count_query);
    if (!$stmt_count) {
        throw new Exception('Error al contar registros');
    }

    if ($types !== '') {
        mysqli_stmt_bind_param($stmt_count, $types, ...$params);
    }

    mysqli_stmt_execute($stmt_count);
    $result_count = mysqli_stmt_get_result($stmt_count);
    $total_records = (int) mysqli_fetch_assoc($result_count)['total'];
    mysqli_stmt_close($stmt_count);

    $result_total = mysqli_query($conexion, 'SELECT COUNT(*) AS total FROM informe_diario');
    $recordsTotal = (int) (mysqli_fetch_assoc($result_total)['total'] ?? 0);

    $data_query = "
        SELECT
            av.id_informe,
            av.fecha_informe,
            av.total_euros_lotes_compra_oro,
            av.total_gramos_compra_oro,
            av.total_euros_lotes_compra_plata,
            av.total_gramos_compra_plata,
            av.total_euros_lotes_empenios,
            av.total_gramos_empenios_oro,
            av.total_euros_renovaciones,
            av.total_euros_ventas,
            av.total_gastos,
            av.stock_valorizado_eruo,
            av.stock_articulos,
            sucursal.nombre_sucursal
        " . $query_base . "
        ORDER BY {$orderBy} {$orderDirection}
        LIMIT ?, ?
    ";

    $params_data = $params;
    $types_data = $types . 'ii';
    $params_data[] = $start;
    $params_data[] = $length;

    $stmt_data = mysqli_prepare($conexion, $data_query);
    if (!$stmt_data) {
        throw new Exception('Error al preparar consulta principal');
    }

    mysqli_stmt_bind_param($stmt_data, $types_data, ...$params_data);
    mysqli_stmt_execute($stmt_data);
    $result_data = mysqli_stmt_get_result($stmt_data);

    $data = [];
    while ($row = mysqli_fetch_assoc($result_data)) {
        $total_gastos = (float) $row['total_gastos'];

        $data[] = [
            formatear_fecha_reporte_diario($row['fecha_informe']),
            formatear_euro_reporte_diario($row['total_euros_lotes_compra_oro']),
            formatear_gramos_reporte_diario($row['total_gramos_compra_oro']),
            formatear_euro_reporte_diario($row['total_euros_lotes_compra_plata']),
            formatear_gramos_reporte_diario($row['total_gramos_compra_plata']),
            formatear_euro_reporte_diario($row['total_euros_lotes_empenios']),
            formatear_gramos_reporte_diario($row['total_gramos_empenios_oro']),
            formatear_euro_reporte_diario($row['total_euros_renovaciones']),
            formatear_euro_reporte_diario($row['total_euros_ventas']),
            formatear_euro_reporte_diario($total_gastos),
            formatear_euro_reporte_diario($row['stock_valorizado_eruo']),
            htmlspecialchars($row['nombre_sucursal'] ?: 'Sin sucursal'),
            (int) $row['id_informe'],
            [
                'fecha_informe' => $row['fecha_informe'],
                'total_gastos' => $total_gastos,
                'nombre_sucursal' => $row['nombre_sucursal'] ?: 'Sin sucursal'
            ]
        ];
    }

    mysqli_stmt_close($stmt_data);
    mysqli_close($conexion);

    echo json_encode([
        'draw' => $draw,
        'recordsTotal' => $recordsTotal,
        'recordsFiltered' => $total_records,
        'data' => $data
    ]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'error' => 'Error interno del servidor: ' . $e->getMessage(),
        'draw' => $draw,
        'recordsTotal' => 0,
        'recordsFiltered' => 0,
        'data' => []
    ]);
}
