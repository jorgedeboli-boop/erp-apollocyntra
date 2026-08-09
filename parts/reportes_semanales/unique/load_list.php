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

$orderColumn = isset($_POST['order'][0]['column']) ? (int) $_POST['order'][0]['column'] : 26;
$orderDir = isset($_POST['order'][0]['dir']) ? $_POST['order'][0]['dir'] : 'asc';

$filtro_empresa = isset($_POST['filtro_empresa']) ? trim($_POST['filtro_empresa']) : '';
$filtro_sucursal = isset($_POST['filtro_sucursal']) ? trim($_POST['filtro_sucursal']) : '';
$filtro_semana = isset($_POST['filtro_semana']) ? trim($_POST['filtro_semana']) : '';
$filtro_anio = isset($_POST['filtro_anio']) ? trim($_POST['filtro_anio']) : '';

$columnMap = [
    0 => 'sucursal.id_sucursal',
    1 => 'sucursal.nombre_sucursal',
    2 => 'av.numero_semana',
    3 => 'av.fecha_desde',
    4 => 'av.fecha_hasta',
    5 => 'av.total_euros_lotes_compra_oro',
    6 => 'av.ajustes_de_lotes',
    7 => 'av.total_gramos_compra_oro',
    8 => 'precio_venta_fundicion_oro',
    9 => 'av.beneficio_fundicion_oro',
    10 => 'av.total_euros_lotes_compra_plata',
    11 => 'av.total_gramos_compra_plata',
    12 => 'precio_venta_fundicion_plata',
    13 => 'av.beneficio_fundicion_plata',
    14 => 'av.beneficio_fundicion',
    15 => 'av.total_euros_renovaciones',
    16 => 'beneficio_ventas',
    17 => 'gramos_oficina',
    18 => 'beneficio_stock_fundicion',
    19 => 'ingresos_arreglos_joyerias',
    20 => 'av.total_gastos',
    21 => 'gastos_no_tienda',
    22 => 'beneficio_tienda_porcentaje',
    23 => 'av.beneficio_tienda',
    26 => 'empresa.nombre_empresa'
];

$ordenPreferidos = [
    8 => ['precio_venta_fundicion_oro'],
    12 => ['precio_venta_fundicion_plata'],
    16 => ['beneficio_ventas', 'total_beneficio_ventas'],
    17 => ['gramos_oficina'],
    18 => ['beneficio_stock_fundicion', 'stock_valorizado_eruo'],
    19 => ['ingresos_arreglos_joyerias'],
    21 => ['gastos_no_tienda', 'yulinfo'],
];

if (!isset($columnMap[$orderColumn])) {
    $orderColumn = 26;
}

$orderBy = $columnMap[$orderColumn];
$orderDirection = strtoupper($orderDir) === 'DESC' ? 'DESC' : 'ASC';

if ($start < 0) {
    $start = 0;
}
if ($length < 1 || $length > 500) {
    $length = 25;
}

function formatear_euro_reporte($valor)
{
    return number_format((float) $valor, 2, ',', '.') . ' €';
}

function formatear_gramos_reporte($valor)
{
    return number_format((float) $valor, 2, ',', '.') . ' gr';
}

function formatear_porcentaje_reporte($valor)
{
    return number_format((float) $valor, 0, ',', '.') . ' %';
}

function formatear_fecha_reporte($fecha)
{
    if (empty($fecha) || $fecha === '0000-00-00') {
        return '-';
    }

    return date('d/m/Y', strtotime($fecha));
}

function rs_obtener_columnas_informe_semanal(mysqli $conexion)
{
    $columnas = [];
    $resultado = mysqli_query($conexion, 'DESCRIBE informe_semanal');

    if ($resultado) {
        while ($row = mysqli_fetch_assoc($resultado)) {
            if (!empty($row['Field'])) {
                $columnas[] = $row['Field'];
            }
        }
        mysqli_free_result($resultado);
    }

    return $columnas;
}

function rs_expr_informe(array $columnasDb, array $preferidos)
{
    foreach ($preferidos as $columna) {
        if (in_array($columna, $columnasDb, true)) {
            return 'av.' . $columna;
        }
    }

    return '0';
}

