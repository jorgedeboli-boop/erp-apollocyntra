<?php
require_once '../../../include/session.php';
require_once '../../../include/functions.php';

if (!isset($_SESSION['usuario_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'No autorizado']);
    exit;
}

header('Content-Type: application/json; charset=utf-8');

$search = isset($_POST['search']) ? trim($_POST['search']) : '';
$filtro_sucursal = isset($_POST['filtro_sucursal']) ? trim($_POST['filtro_sucursal']) : '';
$filtro_fecha_desde = isset($_POST['filtro_fecha_desde']) ? trim($_POST['filtro_fecha_desde']) : '';
$filtro_fecha_hasta = isset($_POST['filtro_fecha_hasta']) ? trim($_POST['filtro_fecha_hasta']) : '';
$filtro_periodo = isset($_POST['filtro_periodo']) ? trim($_POST['filtro_periodo']) : 'todos';

if ($filtro_periodo === 'personalizado') {
    $filtro_periodo = 'fecha';
}

function formatear_euro_reporte_diario_export($valor)
{
    return number_format((float) $valor, 2, ',', '.') . ' €';
}

function formatear_gramos_reporte_diario_export($valor)
{
    return number_format((float) $valor, 2, ',', '.') . ' gr';
}

function formatear_fecha_reporte_diario_export($fecha)
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

    $data_query = "
        SELECT
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
            sucursal.nombre_sucursal
        " . $query_base . "
        ORDER BY av.fecha_informe DESC
    ";

    if ($types !== '') {
        $stmt = mysqli_prepare($conexion, $data_query);
        if (!$stmt) {
            throw new Exception('Error al preparar consulta de exportación');
        }
        mysqli_stmt_bind_param($stmt, $types, ...$params);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
    } else {
        $result = mysqli_query($conexion, $data_query);
        $stmt = null;
    }

    if (!$result) {
        throw new Exception('Error al obtener datos para exportar');
    }

    $data = [];
    while ($row = mysqli_fetch_assoc($result)) {
        $data[] = [
            formatear_fecha_reporte_diario_export($row['fecha_informe']),
            formatear_euro_reporte_diario_export($row['total_euros_lotes_compra_oro']),
            formatear_gramos_reporte_diario_export($row['total_gramos_compra_oro']),
            formatear_euro_reporte_diario_export($row['total_euros_lotes_compra_plata']),
            formatear_gramos_reporte_diario_export($row['total_gramos_compra_plata']),
            formatear_euro_reporte_diario_export($row['total_euros_lotes_empenios']),
            formatear_gramos_reporte_diario_export($row['total_gramos_empenios_oro']),
            formatear_euro_reporte_diario_export($row['total_euros_renovaciones']),
            formatear_euro_reporte_diario_export($row['total_euros_ventas']),
            formatear_euro_reporte_diario_export($row['total_gastos']),
            formatear_euro_reporte_diario_export($row['stock_valorizado_eruo']),
            $row['nombre_sucursal'] ? $row['nombre_sucursal'] : 'Sin sucursal'
        ];
    }

    if ($stmt) {
        mysqli_stmt_close($stmt);
    }
    mysqli_close($conexion);

    echo json_encode([
        'success' => true,
        'data' => $data,
        'total' => count($data)
    ]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
