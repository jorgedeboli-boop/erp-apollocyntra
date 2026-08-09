<?php
require_once '../../../include/session.php';
require_once '../../../include/functions.php';

header('Content-Type: application/json; charset=utf-8');

if (!isset($_SESSION['usuario_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'No autorizado']);
    exit;
}

$filtro_empresa = isset($_POST['filtro_empresa']) ? trim($_POST['filtro_empresa']) : '';
$filtro_sucursal = isset($_POST['filtro_sucursal']) ? trim($_POST['filtro_sucursal']) : '';
$filtro_fecha_desde = isset($_POST['filtro_fecha_desde']) ? trim($_POST['filtro_fecha_desde']) : '';
$filtro_fecha_hasta = isset($_POST['filtro_fecha_hasta']) ? trim($_POST['filtro_fecha_hasta']) : '';
$filtro_periodo = isset($_POST['filtro_periodo']) ? trim($_POST['filtro_periodo']) : 'dia';
$search = isset($_POST['search']) ? trim($_POST['search']) : '';

function rg_stats_columnas_informe(mysqli $conexion)
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

function rg_stats_expr(array $columnasDb, array $preferidos)
{
    foreach ($preferidos as $columna) {
        if (in_array($columna, $columnasDb, true)) {
            return 'av.' . $columna;
        }
    }
    return '0';
}

function rg_stats_sum(array $columnasDb, array $preferidos, $alias)
{
    $expr = rg_stats_expr($columnasDb, $preferidos);
    if ($expr === '0') {
        return '0 AS ' . $alias;
    }
    return 'COALESCE(SUM(' . $expr . '), 0) AS ' . $alias;
}

try {
    $conexion = conectar_bd();
    if (!$conexion) {
        throw new Exception('Error de conexión a la base de datos');
    }

    $columnas = rg_stats_columnas_informe($conexion);

    $query_base = '
        FROM informe_diario AS av
        LEFT JOIN sucursal ON av.sucursal_informe = sucursal.id_sucursal
        LEFT JOIN empresas AS empresa ON av.empresa_informe_id = empresa.id_empresa
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

    $sql = 'SELECT
            ' . rg_stats_sum($columnas, ['total_lotes_compra_oro'], 'total_lotes_compra_oro') . ',
            ' . rg_stats_sum($columnas, ['total_gramos_compra_oro'], 'total_gramos_compra_oro') . ',
            ' . rg_stats_sum($columnas, ['total_euros_lotes_compra_oro'], 'total_euros_lotes_compra_oro') . ',
            ' . rg_stats_sum($columnas, ['total_lotes_compra_plata'], 'total_lotes_compra_plata') . ',
            ' . rg_stats_sum($columnas, ['total_gramos_compra_plata'], 'total_gramos_compra_plata') . ',
            ' . rg_stats_sum($columnas, ['total_euros_lotes_compra_plata'], 'total_euros_lotes_compra_plata') . ',
            ' . rg_stats_sum($columnas, ['total_lotes_empenios_oro'], 'total_lotes_empenios_oro') . ',
            ' . rg_stats_sum($columnas, ['total_gramos_empenios_oro'], 'total_gramos_empenios_oro') . ',
            ' . rg_stats_sum($columnas, ['total_euros_lotes_empenios_oro'], 'total_euros_lotes_empenios_oro') . ',
            ' . rg_stats_sum($columnas, ['total_ventas'], 'total_ventas') . ',
            ' . rg_stats_sum($columnas, ['total_euros_ventas'], 'total_euros_ventas') . ',
            ' . rg_stats_sum($columnas, ['total_coste_art_venta'], 'total_coste_art_venta') . ',
            ' . rg_stats_sum($columnas, ['total_beneficio_ventas'], 'total_beneficio_ventas') . ',
            ' . rg_stats_sum($columnas, ['total_ventas_plazo'], 'total_ventas_plazo') . ',
            ' . rg_stats_sum($columnas, ['total_ventas_plazo_euro'], 'total_ventas_plazo_euro') . ',
            ' . rg_stats_sum($columnas, ['total_euros_renovaciones'], 'total_euros_renovaciones') . ',
            ' . rg_stats_sum($columnas, ['total_gastos'], 'total_gastos') . ',
            ' . rg_stats_sum($columnas, ['total_contratos_intervenidos'], 'total_contratos_intervenidos') . ',
            ' . rg_stats_sum($columnas, ['total_euros_contratos_intervenidos'], 'total_euros_contratos_intervenidos') . ',
            ' . rg_stats_sum($columnas, ['total_gramos_contratos_intervenidos'], 'total_gramos_contratos_intervenidos') . ',
            ' . rg_stats_sum($columnas, ['total_caja_entradas'], 'total_caja_entradas') . ',
            ' . rg_stats_sum($columnas, ['total_caja_salidas'], 'total_caja_salidas') . ',
            ' . rg_stats_sum($columnas, ['total_operaciones_tarjeta'], 'total_operaciones_tarjeta') . ',
            ' . rg_stats_sum($columnas, ['total_operaciones_trasnferencia_entrada'], 'total_operaciones_trasnferencia_entrada') . ',
            ' . rg_stats_sum($columnas, ['total_operaciones_trasnferencia_salida'], 'total_operaciones_trasnferencia_salida') . ',
            ' . rg_stats_sum($columnas, ['total_operaciones_bizum'], 'total_operaciones_bizum') . ',
            ' . rg_stats_sum($columnas, ['ventas_contado'], 'ventas_contado') . ',
            ' . rg_stats_sum($columnas, ['ventas_contado_euros'], 'ventas_contado_euros') . ',
            ' . rg_stats_sum($columnas, ['ventas_transferencia'], 'ventas_transferencia') . ',
            ' . rg_stats_sum($columnas, ['ventas_transferencia_euros'], 'ventas_transferencia_euros') . ',
            ' . rg_stats_sum($columnas, ['ventas_tarjeta'], 'ventas_tarjeta') . ',
            ' . rg_stats_sum($columnas, ['ventas_tarjeta_euros'], 'ventas_tarjeta_euros') . ',
            ' . rg_stats_sum($columnas, ['ventas_bizum'], 'ventas_bizum') . ',
            ' . rg_stats_sum($columnas, ['ventas_bizum_euros'], 'ventas_bizum_euros') . '
        ' . $query_base;

    $stmt = mysqli_prepare($conexion, $sql);
    if (!$stmt) {
        throw new Exception('Error al preparar consulta: ' . mysqli_error($conexion));
    }

    if ($types !== '') {
        mysqli_stmt_bind_param($stmt, $types, ...$params);
    }

    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $row = $result ? mysqli_fetch_assoc($result) : null;
    mysqli_stmt_close($stmt);
    mysqli_close($conexion);

    if (!$row) {
        $row = [];
    }

    $oro_lotes = (int) ($row['total_lotes_compra_oro'] ?? 0);
    $oro_gramos = (float) ($row['total_gramos_compra_oro'] ?? 0);
    $oro_euros = (float) ($row['total_euros_lotes_compra_oro'] ?? 0);

    $plata_lotes = (int) ($row['total_lotes_compra_plata'] ?? 0);
    $plata_gramos = (float) ($row['total_gramos_compra_plata'] ?? 0);
    $plata_euros = (float) ($row['total_euros_lotes_compra_plata'] ?? 0);

    $empenos_lotes = (int) ($row['total_lotes_empenios_oro'] ?? 0);
    $empenos_gramos = (float) ($row['total_gramos_empenios_oro'] ?? 0);
    $empenos_euros = (float) ($row['total_euros_lotes_empenios_oro'] ?? 0);

    $compras_lotes = $oro_lotes + $plata_lotes;
    $compras_gramos = $oro_gramos + $plata_gramos;
    $compras_euros = $oro_euros + $plata_euros;

    $total_lotes = $compras_lotes + $empenos_lotes;
    $total_gramos = $compras_gramos + $empenos_gramos;
    $total_euros = $compras_euros + $empenos_euros;

    $media = static function ($euros, $gramos) {
        return $gramos > 0 ? ($euros / $gramos) : 0.0;
    };

    $mediaVenta = static function ($euros, $total) {
        return $total > 0 ? ($euros / $total) : 0.0;
    };

    $ventas_contado = (int) ($row['ventas_contado'] ?? 0);
    $ventas_contado_euros = (float) ($row['ventas_contado_euros'] ?? 0);
    $ventas_transferencia = (int) ($row['ventas_transferencia'] ?? 0);
    $ventas_transferencia_euros = (float) ($row['ventas_transferencia_euros'] ?? 0);
    $ventas_tarjeta = (int) ($row['ventas_tarjeta'] ?? 0);
    $ventas_tarjeta_euros = (float) ($row['ventas_tarjeta_euros'] ?? 0);
    $ventas_bizum = (int) ($row['ventas_bizum'] ?? 0);
    $ventas_bizum_euros = (float) ($row['ventas_bizum_euros'] ?? 0);

    $total_euros_renovaciones = (float) ($row['total_euros_renovaciones'] ?? 0);
    // Total con IVA incluido: IVA = 21/121, Beneficio = total sin IVA
    $iva_renovaciones = $total_euros_renovaciones * 21 / 121;
    $beneficio_renovaciones = $total_euros_renovaciones - $iva_renovaciones;

    $total_beneficio_ventas = (float) ($row['total_beneficio_ventas'] ?? 0);
    $base_imponible_ventas = $total_beneficio_ventas / 1.21;
    $cuota_iva_ventas = $base_imponible_ventas * 0.21;
    $beneficio_final_ventas = $total_beneficio_ventas - $cuota_iva_ventas;

    echo json_encode([
        'success' => true,
        'totales' => [
            'total_lotes' => $total_lotes,
            'total_lotes_gramos' => $total_gramos,
            'total_lotes_euros' => $total_euros,
            'total_compras' => $compras_lotes,
            'total_compras_gramos' => $compras_gramos,
            'total_compras_euros' => $compras_euros,
            'total_compras_media' => $media($compras_euros, $compras_gramos),
            'total_empenos' => $empenos_lotes,
            'total_empenos_gramos' => $empenos_gramos,
            'total_empenos_euros' => $empenos_euros,
            'total_empenos_media' => $media($empenos_euros, $empenos_gramos),
            'total_oro' => $oro_lotes,
            'total_oro_gramos' => $oro_gramos,
            'total_oro_euros' => $oro_euros,
            'total_oro_media' => $media($oro_euros, $oro_gramos),
            'total_plata' => $plata_lotes,
            'total_plata_gramos' => $plata_gramos,
            'total_plata_euros' => $plata_euros,
            'total_plata_media' => $media($plata_euros, $plata_gramos),
            'total_ventas' => (int) ($row['total_ventas'] ?? 0),
            'total_euros_ventas' => (float) ($row['total_euros_ventas'] ?? 0),
            'total_coste_art_venta' => (float) ($row['total_coste_art_venta'] ?? 0),
            'total_beneficio_ventas' => $total_beneficio_ventas,
            'base_imponible_ventas' => $base_imponible_ventas,
            'cuota_iva_ventas' => $cuota_iva_ventas,
            'beneficio_final_ventas' => $beneficio_final_ventas,
            'total_ventas_plazo' => (int) ($row['total_ventas_plazo'] ?? 0),
            'total_ventas_plazo_euro' => (float) ($row['total_ventas_plazo_euro'] ?? 0),
            'total_euros_renovaciones' => $total_euros_renovaciones,
            'iva_renovaciones' => $iva_renovaciones,
            'beneficio_renovaciones' => $beneficio_renovaciones,
            'total_gastos' => (float) ($row['total_gastos'] ?? 0),
            'total_contratos_intervenidos' => (int) ($row['total_contratos_intervenidos'] ?? 0),
            'total_euros_contratos_intervenidos' => (float) ($row['total_euros_contratos_intervenidos'] ?? 0),
            'total_gramos_contratos_intervenidos' => (float) ($row['total_gramos_contratos_intervenidos'] ?? 0),
            'total_caja_entradas' => (float) ($row['total_caja_entradas'] ?? 0),
            'total_caja_salidas' => (float) ($row['total_caja_salidas'] ?? 0),
            'total_operaciones_tarjeta' => (float) ($row['total_operaciones_tarjeta'] ?? 0),
            'total_operaciones_trasnferencia_entrada' => (float) ($row['total_operaciones_trasnferencia_entrada'] ?? 0),
            'total_operaciones_trasnferencia_salida' => (float) ($row['total_operaciones_trasnferencia_salida'] ?? 0),
            'total_operaciones_bizum' => (float) ($row['total_operaciones_bizum'] ?? 0),
            'ventas_contado' => $ventas_contado,
            'ventas_contado_euros' => $ventas_contado_euros,
            'ventas_contado_media' => $mediaVenta($ventas_contado_euros, $ventas_contado),
            'ventas_transferencia' => $ventas_transferencia,
            'ventas_transferencia_euros' => $ventas_transferencia_euros,
            'ventas_transferencia_media' => $mediaVenta($ventas_transferencia_euros, $ventas_transferencia),
            'ventas_tarjeta' => $ventas_tarjeta,
            'ventas_tarjeta_euros' => $ventas_tarjeta_euros,
            'ventas_tarjeta_media' => $mediaVenta($ventas_tarjeta_euros, $ventas_tarjeta),
            'ventas_bizum' => $ventas_bizum,
            'ventas_bizum_euros' => $ventas_bizum_euros,
            'ventas_bizum_media' => $mediaVenta($ventas_bizum_euros, $ventas_bizum),
        ]
    ]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
