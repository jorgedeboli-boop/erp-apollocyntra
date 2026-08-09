<?php
require_once '../../../include/session.php';
require_once '../../../include/functions.php';
require_once __DIR__ . '/semanas_precio_helper.php';

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
$orderDir = isset($_POST['order'][0]['dir']) ? $_POST['order'][0]['dir'] : 'asc';

$filtro_anio = isset($_POST['filtro_anio']) ? trim($_POST['filtro_anio']) : '';
$filtro_semana = isset($_POST['filtro_semana']) ? trim($_POST['filtro_semana']) : '';
$filtro_fecha_desde = isset($_POST['filtro_fecha_desde']) ? trim($_POST['filtro_fecha_desde']) : '';
$filtro_fecha_hasta = isset($_POST['filtro_fecha_hasta']) ? trim($_POST['filtro_fecha_hasta']) : '';

$columnMap = [
    0 => 'numero_semana',
    1 => 'fecha_semana_desde',
    2 => 'fecha_semana_hasta',
    3 => 'anyo_listado',
    4 => 'precio_24_mercado',
    5 => 'media_porcentual_diferencia',
    6 => 'precio_gramo_oro',
    7 => 'calculo_precio_gramo'
];

if (!isset($columnMap[$orderColumn])) {
    $orderColumn = 0;
}

$orderBy = 'l.' . $columnMap[$orderColumn];
$orderDirection = strtoupper($orderDir) === 'DESC' ? 'DESC' : 'ASC';

if ($start < 0) {
    $start = 0;
}
if ($length < 1 || $length > 500) {
    $length = 25;
}

function formatear_decimal_semana($valor, $suffix = '')
{
    $texto = number_format((float) $valor, 2, ',', '.');
    return $suffix !== '' ? $texto . ' ' . $suffix : $texto;
}

function media_porcentual_visible($media, $precioGramoOro, $precio24Mercado)
{
    if ((float) $precioGramoOro <= 0 || (float) $precio24Mercado <= 0) {
        return 0.0;
    }

    return (float) $media;
}

function formatear_fecha_semana($fecha)
{
    if (empty($fecha) || $fecha === '0000-00-00') {
        return '-';
    }

    return date('d/m/Y', strtotime($fecha));
}

function normalizar_calculo_precio_gramo($valor)
{
    $texto = strtolower(trim((string) $valor));

    if ($texto === '' || $texto === '0' || $texto === 'false') {
        return 'false';
    }

    if (in_array($texto, ['automatico', 'manual', 'proformas'], true)) {
        return $texto;
    }

    return 'false';
}

try {
    $conexion = conectar_bd();
    if (!$conexion) {
        throw new Exception('Error de conexión a la base de datos');
    }

    if ($filtro_anio === '') {
        $filtro_anio = (string) date('Y');
    }

    semanas_aplicar_precio_gramo_automatico($conexion);

    $query_base = " FROM listado_numero_semanas AS l WHERE 1=1 ";
    $params = [];
    $types = '';

    if ($filtro_anio !== '') {
        $query_base .= " AND l.anyo_listado = ? ";
        $params[] = (int) $filtro_anio;
        $types .= 'i';
    }

    if ($filtro_semana !== '') {
        $query_base .= " AND l.numero_semana = ? ";
        $params[] = (int) $filtro_semana;
        $types .= 'i';
    }

    if ($filtro_fecha_desde !== '' && preg_match('/^\d{4}-\d{2}-\d{2}$/', $filtro_fecha_desde)) {
        $query_base .= " AND l.fecha_semana_hasta >= ? ";
        $params[] = $filtro_fecha_desde;
        $types .= 's';
    }

    if ($filtro_fecha_hasta !== '' && preg_match('/^\d{4}-\d{2}-\d{2}$/', $filtro_fecha_hasta)) {
        $query_base .= " AND l.fecha_semana_desde <= ? ";
        $params[] = $filtro_fecha_hasta;
        $types .= 's';
    }

    if ($search !== '') {
        $query_base .= " AND (
            CAST(l.numero_semana AS CHAR) LIKE ?
            OR CAST(l.anyo_listado AS CHAR) LIKE ?
            OR CAST(l.precio_gramo_oro AS CHAR) LIKE ?
        ) ";
        $search_param = '%' . $search . '%';
        $params[] = $search_param;
        $params[] = $search_param;
        $params[] = $search_param;
        $types .= 'sss';
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
            l.id_numero_semana,
            l.numero_semana,
            l.fecha_semana_desde,
            l.fecha_semana_hasta,
            l.anyo_listado,
            l.precio_24_mercado,
            l.media_porcentual_diferencia,
            l.precio_gramo_oro,
            l.calculo_precio_gramo,
            (
                SELECT prev.precio_gramo_oro
                FROM listado_numero_semanas AS prev
                WHERE prev.anyo_listado = l.anyo_listado
                  AND prev.numero_semana = l.numero_semana - 1
                LIMIT 1
            ) AS precio_gramo_oro_anterior,
            (
                SELECT prev.precio_24_mercado
                FROM listado_numero_semanas AS prev
                WHERE prev.anyo_listado = l.anyo_listado
                  AND prev.numero_semana = l.numero_semana - 1
                LIMIT 1
            ) AS precio_24_mercado_anterior
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
        $precioGramoOro = (float) $row['precio_gramo_oro'];
        $precio24Mercado = (float) $row['precio_24_mercado'];

        $precioOroAnterior = $row['precio_gramo_oro_anterior'];
        $precioOroAnterior = $precioOroAnterior !== null && $precioOroAnterior !== '' ? (float) $precioOroAnterior : null;
        $precioMercadoAnterior = $row['precio_24_mercado_anterior'];
        $precioMercadoAnterior = $precioMercadoAnterior !== null && $precioMercadoAnterior !== '' ? (float) $precioMercadoAnterior : null;

        $data[] = [
            (int) $row['numero_semana'],
            formatear_fecha_semana($row['fecha_semana_desde']),
            formatear_fecha_semana($row['fecha_semana_hasta']),
            (int) $row['anyo_listado'],
            $precio24Mercado,
            media_porcentual_visible($row['media_porcentual_diferencia'], $precioGramoOro, $precio24Mercado),
            $precioGramoOro,
            normalizar_calculo_precio_gramo($row['calculo_precio_gramo']),
            (int) $row['id_numero_semana'],
            $precioOroAnterior,
            $precioMercadoAnterior
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
