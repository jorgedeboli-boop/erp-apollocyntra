<?php
require_once '../../../include/session.php';
require_once '../../../include/functions.php';

header('Content-Type: application/json; charset=utf-8');

if (!isset($_SESSION['usuario_id'])) {
    http_response_code(401);
    echo json_encode(['ok' => false, 'error' => 'No autorizado']);
    exit;
}

$filtro_empresa = isset($_POST['filtro_empresa']) ? trim($_POST['filtro_empresa']) : '';
$filtro_sucursal = isset($_POST['filtro_sucursal']) ? trim($_POST['filtro_sucursal']) : '';
$filtro_fecha_desde = isset($_POST['filtro_fecha_desde']) ? trim($_POST['filtro_fecha_desde']) : '';
$filtro_fecha_hasta = isset($_POST['filtro_fecha_hasta']) ? trim($_POST['filtro_fecha_hasta']) : '';
$filtro_periodo = isset($_POST['filtro_periodo']) ? trim($_POST['filtro_periodo']) : 'dia';

try {
    $conexion = conectar_bd();
    if (!$conexion) {
        throw new Exception('Error de conexión a la base de datos');
    }

    $sql = 'SELECT
                l.id_lote,
                l.sucursal AS id_sucursal,
                l.fecha_compra,
                l.peso,
                COALESCE(a.peso_articulos, 0) AS peso_articulos,
                s.nombre_sucursal,
                e.nombre_empresa
            FROM lotes_joyeria l
            LEFT JOIN sucursal s ON s.id_sucursal = l.sucursal
            LEFT JOIN empresas e ON e.id_empresa = s.empresa_id
            LEFT JOIN (
                SELECT
                    id_lote_articulos,
                    sucursal_articulo,
                    SUM(peso_articulo) AS peso_articulos
                FROM articulos_lotes
                GROUP BY id_lote_articulos, sucursal_articulo
            ) a ON a.id_lote_articulos = l.id_lote
               AND a.sucursal_articulo = l.sucursal
            WHERE LOWER(TRIM(l.tipo_de_lote)) = \'oro\'
              AND ABS(ROUND(l.peso, 2) - ROUND(COALESCE(a.peso_articulos, 0), 2)) >= 0.005
    ';

    $params = [];
    $types = '';

    if ($filtro_empresa !== '') {
        $sql .= ' AND s.empresa_id = ?';
        $params[] = (int) $filtro_empresa;
        $types .= 'i';
    }

    if ($filtro_sucursal !== '') {
        $sql .= ' AND l.sucursal = ?';
        $params[] = (int) $filtro_sucursal;
        $types .= 'i';
    }

    if ($filtro_periodo === 'dia') {
        $sql .= ' AND DATE(l.fecha_compra) = CURDATE()';
    } elseif ($filtro_periodo === 'mes') {
        $sql .= ' AND MONTH(l.fecha_compra) = MONTH(CURDATE()) AND YEAR(l.fecha_compra) = YEAR(CURDATE())';
    } elseif ($filtro_periodo === 'todos') {
        // Sin filtro de fecha
    } else {
        if ($filtro_fecha_desde !== '' && $filtro_fecha_hasta !== '') {
            $sql .= ' AND DATE(l.fecha_compra) BETWEEN ? AND ?';
            $params[] = $filtro_fecha_desde;
            $params[] = $filtro_fecha_hasta;
            $types .= 'ss';
        } elseif ($filtro_fecha_desde !== '') {
            $sql .= ' AND DATE(l.fecha_compra) >= ?';
            $params[] = $filtro_fecha_desde;
            $types .= 's';
        } elseif ($filtro_fecha_hasta !== '') {
            $sql .= ' AND DATE(l.fecha_compra) <= ?';
            $params[] = $filtro_fecha_hasta;
            $types .= 's';
        }
    }

    $sql .= ' ORDER BY l.fecha_compra DESC, l.id_lote ASC LIMIT 200';

    $stmt = mysqli_prepare($conexion, $sql);
    if (!$stmt) {
        throw new Exception('Error preparando consulta: ' . mysqli_error($conexion));
    }

    if ($types !== '') {
        mysqli_stmt_bind_param($stmt, $types, ...$params);
    }

    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);

    $lotes = [];
    while ($row = $result ? mysqli_fetch_assoc($result) : null) {
        $fechaRaw = (string) ($row['fecha_compra'] ?? '');
        $fechaYmd = strlen($fechaRaw) >= 10 ? substr($fechaRaw, 0, 10) : '';
        $fechaLabel = '—';
        if ($fechaYmd && $fechaYmd !== '0000-00-00') {
            $ts = strtotime($fechaYmd);
            $fechaLabel = $ts ? date('d-m-Y', $ts) : $fechaYmd;
        }

        $lotes[] = [
            'id_lote' => (int) $row['id_lote'],
            'id_sucursal' => (int) $row['id_sucursal'],
            'nombre_sucursal' => (string) ($row['nombre_sucursal'] ?: 'Sin sucursal'),
            'nombre_empresa' => (string) ($row['nombre_empresa'] ?: 'Sin empresa'),
            'fecha_compra' => $fechaYmd,
            'fecha_compra_label' => $fechaLabel,
            'peso_neto' => round((float) ($row['peso'] ?? 0), 2),
            'peso_articulos' => round((float) ($row['peso_articulos'] ?? 0), 2),
        ];
    }

    mysqli_stmt_close($stmt);
    mysqli_close($conexion);

    echo json_encode([
        'ok' => true,
        'total' => count($lotes),
        'lotes' => $lotes,
    ]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'ok' => false,
        'error' => $e->getMessage(),
    ]);
}