function rs_select_informe(array $columnasDb, array $preferidos, $alias)
{
    $expr = rs_expr_informe($columnasDb, $preferidos);

    if ($expr === '0') {
        return '0 AS ' . $alias;
    }

    if ($expr === 'av.' . $alias) {
        return $expr;
    }

    return $expr . ' AS ' . $alias;
}

try {
    $conexion = conectar_bd();
    if (!$conexion) {
        throw new Exception('Error de conexión a la base de datos');
    }

    if ($filtro_semana === '') {
        $semana_default = obtener_numero_semana();
        $filtro_semana = $semana_default !== false ? (string) $semana_default : '';
    }

    if ($filtro_anio === '') {
        $filtro_anio = (string) date('Y');
    }

    $columnasInforme = rs_obtener_columnas_informe_semanal($conexion);

    if (isset($ordenPreferidos[$orderColumn])) {
        $orderBy = rs_expr_informe($columnasInforme, $ordenPreferidos[$orderColumn]);
    } elseif (
        strpos($columnMap[$orderColumn], 'av.') === 0
        || strpos($columnMap[$orderColumn], 'sucursal.') === 0
        || strpos($columnMap[$orderColumn], 'empresa.') === 0
    ) {
        $orderBy = $columnMap[$orderColumn];
    } else {
        $orderBy = rs_expr_informe($columnasInforme, [$columnMap[$orderColumn]]);
    }

    $selectPrecioVentaFundicionOro = rs_select_informe($columnasInforme, ['precio_venta_fundicion_oro'], 'precio_venta_fundicion_oro');
    $selectPrecioVentaFundicionPlata = rs_select_informe($columnasInforme, ['precio_venta_fundicion_plata'], 'precio_venta_fundicion_plata');
    $selectBeneficioVentas = rs_select_informe($columnasInforme, ['beneficio_ventas', 'total_beneficio_ventas'], 'beneficio_ventas');
    $selectGramosOficina = rs_select_informe($columnasInforme, ['gramos_oficina'], 'gramos_oficina');
    $selectBeneficioStockFundicion = rs_select_informe($columnasInforme, ['beneficio_stock_fundicion', 'stock_valorizado_eruo'], 'beneficio_stock_fundicion');
    $selectIngresosArreglos = rs_select_informe($columnasInforme, ['ingresos_arreglos_joyerias'], 'ingresos_arreglos_joyerias');
    $selectGastosNoTienda = rs_select_informe($columnasInforme, ['gastos_no_tienda', 'yulinfo'], 'gastos_no_tienda');
    $selectYulinfo = rs_select_informe($columnasInforme, ['yulinfo'], 'yulinfo');
    $selectBeneficioTiendaPorcentaje = rs_select_informe($columnasInforme, ['beneficio_tienda_porcentaje'], 'beneficio_tienda_porcentaje');

    $query_base = "
        FROM informe_semanal AS av
        LEFT JOIN sucursal ON av.sucursal_informe = sucursal.id_sucursal
        LEFT JOIN empresas AS empresa ON av.empresa_informe_id = empresa.id_empresa
        WHERE 1=1
    ";

    $params = [];
    $types = '';

    if ($filtro_empresa !== '') {
        $query_base .= " AND av.empresa_informe_id = ?";
        $params[] = (int) $filtro_empresa;
        $types .= 'i';
    }

    if ($filtro_sucursal !== '') {
        $query_base .= " AND av.sucursal_informe = ?";
        $params[] = (int) $filtro_sucursal;
        $types .= 'i';
    }

    if ($filtro_semana !== '') {
        $query_base .= " AND av.numero_semana = ?";
        $params[] = (int) $filtro_semana;
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
            OR CAST(sucursal.id_sucursal AS CHAR) LIKE ?
            OR empresa.nombre_empresa LIKE ?
            OR av.numero_semana LIKE ?
        )";
        $search_param = '%' . $search . '%';
        $params[] = $search_param;
        $params[] = $search_param;
        $params[] = $search_param;
        $params[] = $search_param;
        $types .= 'ssss';
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
            av.numero_semana,
            av.year_informe,
            av.fecha_desde,
            av.fecha_hasta,
            av.fecha_informe,
            av.total_euros_lotes_compra_oro,
            av.ajustes_de_lotes,
            av.total_gramos_compra_oro,
            {$selectPrecioVentaFundicionOro},
            av.beneficio_fundicion_oro,
            av.total_euros_lotes_compra_plata,
            av.total_gramos_compra_plata,
            {$selectPrecioVentaFundicionPlata},
            av.beneficio_fundicion_plata,
            av.beneficio_fundicion,
            av.total_euros_renovaciones,
            {$selectBeneficioVentas},
            {$selectGramosOficina},
            {$selectBeneficioStockFundicion},
            {$selectIngresosArreglos},
            av.total_gastos,
            {$selectGastosNoTienda},
            {$selectBeneficioTiendaPorcentaje},
            av.beneficio_tienda,
            {$selectYulinfo},
            av.empresa_informe_id,
            sucursal.id_sucursal,
            sucursal.nombre_sucursal,
            empresa.nombre_empresa
        " . $query_base . "
        ORDER BY {$orderBy} {$orderDirection}, empresa.nombre_empresa ASC, sucursal.nombre_sucursal ASC
        LIMIT ?, ?
    ";

    $params_data = $params;
    $types_data = $types . 'ii';
    $params_data[] = $start;
    $params_data[] = $length;

    $stmt_data = mysqli_prepare($conexion, $data_query);
    if (!$stmt_data) {
        throw new Exception('Error al preparar consulta principal: ' . mysqli_error($conexion));
    }

    mysqli_stmt_bind_param($stmt_data, $types_data, ...$params_data);
    mysqli_stmt_execute($stmt_data);
    $result_data = mysqli_stmt_get_result($stmt_data);

    $data = [];
    while ($row = mysqli_fetch_assoc($result_data)) {
        $total_gastos = (float) $row['total_gastos'];
        $yulinfo = (float) $row['yulinfo'];

        $nombreSucursal = $row['nombre_sucursal'] ?: 'Sin sucursal';
        $nombreEmpresa = $row['nombre_empresa'] ?: 'Sin empresa';

        $data[] = [
            (int) $row['id_sucursal'],
            htmlspecialchars($nombreSucursal),
            (int) $row['numero_semana'],
            formatear_fecha_reporte($row['fecha_desde']),
            formatear_fecha_reporte($row['fecha_hasta']),
            formatear_euro_reporte($row['total_euros_lotes_compra_oro']),
            formatear_euro_reporte($row['ajustes_de_lotes']),
            formatear_gramos_reporte($row['total_gramos_compra_oro']),
            formatear_euro_reporte($row['precio_venta_fundicion_oro']),
            formatear_euro_reporte($row['beneficio_fundicion_oro']),
            formatear_euro_reporte($row['total_euros_lotes_compra_plata']),
            formatear_gramos_reporte($row['total_gramos_compra_plata']),
            formatear_euro_reporte($row['precio_venta_fundicion_plata']),
            formatear_euro_reporte($row['beneficio_fundicion_plata']),
            formatear_euro_reporte($row['beneficio_fundicion']),
            formatear_euro_reporte($row['total_euros_renovaciones']),
            formatear_euro_reporte($row['beneficio_ventas']),
            formatear_gramos_reporte($row['gramos_oficina']),
            formatear_euro_reporte($row['beneficio_stock_fundicion']),
            formatear_euro_reporte($row['ingresos_arreglos_joyerias']),
            formatear_euro_reporte($total_gastos),
            formatear_euro_reporte($row['gastos_no_tienda']),
            formatear_porcentaje_reporte($row['beneficio_tienda_porcentaje']),
            number_format((float) $row['beneficio_tienda'], 0, ',', '.') . ' €',
            (int) $row['id_informe'],
            [
                'year_informe' => (int) $row['year_informe'],
                'total_gastos' => $total_gastos,
                'yulinfo' => $yulinfo,
                'nombre_sucursal' => $nombreSucursal,
                'nombre_empresa' => $nombreEmpresa,
                'empresa_informe_id' => (int) $row['empresa_informe_id']
            ],
            htmlspecialchars($nombreEmpresa)
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
