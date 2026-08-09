<?php
require_once '../../../include/session.php';
require_once '../../../include/functions.php';

header('Content-Type: application/json; charset=utf-8');

if (!isset($_SESSION['usuario_id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'No autorizado']);
    exit;
}

$is_export_all = isset($_POST['export_all']) && (string) $_POST['export_all'] === '1';

$draw = isset($_POST['draw']) ? (int) $_POST['draw'] : 1;
$start = isset($_POST['start']) ? (int) $_POST['start'] : 0;
$length = isset($_POST['length']) ? (int) $_POST['length'] : 25;
$search = isset($_POST['search']['value'])
    ? trim($_POST['search']['value'])
    : (isset($_POST['search']) && is_string($_POST['search']) ? trim($_POST['search']) : '');

if ($is_export_all) {
    $start = 0;
    $length = 10000;
}

$orderColumn = isset($_POST['order'][0]['column']) ? (int) $_POST['order'][0]['column'] : 0;
$orderDir = isset($_POST['order'][0]['dir']) ? $_POST['order'][0]['dir'] : 'asc';

$filtro_empresa = isset($_POST['filtro_empresa']) ? trim($_POST['filtro_empresa']) : '';
$filtro_sucursal = isset($_POST['filtro_sucursal']) ? trim($_POST['filtro_sucursal']) : '';
$filtro_fecha_desde = isset($_POST['filtro_fecha_desde']) ? trim($_POST['filtro_fecha_desde']) : '';
$filtro_fecha_hasta = isset($_POST['filtro_fecha_hasta']) ? trim($_POST['filtro_fecha_hasta']) : '';
$filtro_periodo = isset($_POST['filtro_periodo']) ? trim($_POST['filtro_periodo']) : 'dia';

$columnMap = [
    0 => 'av.fecha_informe',
    1 => 'sucursal.nombre_sucursal',
    2 => 'rango_lotes.primer_lote',
    3 => 'av.total_euros_lotes_compra_oro',
    4 => 'av.total_lotes_compra_oro',
    5 => 'av.total_gramos_compra_oro',
    6 => 'av.media_pagado_oro_compra',
    7 => 'av.total_euros_lotes_compra_plata',
    8 => 'av.total_gramos_compra_plata',
    9 => 'av.total_lotes_compra_plata',
    10 => 'av.media_pagado_plata_compra',
    11 => 'av.total_lotes_empenios_oro',
    12 => 'av.total_euros_lotes_empenios_oro',
    13 => 'av.total_gramos_empenios_oro',
    14 => 'av.total_euros_renovaciones',
    15 => 'av.total_empenyos_retirados',
    16 => 'av.total_euros_empenyos_retirados',
    17 => 'av.total_gramos_empenios_retirados',
    18 => 'av.total_contratos_intervenidos',
    19 => 'av.total_euros_contratos_intervenidos',
    20 => 'av.total_gramos_contratos_intervenidos',
    21 => 'av.total_ventas',
    22 => 'av.total_euros_ventas',
    23 => 'av.total_beneficio_ventas',
    24 => 'av.total_ventas_plazo',
    25 => 'av.total_ventas_plazo_euro',
    26 => 'av.total_gastos',
    27 => 'av.total_caja_entradas',
    28 => 'av.total_caja_salidas',
    29 => 'av.total_operaciones_tarjeta',
    30 => 'av.total_operaciones_trasnferencia_entrada',
    31 => 'av.total_operaciones_trasnferencia_salida',
    32 => 'av.total_operaciones_bizum',
    35 => 'empresa.nombre_empresa'
];

$ordenPreferidos = [];

if (!isset($columnMap[$orderColumn])) {
    $orderColumn = 0;
}

$orderBy = $columnMap[$orderColumn];
$orderDirection = strtoupper($orderDir) === 'DESC' ? 'DESC' : 'ASC';

if ($start < 0) {
    $start = 0;
}
if ($is_export_all) {
    if ($length < 1 || $length > 20000) {
        $length = 10000;
    }
} elseif ($length < 1 || $length > 500) {
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

    return date('d-m-Y', strtotime($fecha));
}

function rg_obtener_columnas_informe_diario(mysqli $conexion)
{
    $columnas = [];
    $resultado = mysqli_query($conexion, 'DESCRIBE informe_diario');

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

function rg_expr_informe(array $columnasDb, array $preferidos)
{
    foreach ($preferidos as $columna) {
        if (in_array($columna, $columnasDb, true)) {
            return 'av.' . $columna;
        }
    }

    return '0';
}

function rg_select_informe(array $columnasDb, array $preferidos, $alias)
{
    $expr = rg_expr_informe($columnasDb, $preferidos);

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

    $columnasInforme = rg_obtener_columnas_informe_diario($conexion);

    if (isset($ordenPreferidos[$orderColumn])) {
        $orderBy = rg_expr_informe($columnasInforme, $ordenPreferidos[$orderColumn]);
    } elseif (
        strpos($columnMap[$orderColumn], 'av.') === 0
        || strpos($columnMap[$orderColumn], 'sucursal.') === 0
        || strpos($columnMap[$orderColumn], 'empresa.') === 0
        || strpos($columnMap[$orderColumn], 'rango_lotes.') === 0
    ) {
        $orderBy = $columnMap[$orderColumn];
    } else {
        $orderBy = rg_expr_informe($columnasInforme, [$columnMap[$orderColumn]]);
    }

    $selectYulinfo = rg_select_informe($columnasInforme, ['yulinfo'], 'yulinfo');
    $selectTotalVentas = rg_select_informe($columnasInforme, ['total_ventas'], 'total_ventas');
    $selectEurosVentas = rg_select_informe($columnasInforme, ['total_euros_ventas'], 'total_euros_ventas');
    $selectBeneficioVentas = rg_select_informe($columnasInforme, ['total_beneficio_ventas'], 'total_beneficio_ventas');
    $selectTotalVentasPlazo = rg_select_informe($columnasInforme, ['total_ventas_plazo'], 'total_ventas_plazo');
    $selectEurosVentasPlazo = rg_select_informe($columnasInforme, ['total_ventas_plazo_euro'], 'total_ventas_plazo_euro');
    $selectCajaEntradas = rg_select_informe($columnasInforme, ['total_caja_entradas'], 'total_caja_entradas');
    $selectCajaSalidas = rg_select_informe($columnasInforme, ['total_caja_salidas'], 'total_caja_salidas');
    $selectOperacionesTarjeta = rg_select_informe($columnasInforme, ['total_operaciones_tarjeta'], 'total_operaciones_tarjeta');
    $selectTransferenciaEntrada = rg_select_informe($columnasInforme, ['total_operaciones_trasnferencia_entrada'], 'total_operaciones_trasnferencia_entrada');
    $selectTransferenciaSalida = rg_select_informe($columnasInforme, ['total_operaciones_trasnferencia_salida'], 'total_operaciones_trasnferencia_salida');
    $selectOperacionesBizum = rg_select_informe($columnasInforme, ['total_operaciones_bizum'], 'total_operaciones_bizum');
    $selectLotesEmpeniosOro = rg_select_informe($columnasInforme, ['total_lotes_empenios_oro'], 'total_lotes_empenios_oro');
    $selectEurosEmpeniosOro = rg_select_informe($columnasInforme, ['total_euros_lotes_empenios_oro'], 'total_euros_lotes_empenios_oro');
    $selectGramosEmpeniosOro = rg_select_informe($columnasInforme, ['total_gramos_empenios_oro'], 'total_gramos_empenios_oro');
    $selectEmpenyosRetirados = rg_select_informe($columnasInforme, ['total_empenyos_retirados'], 'total_empenyos_retirados');
    $selectEurosEmpenyosRetirados = rg_select_informe($columnasInforme, ['total_euros_empenyos_retirados'], 'total_euros_empenyos_retirados');
    $selectGramosEmpeniosRetirados = rg_select_informe($columnasInforme, ['total_gramos_empenios_retirados'], 'total_gramos_empenios_retirados');
    $selectContratosIntervenidos = rg_select_informe($columnasInforme, ['total_contratos_intervenidos'], 'total_contratos_intervenidos');
    $selectEurosContratosIntervenidos = rg_select_informe($columnasInforme, ['total_euros_contratos_intervenidos'], 'total_euros_contratos_intervenidos');
    $selectGramosContratosIntervenidos = rg_select_informe($columnasInforme, ['total_gramos_contratos_intervenidos'], 'total_gramos_contratos_intervenidos');

    $query_base = '
        FROM informe_diario AS av
        LEFT JOIN sucursal ON av.sucursal_informe = sucursal.id_sucursal
        LEFT JOIN empresas AS empresa ON av.empresa_informe_id = empresa.id_empresa
        LEFT JOIN (
            SELECT
                sucursal,
                DATE(fecha_compra) AS fecha_compra_dia,
                MIN(id_lote) AS primer_lote,
                MAX(id_lote) AS ultimo_lote
            FROM lotes_joyeria
            GROUP BY sucursal, DATE(fecha_compra)
        ) AS rango_lotes
            ON rango_lotes.sucursal = av.sucursal_informe
           AND rango_lotes.fecha_compra_dia = DATE(av.fecha_informe)
        WHERE 1=1
    ';

    $params = [];
    $types = '';

    if ($filtro_empresa !== '') {
        $query_base .= ' AND av.empresa_informe_id = ?';
        $params[] = (int) $filtro_empresa;
        $types .= 'i';
    }

    if ($filtro_sucursal !== '') {
        $query_base .= ' AND av.sucursal_informe = ?';
        $params[] = (int) $filtro_sucursal;
        $types .= 'i';
    }

    if ($filtro_periodo === 'dia') {
        $query_base .= ' AND DATE(av.fecha_informe) = CURDATE()';
    } elseif ($filtro_periodo === 'mes') {
        $query_base .= ' AND MONTH(av.fecha_informe) = MONTH(CURDATE()) AND YEAR(av.fecha_informe) = YEAR(CURDATE())';
    } elseif ($filtro_periodo === 'todos') {
        // Sin filtro de fecha
    } else {
        if ($filtro_fecha_desde !== '' && $filtro_fecha_hasta !== '') {
            $query_base .= ' AND DATE(av.fecha_informe) BETWEEN ? AND ?';
            $params[] = $filtro_fecha_desde;
            $params[] = $filtro_fecha_hasta;
            $types .= 'ss';
        } elseif ($filtro_fecha_desde !== '') {
            $query_base .= ' AND DATE(av.fecha_informe) >= ?';
            $params[] = $filtro_fecha_desde;
            $types .= 's';
        } elseif ($filtro_fecha_hasta !== '') {
            $query_base .= ' AND DATE(av.fecha_informe) <= ?';
            $params[] = $filtro_fecha_hasta;
            $types .= 's';
        }
    }

    if ($search !== '') {
        $query_base .= " AND (
            sucursal.nombre_sucursal LIKE ?
            OR CAST(sucursal.id_sucursal AS CHAR) LIKE ?
            OR empresa.nombre_empresa LIKE ?
            OR DATE_FORMAT(av.fecha_informe, '%d-%m-%Y') LIKE ?
            OR DATE_FORMAT(av.fecha_informe, '%Y-%m-%d') LIKE ?
        )";
        $search_param = '%' . $search . '%';
        $params[] = $search_param;
        $params[] = $search_param;
        $params[] = $search_param;
        $params[] = $search_param;
        $params[] = $search_param;
        $types .= 'sssss';
    }

    $count_query = 'SELECT COUNT(*) AS total ' . $query_base;
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
            av.semana_numero,
            av.year_rel,
            av.fecha_informe,
            av.total_euros_lotes_compra_oro,
            av.total_lotes_compra_oro,
            av.total_gramos_compra_oro,
            av.media_pagado_oro_compra,
            av.total_euros_lotes_compra_plata,
            av.total_gramos_compra_plata,
            av.total_lotes_compra_plata,
            av.media_pagado_plata_compra,
            {$selectLotesEmpeniosOro},
            {$selectEurosEmpeniosOro},
            {$selectGramosEmpeniosOro},
            av.total_euros_renovaciones,
            {$selectEmpenyosRetirados},
            {$selectEurosEmpenyosRetirados},
            {$selectGramosEmpeniosRetirados},
            {$selectContratosIntervenidos},
            {$selectEurosContratosIntervenidos},
            {$selectGramosContratosIntervenidos},
            {$selectTotalVentas},
            {$selectEurosVentas},
            {$selectBeneficioVentas},
            {$selectTotalVentasPlazo},
            {$selectEurosVentasPlazo},
            av.total_gastos,
            {$selectCajaEntradas},
            {$selectCajaSalidas},
            {$selectOperacionesTarjeta},
            {$selectTransferenciaEntrada},
            {$selectTransferenciaSalida},
            {$selectOperacionesBizum},
            {$selectYulinfo},
            av.empresa_informe_id,
            sucursal.id_sucursal,
            sucursal.nombre_sucursal,
            empresa.nombre_empresa,
            rango_lotes.primer_lote,
            rango_lotes.ultimo_lote
        " . $query_base . "
        ORDER BY {$orderBy} {$orderDirection}, av.fecha_informe ASC, empresa.nombre_empresa ASC, sucursal.nombre_sucursal ASC
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

        $beneficioVentas = (float) ($row['total_beneficio_ventas'] ?? 0);
        $baseImponibleVentas = $beneficioVentas / 1.21;
        $cuotaIvaVentas = $baseImponibleVentas * 0.21;
        $beneficioFinalVentas = $beneficioVentas - $cuotaIvaVentas;

        $primerLote = isset($row['primer_lote']) ? (int) $row['primer_lote'] : 0;
        $ultimoLote = isset($row['ultimo_lote']) ? (int) $row['ultimo_lote'] : 0;
        if ($primerLote > 0 && $ultimoLote > 0) {
            $rangoLotes = $primerLote . ' - ' . $ultimoLote;
        } elseif ($primerLote > 0) {
            $rangoLotes = (string) $primerLote;
        } else {
            $rangoLotes = '-';
        }

        $data[] = [
            formatear_fecha_reporte($row['fecha_informe']),
            htmlspecialchars($nombreSucursal),
            $rangoLotes,
            formatear_euro_reporte($row['total_euros_lotes_compra_oro']),
            (int) $row['total_lotes_compra_oro'],
            formatear_gramos_reporte($row['total_gramos_compra_oro']),
            formatear_euro_reporte($row['media_pagado_oro_compra']),
            formatear_euro_reporte($row['total_euros_lotes_compra_plata']),
            formatear_gramos_reporte($row['total_gramos_compra_plata']),
            (int) $row['total_lotes_compra_plata'],
            formatear_euro_reporte($row['media_pagado_plata_compra']),
            (int) $row['total_lotes_empenios_oro'],
            formatear_euro_reporte($row['total_euros_lotes_empenios_oro']),
            formatear_gramos_reporte($row['total_gramos_empenios_oro']),
            formatear_euro_reporte($row['total_euros_renovaciones']),
            (int) $row['total_empenyos_retirados'],
            formatear_euro_reporte($row['total_euros_empenyos_retirados']),
            formatear_gramos_reporte($row['total_gramos_empenios_retirados']),
            (int) $row['total_contratos_intervenidos'],
            formatear_euro_reporte($row['total_euros_contratos_intervenidos']),
            formatear_gramos_reporte($row['total_gramos_contratos_intervenidos']),
            (int) $row['total_ventas'],
            formatear_euro_reporte($row['total_euros_ventas']),
            formatear_euro_reporte($beneficioFinalVentas),
            (int) $row['total_ventas_plazo'],
            formatear_euro_reporte($row['total_ventas_plazo_euro']),
            formatear_euro_reporte($total_gastos),
            formatear_euro_reporte($row['total_caja_entradas']),
            formatear_euro_reporte($row['total_caja_salidas']),
            formatear_euro_reporte($row['total_operaciones_tarjeta']),
            formatear_euro_reporte($row['total_operaciones_trasnferencia_entrada']),
            formatear_euro_reporte($row['total_operaciones_trasnferencia_salida']),
            formatear_euro_reporte($row['total_operaciones_bizum']),
            (int) $row['id_informe'],
            [
                'year_informe' => (int) $row['year_rel'],
                'semana_numero' => (int) $row['semana_numero'],
                'fecha_informe' => $row['fecha_informe'],
                'id_sucursal' => (int) $row['id_sucursal'],
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

    if ($is_export_all) {
        $export_data = [];
        foreach ($data as $row) {
            $export_data[] = array_slice($row, 0, 33);
        }

        echo json_encode([
            'success' => true,
            'total' => count($export_data),
            'data' => $export_data
        ]);
        exit;
    }

    echo json_encode([
        'draw' => $draw,
        'recordsTotal' => $total_records,
        'recordsFiltered' => $total_records,
        'data' => $data
    ]);
} catch (Exception $e) {
    http_response_code(500);
    if ($is_export_all) {
        echo json_encode([
            'success' => false,
            'error' => 'Error interno del servidor: ' . $e->getMessage(),
            'data' => []
        ]);
        exit;
    }
    echo json_encode([
        'error' => 'Error interno del servidor: ' . $e->getMessage(),
        'draw' => $draw,
        'recordsTotal' => 0,
        'recordsFiltered' => 0,
        'data' => []
    ]);
}
