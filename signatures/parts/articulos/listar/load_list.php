<?php
/**
 * Server-side processing para DataTable de artículos venta
 */

require_once '../../../include/session.php';
require_once '../../../include/functions.php';

header('Content-Type: application/json');

try {
    // Conectar BD
    $conexion = conectar_bd();
    
    // Obtener parámetros de DataTables
    $draw = isset($_POST['draw']) ? (int)$_POST['draw'] : 1;
    $start = isset($_POST['start']) ? (int)$_POST['start'] : 0;
    $length = isset($_POST['length']) ? (int)$_POST['length'] : 10;
    $searchValue = isset($_POST['search']['value']) ? $_POST['search']['value'] : '';
    
    // Obtener filtros
    $filtro_sucursal = isset($_POST['filtro_sucursal']) ? trim($_POST['filtro_sucursal']) : '';
    $filtro_tipo = isset($_POST['filtro_tipo']) ? trim($_POST['filtro_tipo']) : '';
    $filtro_estado = isset($_POST['filtro_estado']) ? trim($_POST['filtro_estado']) : '';
    $filtro_origen = isset($_POST['filtro_origen']) ? trim($_POST['filtro_origen']) : '';
    $filtro_fecha_desde = isset($_POST['filtro_fecha_desde']) ? trim($_POST['filtro_fecha_desde']) : '';
    $filtro_fecha_hasta = isset($_POST['filtro_fecha_hasta']) ? trim($_POST['filtro_fecha_hasta']) : '';
    $filtro_periodo = isset($_POST['filtro_periodo']) ? trim($_POST['filtro_periodo']) : '';
    $filtro_tipo_fecha = isset($_POST['filtro_tipo_fecha']) ? trim($_POST['filtro_tipo_fecha']) : 'vendido';
    
    // Campo de fecha según tipo seleccionado
    if ($filtro_tipo_fecha === 'enviado') {
        $campoFecha = 'av.fecha_enviado';
    } elseif ($filtro_tipo_fecha === 'en_venta') {
        $campoFecha = 'av.fecha_en_venta';
    } else {
        $campoFecha = 'av.fecha_vendido';
    }
    
    // Construir WHERE clause
    $whereConditions = array();
    $params = array();
    $types = '';
    
    // Filtro de sucursal
    if (!empty($filtro_sucursal)) {
        $whereConditions[] = "av.id_sucursal_destino = ?";
        $params[] = $filtro_sucursal;
        $types .= 'i';
    }
    
    // Filtro de tipo
    if (!empty($filtro_tipo)) {
        $whereConditions[] = "av.tipo = ?";
        $params[] = $filtro_tipo;
        $types .= 's';
    }
    
    // Filtro de estado
    if (!empty($filtro_estado)) {
        $whereConditions[] = "av.estado = ?";
        $params[] = $filtro_estado;
        $types .= 's';
    }
    
    // Filtro de origen
    if (!empty($filtro_origen)) {
        $whereConditions[] = "av.origen_articulo = ?";
        $params[] = $filtro_origen;
        $types .= 's';
    }
    
    // Filtro de fecha según período y tipo de fecha
    if ($filtro_periodo === 'dia') {
        $hoy = date('Y-m-d');
        $whereConditions[] = "DATE({$campoFecha}) = ?";
        $params[] = $hoy;
        $types .= 's';
    } elseif ($filtro_periodo === 'mes') {
        $whereConditions[] = "MONTH({$campoFecha}) = MONTH(CURRENT_DATE()) AND YEAR({$campoFecha}) = YEAR(CURRENT_DATE())";
    } elseif ($filtro_periodo === 'fecha') {
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
    
    // Búsqueda global
    if (!empty($searchValue)) {
        $whereConditions[] = "(
            av.id LIKE ? OR
            av.descripcion LIKE ? OR
            av.estado LIKE ? OR
            s.nombre_sucursal LIKE ?
        )";
        $searchParam = '%' . $searchValue . '%';
        $params[] = $searchValue;
        $params[] = $searchParam;
        $params[] = $searchParam;
        $params[] = $searchParam;
        $types .= 'ssss';
    }
    
    $whereClause = '';
    if (count($whereConditions) > 0) {
        $whereClause = 'WHERE ' . implode(' AND ', $whereConditions);
    }
    
    // Consulta para contar total de registros (sin filtros)
    $query_total = "SELECT COUNT(*) as total FROM articulos_venta av";
    $result_total = mysqli_query($conexion, $query_total);
    $row_total = mysqli_fetch_assoc($result_total);
    $recordsTotal = $row_total['total'];
    
    // Consulta para contar registros filtrados
    $query_filtered = "
        SELECT COUNT(*) as total 
        FROM articulos_venta av
        LEFT JOIN sucursal s ON av.id_sucursal_destino = s.id_sucursal
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
    
    // Consulta principal con paginación
    $query = "
        SELECT 
            av.id as id_articulo,
            av.id_articulo_sucursal,
            av.id_sucursal_destino,
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
            s.nombre_sucursal,
            s_origen.nombre_sucursal as sucursal_origen,
            u.nombre_usuario
        FROM articulos_venta av
        LEFT JOIN sucursal s ON av.id_sucursal_destino = s.id_sucursal
        LEFT JOIN sucursal s_origen ON av.id_sucursal_origen = s_origen.id_sucursal
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
        // Calcular € por gramo si no está precalculado
        $euro_gramo = $row['precio_gramo'];
        if (empty($euro_gramo) && $row['peso'] > 0) {
            $euro_gramo = $row['precio'] / $row['peso'];
        }
        
        // Badge para tipo
        $tipo_badge = '';
        if ($row['tipo'] === 'oro') {
            $tipo_badge = '<span class="badge bg-label-warning ">Oro</span>';
        } elseif ($row['tipo'] === 'plata') {
            $tipo_badge = '<span class="badge bg-label-secondary ">Plata</span>';
        } elseif ($row['tipo'] === 'acero') {
            $tipo_badge = '<span class="badge bg-label-dark ">Acero</span>';
        } else {
            $tipo_badge = '<span class="badge bg-label-secondary ">Otros</span>';
        }
        
        // Badge para estado
        $estado_badge = '';
        if (!empty($row['estado'])) {
            $estado_class = 'secondary';
            $estado_texto = $row['estado'];
            
            switch($row['estado']) {
                case 'enventa': 
                    $estado_class = 'success';
                    $estado_texto = 'En venta';
                    break;
                case 'vendido': 
                case 'vendido_web':
                    $estado_class = 'info';
                    $estado_texto = $row['estado'] === 'vendido_web' ? 'Vendido web' : 'Vendido';
                    break;
                case 'reservado': 
                    $estado_class = 'warning';
                    $estado_texto = 'Reservado';
                    break;
                case 'enviado': 
                    $estado_class = 'primary';
                    $estado_texto = 'Enviado';
                    break;
                case 'retirado': 
                    $estado_class = 'danger';
                    $estado_texto = 'Retirado';
                    break;
                case 'mermado': 
                    $estado_class = 'dark';
                    $estado_texto = 'Mermado';
                    break;
                case 'enreparacion': 
                    $estado_class = 'secondary';
                    $estado_texto = 'En reparación';
                    break;
                case 'noetiquetado_c': 
                case 'noetiquetado_u':
                    $estado_class = 'secondary';
                    $estado_texto = 'No etiquetado';
                    break;
            }
            $estado_badge = '<span class="badge bg-label-' . $estado_class . ' ">' . htmlspecialchars($estado_texto) . '</span>';
        }
        
        // Badge para origen
        $origen_badge = '';
        if ($row['origen_articulo'] === 'central') {
            $origen_badge = '<span class="badge bg-label-primary ">Central</span>';
        } else {
            $origen_badge = '<span class="badge bg-label-info ">Sucursal</span>';
        }
        // Botón Vender (solo si estado es "enventa")
        $boton_vender = '-';
        if ($row['estado'] === 'enventa') {
            // Escapar descripción para JavaScript (convertir comillas y saltos de línea)
            $descripcion_js = str_replace(['"', "'", "\n", "\r"], ['&quot;', '&#39;', ' ', ' '], $row['descripcion']);
            $boton_vender = '<button type="button" class="btn btn-sm btn-primary waves-effect button-actions-datatable" onclick="venderArticulo(' . $row['id_articulo'] . ', &quot;' . htmlspecialchars($descripcion_js) . '&quot;, ' . $row['id_sucursal_destino'] . ')">
                <i class="icon-base ri ri-money-euro-circle-line me-1 icon-16px"></i>Vender
            </button>';
        }
        
        $data[] = [
            htmlspecialchars($row['id_articulo']),                              // 0 - SKU
            htmlspecialchars($row['descripcion']),                              // 1 - Descripción
            htmlspecialchars($row['sucursal_origen'] ?: '---'),                // 2 - Sucursal Origen
            htmlspecialchars($row['nombre_sucursal'] ?: '---'),                // 3 - Sucursal (destino)
            number_format($row['peso'], 2, ',', '.') . ' g',                   // 4 - Peso
            number_format($row['precio'], 0, ',', '.') . ' €',                 // 5 - Precio (sin decimales)
            number_format($row['precio_coste'], 0, ',', '.') . ' €',           // 6 - Precio Coste (sin decimales)
            number_format($euro_gramo, 2, ',', '.') . ' €/g',                  // 7 - €/g
            $tipo_badge,                                                         // 8 - Tipo
            $estado_badge,                                                       // 9 - Estado
            !empty($row['fecha_enviado']) && $row['fecha_enviado'] !== '0000-00-00 00:00:00' ? date('d/m/Y', strtotime($row['fecha_enviado'])) : '-',  // 10 - F. Enviado
            !empty($row['fecha_en_venta']) && $row['fecha_en_venta'] !== '0000-00-00 00:00:00' ? date('d/m/Y', strtotime($row['fecha_en_venta'])) : '-', // 11 - F. En Venta
            !empty($row['fecha_vendido']) && $row['fecha_vendido'] !== '0000-00-00' ? date('d/m/Y', strtotime($row['fecha_vendido'])) : '-',             // 12 - F. Vendido
            !empty($row['fecha_retirado']) && $row['fecha_retirado'] !== '0000-00-00 00:00:00' ? date('d/m/Y', strtotime($row['fecha_retirado'])) : '-', // 13 - F. Retirado
            htmlspecialchars($row['nombre_usuario'] ?: '---'),                 // 14 - Creado Por
            $origen_badge,                                                       // 15 - Origen
            $boton_vender,                                                       // 16 - Acciones
            $row['id_articulo']                                                 // 17 - ID (hidden para click)
        ];
    }
    
    mysqli_stmt_close($stmt);
    mysqli_close($conexion);
    
    // Respuesta
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
?>

