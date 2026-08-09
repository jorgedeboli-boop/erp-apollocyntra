<?php
/**
 * Server-side processing para DataTable de artículos vendidos
 */

require_once '../../../include/session.php';
require_once '../../../include/functions.php';

header('Content-Type: application/json');

try {
    $conexion = conectar_bd();

    /**
     * mysqli_stmt_bind_param requiere variables por referencia.
     * En PHP 7, usar ...$params con un array de valores puede fallar.
     */
    $mysqli_bind_params = function ($stmt, $types, array $params) {
        if ($types === '' || empty($params)) {
            return true;
        }
        $bind_names = array();
        $bind_names[] = $types;
        for ($i = 0; $i < count($params); $i++) {
            $bind_names[] = &$params[$i];
        }
        return call_user_func_array(array($stmt, 'bind_param'), $bind_names);
    };

    $draw = isset($_POST['draw']) ? (int)$_POST['draw'] : 1;
    $start = isset($_POST['start']) ? (int)$_POST['start'] : 0;
    $length = isset($_POST['length']) ? (int)$_POST['length'] : 10;
    $searchValue = isset($_POST['search']['value']) ? $_POST['search']['value'] : '';

    $filtro_sucursal = isset($_POST['filtro_sucursal']) ? trim($_POST['filtro_sucursal']) : '';
    $filtro_tipo = isset($_POST['filtro_tipo']) ? trim($_POST['filtro_tipo']) : '';
    $filtro_fecha_desde = isset($_POST['filtro_fecha_desde']) ? trim($_POST['filtro_fecha_desde']) : '';
    $filtro_fecha_hasta = isset($_POST['filtro_fecha_hasta']) ? trim($_POST['filtro_fecha_hasta']) : '';
    $filtro_periodo = isset($_POST['filtro_periodo']) ? trim($_POST['filtro_periodo']) : '';

    $whereConditions = [];
    $params = [];
    $types = '';

    // Solo vendidos / vendidos web
    $whereConditions[] = "(av.estado = 'vendido' OR av.estado = 'vendido_web')";

    // Normalizar periodo: flatpickr universal puede enviar "personalizado"
    if ($filtro_periodo === 'personalizado') {
        $filtro_periodo = 'fecha';
    }

    // Rango por defecto: últimos 6 años (solo cuando NO hay filtro de fecha explícito)
    if ($filtro_periodo === '' || $filtro_periodo === 'todos') {
        $whereConditions[] = "av.fecha_vendido BETWEEN DATE_SUB(NOW(), INTERVAL 6 YEAR) AND NOW()";
    }

    // Filtro sucursal destino
    if (!empty($filtro_sucursal)) {
        $whereConditions[] = "av.id_sucursal_destino = ?";
        $params[] = $filtro_sucursal;
        $types .= 'i';
    }

    // Filtro tipo (oro/plata) sobre av.tipo
    if (!empty($filtro_tipo)) {
        $tipo_norm = strtolower($filtro_tipo);
        if ($tipo_norm === 'oro' || $tipo_norm === 'plata') {
            $whereConditions[] = "LOWER(av.tipo) LIKE ?";
            $params[] = '%' . $tipo_norm . '%';
            $types .= 's';
        }
    }

    // Filtro fecha (SIEMPRE sobre fecha_vendido)
    if ($filtro_periodo === 'dia') {
        $hoy = date('Y-m-d');
        $whereConditions[] = "DATE(av.fecha_vendido) = ?";
        $params[] = $hoy;
        $types .= 's';
    } elseif ($filtro_periodo === 'mes') {
        $whereConditions[] = "MONTH(av.fecha_vendido) = MONTH(CURRENT_DATE()) AND YEAR(av.fecha_vendido) = YEAR(CURRENT_DATE())";
    } elseif ($filtro_periodo === 'fecha' || (!empty($filtro_fecha_desde) || !empty($filtro_fecha_hasta))) {
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

    // Búsqueda global
    if (!empty($searchValue)) {
        $whereConditions[] = "(
            av.id LIKE ? OR
            av.descripcion LIKE ? OR
            av.nombre_sucursal_venta LIKE ? OR
            av.last_id_venta LIKE ? OR
            av.id_venta_sucursal LIKE ?
        )";
        $searchParam = '%' . $searchValue . '%';
        $params[] = $searchParam;
        $params[] = $searchParam;
        $params[] = $searchParam;
        $params[] = $searchParam;
        $params[] = $searchParam;
        $types .= 'sssss';
    }

    $whereClause = 'WHERE ' . implode(' AND ', $whereConditions);

    // Total (sin filtros extra, pero siempre vendidos y rango 6 años)
    $query_total = "
        SELECT COUNT(*) as total
        FROM articulos_venta av
        WHERE (av.estado = 'vendido' OR av.estado = 'vendido_web')
          AND av.fecha_vendido BETWEEN DATE_SUB(NOW(), INTERVAL 6 YEAR) AND NOW()
    ";
    $result_total = mysqli_query($conexion, $query_total);
    $row_total = mysqli_fetch_assoc($result_total);
    $recordsTotal = (int)($row_total['total'] ?? 0);

    // Filtrados
    $query_filtered = "
        SELECT COUNT(*) as total
        FROM articulos_venta av
        $whereClause
    ";

    if (!empty($types)) {
        $stmt_filtered = mysqli_prepare($conexion, $query_filtered);
        $mysqli_bind_params($stmt_filtered, $types, $params);
        mysqli_stmt_execute($stmt_filtered);
        $result_filtered = mysqli_stmt_get_result($stmt_filtered);
        $row_filtered = mysqli_fetch_assoc($result_filtered);
        $recordsFiltered = (int)($row_filtered['total'] ?? 0);
        mysqli_stmt_close($stmt_filtered);
    } else {
        $result_filtered = mysqli_query($conexion, $query_filtered);
        $row_filtered = mysqli_fetch_assoc($result_filtered);
        $recordsFiltered = (int)($row_filtered['total'] ?? 0);
    }

    $query = "
        SELECT
            av.id as id_articulo,
            av.id_articulo_sucursal,
            av.descripcion,
            av.id_sucursal_destino,
            av.nombre_sucursal_venta,
            av.fecha_enviado,
            av.fecha_en_venta,
            av.fecha_vendido,
            av.fecha_retirado,
            av.last_id_venta,
            av.id_venta_sucursal,
            av.precio,
            av.peso,
            av.estado,
            av.tipo,
            av.precio_coste,
            av.motivo_retirado,
            av.articulo_web
        FROM articulos_venta AS av
        $whereClause
        ORDER BY av.id DESC
        LIMIT ?, ?
    ";

    $allParams = array_merge($params, [$start, $length]);
    $allTypes = $types . 'ii';

    $stmt = mysqli_prepare($conexion, $query);
    $mysqli_bind_params($stmt, $allTypes, $allParams);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);

    $data = [];
    while ($row = mysqli_fetch_assoc($result)) {
        $idVentaSucursalRaw = isset($row['id_venta_sucursal']) ? trim((string) $row['id_venta_sucursal']) : '';
        $lastIdVentaRaw = isset($row['last_id_venta']) ? trim((string) $row['last_id_venta']) : '';
        $ventaNumero = ($idVentaSucursalRaw !== '' && $idVentaSucursalRaw !== '0')
            ? $idVentaSucursalRaw
            : (($lastIdVentaRaw !== '' && $lastIdVentaRaw !== '0') ? $lastIdVentaRaw : '');
        // Web: sin JOIN a ventas. Usamos el estado o el flag del propio artículo.
        $estado_av = isset($row['estado']) ? strtolower((string) $row['estado']) : '';
        $articulo_web_raw = isset($row['articulo_web']) ? trim((string) $row['articulo_web']) : '';
        $esWeb = ($estado_av === 'vendido_web') || ($articulo_web_raw !== '' && $articulo_web_raw !== '0' && strtolower($articulo_web_raw) !== 'false');
        $webBadge = $esWeb
            ? '<span class="badge bg-label-info">Sí</span>'
            : '<span class="badge bg-label-secondary">No</span>';

        $sku = (int) ($row['id_articulo'] ?? 0);
        $skuHtml = $sku > 0
            ? '<a href="articulo.php?id=' . $sku . '" target="_blank" rel="noopener">' . htmlspecialchars((string) $sku) . '</a>'
            : '-';

        $idVentaRel = (int) ($row['last_id_venta'] ?? 0); // id interno relacional
        // Mostrar siempre el número de venta de sucursal si existe; si no, mostrar el id relacional.
        $ventaNumeroText = ($idVentaSucursalRaw !== '' && $idVentaSucursalRaw !== '0')
            ? $idVentaSucursalRaw
            : (($idVentaRel > 0) ? (string) $idVentaRel : '-');
        $ventaNumeroHtml = ($idVentaRel > 0)
            ? '<a href="venta.php?id=' . $idVentaRel . '" target="_blank" rel="noopener">' . htmlspecialchars($ventaNumeroText) . '</a>'
            : htmlspecialchars($ventaNumeroText);

        $tipoTxt = isset($row['tipo']) ? (string) $row['tipo'] : '';
        $tipoLower = strtolower($tipoTxt);
        $tipoCls = 'info';
        if (strpos($tipoLower, 'oro') !== false) {
            $tipoCls = 'warning';
        } elseif (strpos($tipoLower, 'plata') !== false) {
            $tipoCls = 'secondary';
        }
        $tipoBadge = '<span class="badge bg-label-' . $tipoCls . ' rounded-pill">' . htmlspecialchars($tipoTxt) . '</span>';

        $data[] = [
            $skuHtml, // SKU (link)
            htmlspecialchars($row['descripcion'] ?? ''), // Descripción
            htmlspecialchars($row['nombre_sucursal_venta'] ?: '---'), // Sucursal
            (!empty($row['fecha_vendido']) && $row['fecha_vendido'] !== '0000-00-00' && $row['fecha_vendido'] !== '0000-00-00 00:00:00')
                ? date('d/m/Y', strtotime($row['fecha_vendido']))
                : '-', // Fecha de venta
            $ventaNumeroHtml, // Venta Nº (link)
            number_format((float)($row['precio'] ?? 0), 0, ',', '.') . ' €', // Precio
            number_format((float)($row['precio_coste'] ?? 0), 0, ',', '.') . ' €', // Coste
            number_format((float)($row['peso'] ?? 0), 2, ',', '.') . ' g', // Peso
            $tipoBadge, // Tipo
            $webBadge // Web
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
    echo json_encode(['error' => $e->getMessage()]);
}

