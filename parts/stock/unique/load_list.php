<?php
/**
 * Server-side processing para DataTable de stock (artículos en venta).
 */

require_once '../../../include/session.php';
require_once '../../../include/functions.php';

header('Content-Type: application/json');

try {
    $conexion = conectar_bd();

    $draw = isset($_POST['draw']) ? (int) $_POST['draw'] : 1;
    $start = isset($_POST['start']) ? (int) $_POST['start'] : 0;
    $length = isset($_POST['length']) ? (int) $_POST['length'] : 10;
    $searchValue = isset($_POST['search']['value']) ? $_POST['search']['value'] : '';

    $filtro_tipo = isset($_POST['filtro_tipo']) ? trim($_POST['filtro_tipo']) : '';
    $filtro_origen = isset($_POST['filtro_origen']) ? trim($_POST['filtro_origen']) : '';
    $filtro_fecha_desde = isset($_POST['filtro_fecha_desde']) ? trim($_POST['filtro_fecha_desde']) : '';
    $filtro_fecha_hasta = isset($_POST['filtro_fecha_hasta']) ? trim($_POST['filtro_fecha_hasta']) : '';
    $filtro_periodo = isset($_POST['filtro_periodo']) ? trim($_POST['filtro_periodo']) : '';

    $campoFecha = 'av.fecha_en_venta';

    $whereConditions = array("av.estado = 'enventa'");
    $params = array();
    $types = '';

    if (!empty($filtro_tipo)) {
        $whereConditions[] = 'av.tipo = ?';
        $params[] = $filtro_tipo;
        $types .= 's';
    }

    if (!empty($filtro_origen)) {
        $whereConditions[] = 'av.origen_articulo = ?';
        $params[] = $filtro_origen;
        $types .= 's';
    }

    if ($filtro_periodo === 'dia') {
        $hoy = date('Y-m-d');
        $whereConditions[] = "DATE({$campoFecha}) = ?";
        $params[] = $hoy;
        $types .= 's';
    } elseif ($filtro_periodo === 'mes') {
        $whereConditions[] = "MONTH({$campoFecha}) = MONTH(CURRENT_DATE()) AND YEAR({$campoFecha}) = YEAR(CURRENT_DATE())";
    } elseif ($filtro_periodo === 'fecha' || $filtro_periodo === 'personalizado') {
        if (!empty($filtro_fecha_desde) && !empty($filtro_fecha_hasta)) {
            $whereConditions[] = "DATE({$campoFecha}) BETWEEN ? AND ?";
            $params[] = $filtro_fecha_desde;
            $params[] = $filtro_fecha_hasta;
            $types .= 'ss';
        } elseif (!empty($filtro_fecha_desde)) {
            $whereConditions[] = "DATE({$campoFecha}) >= ?";
            $params[] = $filtro_fecha_desde;
            $types .= 's';
        } elseif (!empty($filtro_fecha_hasta)) {
            $whereConditions[] = "DATE({$campoFecha}) <= ?";
            $params[] = $filtro_fecha_hasta;
            $types .= 's';
        }
    }

    if (!empty($searchValue)) {
        $whereConditions[] = '(
            av.id LIKE ? OR
            av.descripcion LIKE ? OR
            av.estado LIKE ?
        )';
        $searchParam = '%' . $searchValue . '%';
        $params[] = $searchValue;
        $params[] = $searchParam;
        $params[] = $searchParam;
        $types .= 'sss';
    }

    $whereClause = 'WHERE ' . implode(' AND ', $whereConditions);

    $query_total = "SELECT COUNT(*) as total FROM articulos_venta av WHERE av.estado = 'enventa'";
    $result_total = mysqli_query($conexion, $query_total);
    $row_total = mysqli_fetch_assoc($result_total);
    $recordsTotal = $row_total['total'];

    $query_filtered = "
        SELECT COUNT(*) as total
        FROM articulos_venta av
        $whereClause
    ";

    if (!empty($types)) {
        $stmt_filtered = mysqli_prepare($conexion, $query_filtered);
        mysqli_stmt_bind_param($stmt_filtered, $types, ...$params);
        mysqli_stmt_execute($stmt_filtered);
        $result_filtered = mysqli_stmt_get_result($stmt_filtered);
        $row_filtered = mysqli_fetch_assoc($result_filtered);
        $recordsFiltered = $row_filtered['total'];
        mysqli_stmt_close($stmt_filtered);
    } else {
        $result_filtered = mysqli_query($conexion, $query_filtered);
        $row_filtered = mysqli_fetch_assoc($result_filtered);
        $recordsFiltered = $row_filtered['total'];
    }

    $query = "
        SELECT
            av.id as id_articulo,
            av.id_articulo_sucursal,
            av.descripcion,
            av.peso,
            av.precio,
            av.precio_coste,
            av.precio_gramo,
            av.tipo,
            av.estado,
            av.fecha_enviado,
            av.fecha_en_venta,
            av.fecha_vendido,
            av.fecha_retirado,
            av.origen_articulo,
            u.nombre_usuario
        FROM articulos_venta av
        LEFT JOIN usuarios u ON av.creado_por = u.id_usuario
        $whereClause
        ORDER BY av.id DESC
        LIMIT ?, ?
    ";

    $allParams = array_merge($params, [$start, $length]);
    $allTypes = $types . 'ii';

    $stmt = mysqli_prepare($conexion, $query);

    if (!empty($types)) {
        mysqli_stmt_bind_param($stmt, $allTypes, ...$allParams);
    } else {
        mysqli_stmt_bind_param($stmt, 'ii', $start, $length);
    }

    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);

    $data = [];
    while ($row = mysqli_fetch_assoc($result)) {
        $euro_gramo = $row['precio_gramo'];
        if (empty($euro_gramo) && $row['peso'] > 0) {
            $euro_gramo = $row['precio'] / $row['peso'];
        }

        if ($row['tipo'] === 'oro') {
            $tipo_badge = '<span class="badge bg-label-warning ">Oro</span>';
        } elseif ($row['tipo'] === 'plata') {
            $tipo_badge = '<span class="badge bg-label-secondary ">Plata</span>';
        } elseif ($row['tipo'] === 'acero') {
            $tipo_badge = '<span class="badge bg-label-dark ">Acero</span>';
        } else {
            $tipo_badge = '<span class="badge bg-label-secondary ">Otros</span>';
        }

        if ($row['origen_articulo'] === 'central') {
            $origen_badge = '<span class="badge bg-label-primary ">Central</span>';
        } else {
            $origen_badge = '<span class="badge bg-label-info ">Local</span>';
        }

        $data[] = [
            htmlspecialchars($row['id_articulo']),
            htmlspecialchars($row['descripcion']),
            number_format($row['peso'], 2, ',', '.') . ' g',
            number_format($row['precio'], 0, ',', '.') . ' €',
            number_format($row['precio_coste'], 0, ',', '.') . ' €',
            number_format($euro_gramo, 2, ',', '.') . ' €/g',
            $tipo_badge,
            !empty($row['fecha_enviado']) && $row['fecha_enviado'] !== '0000-00-00 00:00:00' ? date('d/m/Y', strtotime($row['fecha_enviado'])) : '-',
            !empty($row['fecha_en_venta']) && $row['fecha_en_venta'] !== '0000-00-00 00:00:00' ? date('d/m/Y', strtotime($row['fecha_en_venta'])) : '-',
            htmlspecialchars($row['nombre_usuario'] ?: '---'),
            $origen_badge,
            $row['id_articulo']
        ];
    }

    mysqli_stmt_close($stmt);
    mysqli_close($conexion);

    echo json_encode([
        'draw' => $draw,
        'recordsTotal' => $recordsTotal,
        'recordsFiltered' => $recordsFiltered,
        'data' => $data
    ]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'error' => $e->getMessage()
    ]);
}
