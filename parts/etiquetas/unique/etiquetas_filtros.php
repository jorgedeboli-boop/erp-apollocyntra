<?php
/**
 * Filtros compartidos para listado de etiquetas pendientes.
 */

function etiquetas_mysqli_bind_params($stmt, $types, array $params)
{
    if ($types === '' || empty($params)) {
        return true;
    }
    $bind_names = array();
    $bind_names[] = $types;
    for ($i = 0; $i < count($params); $i++) {
        $bind_names[] = &$params[$i];
    }
    return call_user_func_array(array($stmt, 'bind_param'), $bind_names);
}

function etiquetas_ids_sucursales_excluidas($conexion)
{
    $ids = array(49);
    $query = "SELECT id_sucursal FROM sucursal WHERE estado_tienda = 'desabilitada'";
    $result = mysqli_query($conexion, $query);
    if ($result) {
        while ($row = mysqli_fetch_assoc($result)) {
            $ids[] = (int) $row['id_sucursal'];
        }
    }
    return array_values(array_unique($ids));
}

/**
 * @param mysqli $conexion
 * @param array $filtros sucursal, periodo, fecha_desde, fecha_hasta, search
 * @return array{where:string,params:array,types:string}
 */
function etiquetas_build_where($conexion, array $filtros)
{
    $whereConditions = array();
    $params = array();
    $types = '';

    $whereConditions[] = "(av.estado = 'noetiquetado_c' OR av.estado = 'noetiquetado_u')";

    $sucursal = isset($filtros['sucursal']) ? (int) $filtros['sucursal'] : 0;
    if ($sucursal > 0) {
        $whereConditions[] = 'av.id_sucursal_destino = ?';
        $params[] = $sucursal;
        $types .= 'i';
    } else {
        $excluidas = etiquetas_ids_sucursales_excluidas($conexion);
        if (!empty($excluidas)) {
            $placeholders = implode(',', array_fill(0, count($excluidas), '?'));
            $whereConditions[] = "av.id_sucursal_destino NOT IN ($placeholders)";
            foreach ($excluidas as $id) {
                $params[] = $id;
                $types .= 'i';
            }
        }
    }

    $periodo = isset($filtros['periodo']) ? trim((string) $filtros['periodo']) : '';
    $fechaDesde = isset($filtros['fecha_desde']) ? trim((string) $filtros['fecha_desde']) : '';
    $fechaHasta = isset($filtros['fecha_hasta']) ? trim((string) $filtros['fecha_hasta']) : '';

    if ($periodo === 'personalizado') {
        $periodo = 'fecha';
    }

    if ($periodo === 'dia') {
        $hoy = date('Y-m-d');
        $whereConditions[] = 'DATE(av.fecha_alta) = ?';
        $params[] = $hoy;
        $types .= 's';
    } elseif ($periodo === 'mes') {
        $whereConditions[] = 'YEAR(av.fecha_alta) = YEAR(CURRENT_DATE()) AND MONTH(av.fecha_alta) = MONTH(CURRENT_DATE())';
    } elseif ($periodo === 'fecha' || ($fechaDesde !== '' || $fechaHasta !== '')) {
        if ($fechaDesde !== '' && $fechaHasta !== '') {
            $whereConditions[] = 'DATE(av.fecha_alta) BETWEEN ? AND ?';
            $params[] = $fechaDesde;
            $params[] = $fechaHasta;
            $types .= 'ss';
        } elseif ($fechaDesde !== '') {
            $whereConditions[] = 'DATE(av.fecha_alta) >= ?';
            $params[] = $fechaDesde;
            $types .= 's';
        } elseif ($fechaHasta !== '') {
            $whereConditions[] = 'DATE(av.fecha_alta) <= ?';
            $params[] = $fechaHasta;
            $types .= 's';
        }
    }

    $search = isset($filtros['search']) ? trim((string) $filtros['search']) : '';
    if ($search !== '') {
        $whereConditions[] = '(
            av.id LIKE ? OR
            av.descripcion LIKE ? OR
            av.origen_articulo LIKE ? OR
            s.nombre_sucursal LIKE ? OR
            u.nombre_usuario LIKE ?
        )';
        $searchParam = '%' . $search . '%';
        $params[] = $searchParam;
        $params[] = $searchParam;
        $params[] = $searchParam;
        $params[] = $searchParam;
        $params[] = $searchParam;
        $types .= 'sssss';
    }

    return array(
        'where' => 'WHERE ' . implode(' AND ', $whereConditions),
        'params' => $params,
        'types' => $types,
    );
}

function etiquetas_format_origen($origen)
{
    $origen = strtolower(trim((string) $origen));
    if ($origen === 'central') {
        return '<span class="badge bg-label-primary">Central</span>';
    }
    if ($origen === 'sucursal') {
        return '<span class="badge bg-label-info">Sucursal</span>';
    }
    return htmlspecialchars($origen !== '' ? $origen : '---');
}

function etiquetas_format_tipo($tipo)
{
    $tipoLower = strtolower(trim((string) $tipo));
    if (strpos($tipoLower, 'oro') !== false) {
        return '<span class="badge bg-label-warning rounded-pill">Oro</span>';
    }
    if (strpos($tipoLower, 'plata') !== false) {
        return '<span class="badge bg-label-secondary rounded-pill">Plata</span>';
    }
    return '<span class="badge bg-label-info rounded-pill">' . htmlspecialchars($tipo !== '' ? $tipo : '---') . '</span>';
}

function etiquetas_format_fecha($fecha)
{
    if (empty($fecha) || $fecha === '0000-00-00' || $fecha === '0000-00-00 00:00:00') {
        return '------';
    }
    return date('d/m/Y', strtotime($fecha));
}

function etiquetas_boton_imprimir($idArticulo)
{
    $id = (int) $idArticulo;
    if ($id <= 0) {
        return '-';
    }
    $href = 'Impresiones/Articulos/etiquetas_articulos.php?id_articulo=' . $id;
    return '<a class="btn btn-sm btn-success waves-effect etiqueta-print-link" target="_blank" data-sku="' . $id . '" href="' . htmlspecialchars($href, ENT_QUOTES, 'UTF-8') . '">'
        . '<i class="icon-base ri ri-printer-line me-1"></i>Imprimir</a>';
}
