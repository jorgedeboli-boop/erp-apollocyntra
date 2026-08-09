<?php
require_once '../../../include/session.php';
require_once '../../../include/functions.php';

header('Content-Type: application/json');

try {
    $conexion = conectar_bd();

    $filtro_sucursal = isset($_POST['filtro_sucursal']) ? trim($_POST['filtro_sucursal']) : '';
    $filtro_tipo = isset($_POST['filtro_tipo']) ? trim($_POST['filtro_tipo']) : '';
    $filtro_origen = isset($_POST['filtro_origen']) ? trim($_POST['filtro_origen']) : '';
    $filtro_fecha_desde = isset($_POST['filtro_fecha_desde']) ? trim($_POST['filtro_fecha_desde']) : '';
    $filtro_fecha_hasta = isset($_POST['filtro_fecha_hasta']) ? trim($_POST['filtro_fecha_hasta']) : '';
    $filtro_periodo = isset($_POST['filtro_periodo']) ? trim($_POST['filtro_periodo']) : '';
    $tipo_stat = isset($_POST['tipo']) ? trim($_POST['tipo']) : 'total';

    $campoFecha = 'av.fecha_en_venta';

    $whereConditions = array("av.estado = 'enventa'");
    $params = array();
    $types = '';

    if (!empty($filtro_sucursal)) {
        $whereConditions[] = 'av.id_sucursal_destino = ?';
        $params[] = $filtro_sucursal;
        $types .= 'i';
    }

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

    switch ($tipo_stat) {
        case 'total-oro':
            $whereConditions[] = "av.tipo = 'oro'";
            break;
        case 'total-plata':
            $whereConditions[] = "av.tipo = 'plata'";
            break;
        case 'total-otros':
            $whereConditions[] = "av.tipo NOT IN ('oro', 'plata')";
            break;
    }

    $whereClause = 'WHERE ' . implode(' AND ', $whereConditions);

    $query = "
        SELECT COUNT(*) as total
        FROM articulos_venta av
        LEFT JOIN sucursal s ON av.id_sucursal_destino = s.id_sucursal
        $whereClause
    ";

    if (!empty($types)) {
        $stmt = mysqli_prepare($conexion, $query);
        mysqli_stmt_bind_param($stmt, $types, ...$params);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        $row = mysqli_fetch_assoc($result);
        mysqli_stmt_close($stmt);
    } else {
        $result = mysqli_query($conexion, $query);
        $row = mysqli_fetch_assoc($result);
    }

    $total = $row['total'];

    mysqli_close($conexion);

    echo json_encode([
        'success' => true,
        'total' => $total
    ]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
