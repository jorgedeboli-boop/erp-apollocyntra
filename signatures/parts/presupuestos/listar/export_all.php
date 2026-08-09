<?php
require_once __DIR__ . '/../../../include/session.php';
require_once __DIR__ . '/../../../include/functions.php';

header('Content-Type: application/json; charset=utf-8');

try {
    $conexion = conectar_bd();

    $searchValue = isset($_POST['search']) ? trim($_POST['search']) : '';
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

    $query = "
        SELECT
            p.numero,
            p.titulo,
            p.total,
            p.estado,
            p.fecha_creacion,
            p.fecha_validez,
            p.id,
            TRIM(CONCAT(COALESCE(c.nombre,''), ' ', COALESCE(c.apellido,''))) AS cliente_nombre
        FROM presupuestos p
        LEFT JOIN clientes c ON p.id_cliente = c.id_cliente
        WHERE $whereSql
        ORDER BY p.fecha_creacion DESC
    ";

    if ($types !== '') {
        $stmt = mysqli_prepare($conexion, $query);
        mysqli_stmt_bind_param($stmt, $types, ...$params);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
    } else {
        $stmt = null;
        $result = mysqli_query($conexion, $query);
    }

    if (!$result) {
        throw new Exception(mysqli_error($conexion));
    }

    $data = [];
    while ($row = mysqli_fetch_assoc($result)) {
        $fc = $row['fecha_creacion'];
        $fv = $row['fecha_validez'];
        $data[] = [
            $row['numero'] ? $row['numero'] : ('#' . $row['id']),
            $row['titulo'] ? $row['titulo'] : '—',
            trim($row['cliente_nombre']) ?: '—',
            number_format((float)$row['total'], 2, ',', '.') . ' €',
            $row['estado'],
            $fc && $fc !== '0000-00-00 00:00:00' ? date('d/m/Y H:i', strtotime($fc)) : '—',
            ($fv && $fv !== '0000-00-00') ? date('d/m/Y', strtotime($fv)) : '—'
        ];
    }

    if ($stmt) {
        mysqli_stmt_close($stmt);
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
