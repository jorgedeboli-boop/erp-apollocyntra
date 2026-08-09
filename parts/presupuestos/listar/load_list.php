<?php
/**
 * DataTables server-side: listado de presupuestos
 */
require_once __DIR__ . '/../../../include/session.php';
require_once __DIR__ . '/../../../include/functions.php';

header('Content-Type: application/json; charset=utf-8');

try {
    $conexion = conectar_bd();

    $draw = isset($_POST['draw']) ? (int)$_POST['draw'] : 1;
    $start = isset($_POST['start']) ? (int)$_POST['start'] : 0;
    $length = isset($_POST['length']) ? (int)$_POST['length'] : 10;
    $searchValue = isset($_POST['search']['value']) ? trim($_POST['search']['value']) : '';

    $filtro_estado = isset($_POST['filtro_estado']) ? trim($_POST['filtro_estado']) : '';
    $filtro_empresa = isset($_POST['filtro_empresa']) ? (int)$_POST['filtro_empresa'] : 0;
    $filtro_fecha_desde = isset($_POST['filtro_fecha_desde']) ? trim($_POST['filtro_fecha_desde']) : '';
    $filtro_fecha_hasta = isset($_POST['filtro_fecha_hasta']) ? trim($_POST['filtro_fecha_hasta']) : '';

    $where = ['1=1'];
    $params = [];
    $types = '';

    if ($filtro_estado !== '') {
        $where[] = 'p.estado = ?';
        $params[] = $filtro_estado;
        $types .= 's';
    }

    if ($filtro_empresa > 0) {
        $where[] = 'p.rel_id_empresa = ?';
        $params[] = $filtro_empresa;
        $types .= 'i';
    }

    if ($filtro_fecha_desde !== '' && $filtro_fecha_hasta !== '') {
        $where[] = 'DATE(p.fecha_creacion) BETWEEN ? AND ?';
        $params[] = $filtro_fecha_desde;
        $params[] = $filtro_fecha_hasta;
        $types .= 'ss';
    } elseif ($filtro_fecha_desde !== '') {
        $where[] = 'DATE(p.fecha_creacion) >= ?';
        $params[] = $filtro_fecha_desde;
        $types .= 's';
    } elseif ($filtro_fecha_hasta !== '') {
        $where[] = 'DATE(p.fecha_creacion) <= ?';
        $params[] = $filtro_fecha_hasta;
        $types .= 's';
    }

    if ($searchValue !== '') {
        $where[] = '(p.numero LIKE ? OR p.titulo LIKE ? OR CONCAT(COALESCE(c.nombre,\'\'),\' \',COALESCE(c.apellido,\'\')) LIKE ?)';
        $like = '%' . $searchValue . '%';
        $params[] = $like;
        $params[] = $like;
        $params[] = $like;
        $types .= 'sss';
    }

    $whereSql = implode(' AND ', $where);

    $qTotal = 'SELECT COUNT(*) AS total FROM presupuestos';
    $rt = mysqli_query($conexion, $qTotal);
    $rowT = mysqli_fetch_assoc($rt);
    $recordsTotal = (int)$rowT['total'];

    $qFiltered = "
        SELECT COUNT(*) AS total
        FROM presupuestos p
        LEFT JOIN clientes c ON p.id_cliente = c.id_cliente
        WHERE $whereSql
    ";

    if ($types !== '') {
        $stmtF = mysqli_prepare($conexion, $qFiltered);
        mysqli_stmt_bind_param($stmtF, $types, ...$params);
        mysqli_stmt_execute($stmtF);
        $rf = mysqli_stmt_get_result($stmtF);
        $rowF = mysqli_fetch_assoc($rf);
        $recordsFiltered = (int)$rowF['total'];
        mysqli_stmt_close($stmtF);
    } else {
        $rf = mysqli_query($conexion, $qFiltered);
        $rowF = mysqli_fetch_assoc($rf);
        $recordsFiltered = (int)$rowF['total'];
    }

    $orderCol = 5;
    $orderDir = 'DESC';
    if (isset($_POST['order'][0]['column'])) {
        $orderCol = (int)$_POST['order'][0]['column'];
    }
    if (isset($_POST['order'][0]['dir']) && strtolower($_POST['order'][0]['dir']) === 'asc') {
        $orderDir = 'ASC';
    }

    $orderCols = [
        'p.numero',
        'p.titulo',
        'cliente_nombre',
        'p.total',
        'p.estado',
        'p.fecha_creacion',
        'p.fecha_validez'
    ];
    $orderBy = isset($orderCols[$orderCol]) ? $orderCols[$orderCol] : 'p.fecha_creacion';
    if ($orderBy === 'cliente_nombre') {
        $orderBy = 'cliente_nombre';
    }

    $query = "
        SELECT
            p.id,
            p.numero,
            p.titulo,
            p.total,
            p.estado,
            p.fecha_creacion,
            p.fecha_validez,
            TRIM(CONCAT(COALESCE(c.nombre,''), ' ', COALESCE(c.apellido,''))) AS cliente_nombre
        FROM presupuestos p
        LEFT JOIN clientes c ON p.id_cliente = c.id_cliente
        WHERE $whereSql
        ORDER BY $orderBy $orderDir
        LIMIT ?, ?
    ";

    $paramsData = $params;
    $typesData = $types . 'ii';
    $paramsData[] = $start;
    $paramsData[] = $length;

    $stmt = mysqli_prepare($conexion, $query);
    if ($typesData !== 'ii') {
        mysqli_stmt_bind_param($stmt, $typesData, ...$paramsData);
    } else {
        mysqli_stmt_bind_param($stmt, 'ii', $start, $length);
    }
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);

    $badgeClass = [
        'borrador' => 'secondary',
        'enviado' => 'info',
        'aceptado' => 'success',
        'rechazado' => 'danger',
        'caducado' => 'warning',
        'facturado' => 'primary',
        'cancelado' => 'dark'
    ];

    $data = [];
    while ($row = mysqli_fetch_assoc($result)) {
        $est = $row['estado'];
        $bc = isset($badgeClass[$est]) ? $badgeClass[$est] : 'secondary';
        $estBadge = '<span class="badge bg-label-' . $bc . ' text-capitalize">' . htmlspecialchars($est) . '</span>';

        $fc = $row['fecha_creacion'];
        $fv = $row['fecha_validez'];
        $data[] = [
            htmlspecialchars($row['numero'] ?: ('#' . $row['id'])),
            htmlspecialchars($row['titulo'] ?: '—'),
            htmlspecialchars(trim($row['cliente_nombre']) ?: '—'),
            number_format((float)$row['total'], 2, ',', '.') . ' €',
            $estBadge,
            $fc && $fc !== '0000-00-00 00:00:00' ? date('d/m/Y H:i', strtotime($fc)) : '—',
            ($fv && $fv !== '0000-00-00') ? date('d/m/Y', strtotime($fv)) : '—',
            (int)$row['id']
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
