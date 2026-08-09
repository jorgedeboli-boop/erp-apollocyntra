<?php
require_once '../../../include/session.php';
require_once '../../../include/functions.php';

if (!isset($_SESSION['usuario_id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'No autorizado']);
    exit;
}

$draw = isset($_POST['draw']) ? intval($_POST['draw']) : 1;
$start = isset($_POST['start']) ? intval($_POST['start']) : 0;
$length = isset($_POST['length']) ? intval($_POST['length']) : 10;
$search = isset($_POST['search']['value']) ? trim($_POST['search']['value']) : '';

$orderColumn = isset($_POST['order'][0]['column']) ? (int)$_POST['order'][0]['column'] : 0;
$orderDir = isset($_POST['order'][0]['dir']) ? $_POST['order'][0]['dir'] : 'desc';

$columnMap = [
    0 => 'ad.id',
    1 => 's.nombre_sucursal',
    2 => 'ad.usuario',
    3 => 'ad.codigo',
    4 => 'ad.id_articulo',
    5 => 'av.descripcion',
    6 => 'ad.estado',
    7 => 'ad.fecha',
    8 => 'ad.precio_original',
    9 => 'ad.precio_nuevo',
    10 => 'ad.id'
];

if (!isset($columnMap[$orderColumn])) {
    $orderColumn = 0;
}

$orderBy = $columnMap[$orderColumn];
$orderDirection = strtoupper($orderDir) === 'ASC' ? 'ASC' : 'DESC';

$filtro_sucursal = isset($_POST['filtro_sucursal']) ? trim($_POST['filtro_sucursal']) : '';
$filtro_estado = isset($_POST['filtro_estado']) ? trim($_POST['filtro_estado']) : '';

try {
    $conexion = conectar_bd();

    if (!$conexion) {
        throw new Exception('Error de conexión a la base de datos');
    }

    $query_base = "FROM autorizaciones_descuento_articulo_venta ad
                    LEFT JOIN sucursal s ON ad.sucursal = s.id_sucursal
                    LEFT JOIN articulos_venta av ON ad.id_articulo = av.id";

    $params = [];
    $types = '';

    $query_base .= " WHERE 1=1";

    if (!empty($filtro_sucursal)) {
        $query_base .= " AND s.nombre_sucursal = ?";
        $params[] = $filtro_sucursal;
        $types .= 's';
    }

    if (!empty($filtro_estado)) {
        $query_base .= " AND ad.estado = ?";
        $params[] = $filtro_estado;
        $types .= 's';
    }

    if (!empty($search)) {
        $query_base .= " AND (
            ad.id LIKE ? OR
            s.nombre_sucursal LIKE ? OR
            ad.usuario LIKE ? OR
            ad.codigo LIKE ? OR
            CAST(ad.id_articulo AS CHAR) LIKE ? OR
            av.descripcion LIKE ? OR
            ad.estado LIKE ? OR
            CAST(ad.precio_original AS CHAR) LIKE ? OR
            CAST(ad.precio_nuevo AS CHAR) LIKE ?
        )";
        $search_param = "%$search%";
        for ($i = 0; $i < 9; $i++) {
            $params[] = $search_param;
        }
        $types .= str_repeat('s', 9);
    }

    $query_count = "SELECT COUNT(*) as total " . $query_base;
    $stmt_count = mysqli_prepare($conexion, $query_count);

    if (!empty($params)) {
        mysqli_stmt_bind_param($stmt_count, $types, ...$params);
    }

    mysqli_stmt_execute($stmt_count);
    $result_count = mysqli_stmt_get_result($stmt_count);
    $total_records = mysqli_fetch_assoc($result_count)['total'];
    mysqli_stmt_close($stmt_count);

    $query_main = "SELECT 
                        ad.id,
                        s.nombre_sucursal,
                        ad.sucursal,
                        ad.usuario,
                        ad.codigo,
                        ad.id_articulo,
                        av.descripcion as descripcion_articulo,
                        ad.estado,
                        ad.fecha,
                        ad.precio_original,
                        ad.precio_nuevo
                    " . $query_base . " 
                    ORDER BY $orderBy $orderDirection 
                    LIMIT ?, ?";

    $params[] = $start;
    $params[] = $length;
    $types .= 'ii';

    $stmt_main = mysqli_prepare($conexion, $query_main);
    mysqli_stmt_bind_param($stmt_main, $types, ...$params);
    mysqli_stmt_execute($stmt_main);
    $result_main = mysqli_stmt_get_result($stmt_main);

    $data = [];
    while ($row = mysqli_fetch_assoc($result_main)) {
        $data[] = [
            $row['id'],
            htmlspecialchars($row['nombre_sucursal'] ?? 'Sin sucursal'),
            htmlspecialchars($row['usuario'] ?? ''),
            htmlspecialchars($row['codigo'] ?? ''),
            (int)$row['id_articulo'],
            htmlspecialchars($row['descripcion_articulo'] ?? '—'),
            $row['estado'],
            $row['fecha'],
            $row['precio_original'],
            $row['precio_nuevo'],
            [
                'id' => (int)$row['id'],
                'estado' => $row['estado'],
                'id_articulo' => (int)$row['id_articulo']
            ]
        ];
    }

    mysqli_stmt_close($stmt_main);
    mysqli_close($conexion);

    echo json_encode([
        'draw' => $draw,
        'recordsTotal' => $total_records,
        'recordsFiltered' => $total_records,
        'data' => $data
    ]);
} catch (Exception $e) {
    error_log('Error en load_list autorizaciones_descuento: ' . $e->getMessage());

    if (isset($stmt_main)) {
        mysqli_stmt_close($stmt_main);
    }
    if (isset($conexion)) {
        mysqli_close($conexion);
    }

    http_response_code(500);
    echo json_encode([
        'error' => 'Error del servidor: ' . $e->getMessage()
    ]);
}
