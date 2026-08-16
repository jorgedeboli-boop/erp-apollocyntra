<?php
/**
 * Exportar TODOS los artículos vendidos (con filtros)
 */

require_once '../../../include/session.php';
require_once '../../../include/functions.php';

header('Content-Type: application/json');

try {
    $conexion = conectar_bd();

    $searchValue = isset($_POST['search']) ? trim($_POST['search']) : '';
    $filtro_fecha_desde = isset($_POST['filtro_fecha_desde']) ? trim($_POST['filtro_fecha_desde']) : '';
    $filtro_fecha_hasta = isset($_POST['filtro_fecha_hasta']) ? trim($_POST['filtro_fecha_hasta']) : '';
    $filtro_periodo = isset($_POST['filtro_periodo']) ? trim($_POST['filtro_periodo']) : '';

    $whereConditions = [];
    $params = [];
    $types = '';

    $whereConditions[] = "(av.estado = 'vendido' OR av.estado = 'vendido_web')";
    $whereConditions[] = "av.fecha_vendido BETWEEN DATE_SUB(NOW(), INTERVAL 6 YEAR) AND NOW()";

    if ($filtro_periodo === 'dia') {
        $hoy = date('Y-m-d');
        $whereConditions[] = "DATE(av.fecha_vendido) = ?";
        $params[] = $hoy;
        $types .= 's';
    } elseif ($filtro_periodo === 'mes') {
        $whereConditions[] = "MONTH(av.fecha_vendido) = MONTH(CURRENT_DATE()) AND YEAR(av.fecha_vendido) = YEAR(CURRENT_DATE())";
    } elseif ($filtro_periodo === 'fecha') {
        if (!empty($filtro_fecha_desde) && !empty($filtro_fecha_hasta)) {
            $whereConditions[] = "DATE(av.fecha_vendido) BETWEEN ? AND ?";
            $params[] = $filtro_fecha_desde;
            $params[] = $filtro_fecha_hasta;
            $types .= 'ss';
        } elseif (!empty($filtro_fecha_desde)) {
            $whereConditions[] = "DATE(av.fecha_vendido) >= ?";
            $params[] = $filtro_fecha_desde;
            $types .= 's';
        } elseif (!empty($filtro_fecha_hasta)) {
            $whereConditions[] = "DATE(av.fecha_vendido) <= ?";
            $params[] = $filtro_fecha_hasta;
            $types .= 's';
        }
    }

    if (!empty($searchValue)) {
        $whereConditions[] = "(
            av.id LIKE ? OR
            av.descripcion LIKE ? OR
            av.last_id_venta LIKE ? OR
            av.id_venta_sucursal LIKE ?
        )";
        $searchParam = '%' . $searchValue . '%';
        $params[] = $searchParam;
        $params[] = $searchParam;
        $params[] = $searchParam;
        $params[] = $searchParam;
        $types .= 'ssss';
    }

    $whereClause = 'WHERE ' . implode(' AND ', $whereConditions);

    $query = "
        SELECT
            av.id as id_articulo,
            av.descripcion,
            av.fecha_vendido,
            av.last_id_venta,
            av.id_venta_sucursal,
            av.precio,
            av.peso,
            av.estado,
            av.articulo_web
        FROM articulos_venta av
        $whereClause
        ORDER BY av.id DESC
    ";

    if (!empty($types)) {
        $stmt = mysqli_prepare($conexion, $query);
        mysqli_stmt_bind_param($stmt, $types, ...$params);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
    } else {
        $result = mysqli_query($conexion, $query);
    }

    $data = [];
    while ($row = mysqli_fetch_assoc($result)) {
        $ventaNumero = $row['id_venta_sucursal'] ?: $row['last_id_venta'];
        $esWeb = ($row['estado'] === 'vendido_web') || (!empty($row['articulo_web']) && $row['articulo_web'] !== '0');
        $webBadge = $esWeb ? 'Sí' : 'No';

        $data[] = [
            (string)$row['id_articulo'],
            (string)($row['descripcion'] ?? ''),
            (!empty($row['fecha_vendido']) && $row['fecha_vendido'] !== '0000-00-00' && $row['fecha_vendido'] !== '0000-00-00 00:00:00')
                ? date('d/m/Y', strtotime($row['fecha_vendido']))
                : '-',
            (string)($ventaNumero ?: '-'),
            number_format((float)($row['precio'] ?? 0), 0, ',', '.') . ' €',
            number_format((float)($row['peso'] ?? 0), 2, ',', '.') . ' g',
            $webBadge
        ];
    }

    if (isset($stmt) && $stmt) {
        mysqli_stmt_close($stmt);
    }
    mysqli_close($conexion);

    echo json_encode([
        'success' => true,
        'data' => $data
    ]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}

