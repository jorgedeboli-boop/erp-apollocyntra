<?php
require_once '../../../include/session.php';
require_once '../../../include/functions.php';

if (!usuario_autenticado()) {
    http_response_code(401);
    echo json_encode(['error' => 'No autorizado']);
    exit;
}

header('Content-Type: application/json');

try {
    $conexion = conectar_bd();

    $searchValue = isset($_POST['search']) ? trim($_POST['search']) : '';
    $filtro_sucursal = isset($_POST['filtro_sucursal']) ? trim($_POST['filtro_sucursal']) : '';
    $filtro_tipo = isset($_POST['filtro_tipo']) ? trim($_POST['filtro_tipo']) : '';
    $filtro_origen = isset($_POST['filtro_origen']) ? trim($_POST['filtro_origen']) : '';
    $filtro_fecha_desde = isset($_POST['filtro_fecha_desde']) ? trim($_POST['filtro_fecha_desde']) : '';
    $filtro_fecha_hasta = isset($_POST['filtro_fecha_hasta']) ? trim($_POST['filtro_fecha_hasta']) : '';
    $filtro_periodo = isset($_POST['filtro_periodo']) ? trim($_POST['filtro_periodo']) : '';

    $campoFecha = 'av.fecha_en_venta';

    $whereConditions = array("av.estado = 'enventa'");
    $searchParams = array();

    if (!empty($filtro_sucursal)) {
        $whereConditions[] = 'av.id_sucursal_destino = ?';
        $searchParams[] = $filtro_sucursal;
    }

    if (!empty($filtro_tipo)) {
        $whereConditions[] = 'av.tipo = ?';
        $searchParams[] = $filtro_tipo;
    }

    if (!empty($filtro_origen)) {
        $whereConditions[] = 'av.origen_articulo = ?';
        $searchParams[] = $filtro_origen;
    }

    if ($filtro_periodo === 'dia') {
        $hoy = date('Y-m-d');
        $whereConditions[] = "DATE({$campoFecha}) = ?";
        $searchParams[] = $hoy;
    } elseif ($filtro_periodo === 'mes') {
        $whereConditions[] = "MONTH({$campoFecha}) = MONTH(CURRENT_DATE()) AND YEAR({$campoFecha}) = YEAR(CURRENT_DATE())";
    } elseif ($filtro_periodo === 'fecha' || $filtro_periodo === 'personalizado') {
        if (!empty($filtro_fecha_desde) && !empty($filtro_fecha_hasta)) {
            $whereConditions[] = "DATE({$campoFecha}) BETWEEN ? AND ?";
            $searchParams[] = $filtro_fecha_desde;
            $searchParams[] = $filtro_fecha_hasta;
        } elseif (!empty($filtro_fecha_desde)) {
            $whereConditions[] = "DATE({$campoFecha}) >= ?";
            $searchParams[] = $filtro_fecha_desde;
        } elseif (!empty($filtro_fecha_hasta)) {
            $whereConditions[] = "DATE({$campoFecha}) <= ?";
            $searchParams[] = $filtro_fecha_hasta;
        }
    }

    if (!empty($searchValue)) {
        $whereConditions[] = '(
            av.id LIKE ? OR
            av.descripcion LIKE ? OR
            av.estado LIKE ? OR
            s.nombre_sucursal LIKE ?
        )';
        $searchTerm = '%' . $searchValue . '%';
        $searchParams[] = $searchValue;
        $searchParams[] = $searchTerm;
        $searchParams[] = $searchTerm;
        $searchParams[] = $searchTerm;
    }

    $whereClause = 'WHERE ' . implode(' AND ', $whereConditions);

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
            s.nombre_sucursal,
            s_origen.nombre_sucursal as sucursal_origen,
            u.nombre_usuario
        FROM articulos_venta av
        LEFT JOIN sucursal s ON av.id_sucursal_destino = s.id_sucursal
        LEFT JOIN sucursal s_origen ON av.id_sucursal_origen = s_origen.id_sucursal
        LEFT JOIN usuarios u ON av.creado_por = u.id_usuario
        $whereClause
        ORDER BY av.id DESC
    ";

    if (!empty($searchParams)) {
        $stmt = mysqli_prepare($conexion, $query);
        if ($stmt) {
            $types = str_repeat('s', count($searchParams));
            mysqli_stmt_bind_param($stmt, $types, ...$searchParams);
            mysqli_stmt_execute($stmt);
            $result = mysqli_stmt_get_result($stmt);
        } else {
            throw new Exception('Error en preparación de consulta: ' . mysqli_error($conexion));
        }
    } else {
        $result = mysqli_query($conexion, $query);
    }

    if (!$result) {
        throw new Exception('Error en consulta: ' . mysqli_error($conexion));
    }

    $data = [];
    while ($row = mysqli_fetch_assoc($result)) {
        $euro_gramo = $row['precio_gramo'];
        if (empty($euro_gramo) && $row['peso'] > 0) {
            $euro_gramo = $row['precio'] / $row['peso'];
        }

        $tipo = ucfirst($row['tipo']);
        $origen = ucfirst($row['origen_articulo']);

        $data[] = [
            $row['id_articulo'],
            $row['descripcion'],
            $row['sucursal_origen'] ? $row['sucursal_origen'] : 'N/A',
            $row['nombre_sucursal'],
            number_format($row['peso'], 2, ',', '.') . ' g',
            number_format($row['precio'], 0, ',', '.') . ' €',
            number_format($row['precio_coste'], 0, ',', '.') . ' €',
            number_format($euro_gramo, 2, ',', '.') . ' €/g',
            $tipo,
            !empty($row['fecha_enviado']) && $row['fecha_enviado'] !== '0000-00-00 00:00:00' ? date('d/m/Y', strtotime($row['fecha_enviado'])) : '-',
            !empty($row['fecha_en_venta']) && $row['fecha_en_venta'] !== '0000-00-00 00:00:00' ? date('d/m/Y', strtotime($row['fecha_en_venta'])) : '-',
            $row['nombre_usuario'] ? $row['nombre_usuario'] : 'N/A',
            $origen
        ];
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
