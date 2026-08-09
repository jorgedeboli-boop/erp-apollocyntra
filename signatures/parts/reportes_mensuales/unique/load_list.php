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

$orderColumn = isset($_POST['order'][0]['column']) ? (int) $_POST['order'][0]['column'] : 15;
$orderDir = isset($_POST['order'][0]['dir']) ? $_POST['order'][0]['dir'] : 'asc';

$filtro_sucursal = isset($_POST['filtro_sucursal']) ? trim($_POST['filtro_sucursal']) : '';
$filtro_mes = isset($_POST['filtro_mes']) ? trim($_POST['filtro_mes']) : '';
$filtro_anio = isset($_POST['filtro_anio']) ? trim($_POST['filtro_anio']) : '';

$columnMap = [
    0 => 'av.numero_mes',
    1 => 'av.fecha_desde',
    2 => 'av.fecha_hasta',
    3 => 'av.total_euros_lotes_compra_oro',
    4 => 'av.total_gramos_compra_oro',
    5 => 'av.total_euros_lotes_compra_plata',
    6 => 'av.total_gramos_compra_plata',
    7 => 'av.total_euros_lotes_empenios',
    8 => 'av.total_gramos_empenios_oro',
    9 => 'av.total_euros_renovaciones',
    10 => 'av.total_euros_ventas',
    11 => 'av.total_gastos',
    12 => 'av.stock_valorizado_eruo',
    13 => 'av.yulinfo',
    14 => 'av.beneficio_tienda',
    15 => 'sucursal.nombre_sucursal'
];

if (!isset($columnMap[$orderColumn])) {
    $orderColumn = 15;
}

$orderBy = $columnMap[$orderColumn];
$orderDirection = strtoupper($orderDir) === 'DESC' ? 'DESC' : 'ASC';

if ($start < 0) {
    $start = 0;
}
if ($length < 1 || $length > 500) {
    $length = 25;
}

function formatear_euro_reporte_mensual($valor)
{
    return number_format((float) $valor, 2, ',', '.') . ' €';
}

function formatear_gramos_reporte_mensual($valor)
{
    return number_format((float) $valor, 2, ',', '.') . ' gr';
}

function formatear_fecha_reporte_mensual($fecha)
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

    if ($filtro_mes === '') {
        $filtro_mes = (string) date('n');
    }

    if ($filtro_anio === '') {
        $filtro_anio = (string) date('Y');
    }

    $query_base = "
        FROM informe_mensual AS av
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

    if ($filtro_mes !== '') {
        $query_base .= " AND av.numero_mes = ?";
        $params[] = (int) $filtro_mes;
        $types .= 'i';
    }

    if ($filtro_anio !== '') {
        $query_base .= " AND av.year_informe = ?";
        $params[] = (int) $filtro_anio;
        $types .= 'i';
    }

    if ($search !== '') {
        $query_base .= " AND (
            sucursal.nombre_sucursal LIKE ?
            OR av.numero_mes LIKE ?
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

    $data_query = "
        SELECT
            av.id_informe,
            av.numero_mes,
            av.year_informe,
            av.fecha_desde,
            av.fecha_hasta,
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
            av.beneficio_tienda,
            av.yulinfo,
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
        $yulinfo = (float) $row['yulinfo'];

        $data[] = [
            (int) $row['numero_mes'],
            formatear_fecha_reporte_mensual($row['fecha_desde']),
            formatear_fecha_reporte_mensual($row['fecha_hasta']),
            formatear_euro_reporte_mensual($row['total_euros_lotes_compra_oro']),
            formatear_gramos_reporte_mensual($row['total_gramos_compra_oro']),
            formatear_euro_reporte_mensual($row['total_euros_lotes_compra_plata']),
            formatear_gramos_reporte_mensual($row['total_gramos_compra_plata']),
            formatear_euro_reporte_mensual($row['total_euros_lotes_empenios']),
            formatear_gramos_reporte_mensual($row['total_gramos_empenios_oro']),
            formatear_euro_reporte_mensual($row['total_euros_renovaciones']),
            formatear_euro_reporte_mensual($row['total_euros_ventas']),
            formatear_euro_reporte_mensual($total_gastos),
            formatear_euro_reporte_mensual($row['stock_valorizado_eruo']),
            formatear_euro_reporte_mensual($yulinfo),
            formatear_euro_reporte_mensual($row['beneficio_tienda']),
            htmlspecialchars($row['nombre_sucursal'] ?: 'Sin sucursal'),
            (int) $row['id_informe'],
            [
                'year_informe' => (int) $row['year_informe'],
                'total_gastos' => $total_gastos,
                'yulinfo' => $yulinfo,
                'nombre_sucursal' => $row['nombre_sucursal'] ?: 'Sin sucursal'
            ]
        ];
    }

    mysqli_stmt_close($stmt_data);
    mysqli_close($conexion);

    echo json_encode([
        'draw' => $draw,
        'recordsTotal' => $total_records,
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
