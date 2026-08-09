<?php
/**
 * Server-side processing para DataTable de control_etiquetado.
 */

require_once '../../../include/session.php';
require_once '../../../include/functions.php';

header('Content-Type: application/json');

try {
    $conexion = conectar_bd();

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

    $draw = isset($_POST['draw']) ? (int) $_POST['draw'] : 1;
    $start = isset($_POST['start']) ? (int) $_POST['start'] : 0;
    $length = isset($_POST['length']) ? (int) $_POST['length'] : 10;
    $searchValue = isset($_POST['search']['value']) ? trim((string) $_POST['search']['value']) : '';

    $filtro_sucursal = isset($_POST['filtro_sucursal']) ? trim((string) $_POST['filtro_sucursal']) : '';
    $filtro_tipo = isset($_POST['filtro_tipo']) ? trim((string) $_POST['filtro_tipo']) : '';
    $filtro_fecha_desde = isset($_POST['filtro_fecha_desde']) ? trim((string) $_POST['filtro_fecha_desde']) : '';
    $filtro_fecha_hasta = isset($_POST['filtro_fecha_hasta']) ? trim((string) $_POST['filtro_fecha_hasta']) : '';
    $filtro_periodo = isset($_POST['filtro_periodo']) ? trim((string) $_POST['filtro_periodo']) : '';

    if ($filtro_periodo === 'personalizado') {
        $filtro_periodo = 'fecha';
    }

    $whereConditions = array();
    $params = array();
    $types = '';

    if ($filtro_sucursal !== '') {
        $whereConditions[] = 'ce.sucursal_etiquetado = ?';
        $params[] = (int) $filtro_sucursal;
        $types .= 'i';
    }

    if ($filtro_tipo !== '') {
        $whereConditions[] = 'ce.tipo_control_etiquetado = ?';
        $params[] = $filtro_tipo;
        $types .= 's';
    }

    if ($filtro_periodo === 'dia') {
        $hoy = date('Y-m-d');
        $whereConditions[] = 'ce.fecha_etiquetado = ?';
        $params[] = $hoy;
        $types .= 's';
    } elseif ($filtro_periodo === 'mes') {
        $whereConditions[] = 'YEAR(ce.fecha_etiquetado) = YEAR(CURRENT_DATE()) AND MONTH(ce.fecha_etiquetado) = MONTH(CURRENT_DATE())';
    } elseif ($filtro_periodo === 'fecha' || ($filtro_fecha_desde !== '' || $filtro_fecha_hasta !== '')) {
        if ($filtro_fecha_desde !== '' && $filtro_fecha_hasta !== '') {
            $whereConditions[] = 'ce.fecha_etiquetado BETWEEN ? AND ?';
            $params[] = $filtro_fecha_desde;
            $params[] = $filtro_fecha_hasta;
            $types .= 'ss';
        } elseif ($filtro_fecha_desde !== '') {
            $whereConditions[] = 'ce.fecha_etiquetado >= ?';
            $params[] = $filtro_fecha_desde;
            $types .= 's';
        } elseif ($filtro_fecha_hasta !== '') {
            $whereConditions[] = 'ce.fecha_etiquetado <= ?';
            $params[] = $filtro_fecha_hasta;
            $types .= 's';
        }
    }

    if ($searchValue !== '') {
        $whereConditions[] = '(
            ce.id_control_etiquetado LIKE ? OR
            ce.envio_etiquetado LIKE ? OR
            ce.tipo_control_etiquetado LIKE ? OR
            u.nombre_usuario LIKE ? OR
            u.usuario LIKE ? OR
            s.nombre_sucursal LIKE ?
        )';
        $searchParam = '%' . $searchValue . '%';
        $params[] = $searchParam;
        $params[] = $searchParam;
        $params[] = $searchParam;
        $params[] = $searchParam;
        $params[] = $searchParam;
        $params[] = $searchParam;
        $types .= 'ssssss';
    }

    $whereClause = '';
    if (!empty($whereConditions)) {
        $whereClause = 'WHERE ' . implode(' AND ', $whereConditions);
    }

    $fromJoin = '
        FROM control_etiquetado ce
        LEFT JOIN usuarios u ON ce.usuario_etiquetado = u.id_usuario
        LEFT JOIN sucursal s ON ce.sucursal_etiquetado = s.id_sucursal
    ';

    $result_total = mysqli_query($conexion, 'SELECT COUNT(*) AS total FROM control_etiquetado');
    $row_total = mysqli_fetch_assoc($result_total);
    $recordsTotal = (int) ($row_total['total'] ?? 0);

    $query_filtered = "SELECT COUNT(*) AS total $fromJoin $whereClause";
    if ($types !== '') {
        $stmt_filtered = mysqli_prepare($conexion, $query_filtered);
        $mysqli_bind_params($stmt_filtered, $types, $params);
        mysqli_stmt_execute($stmt_filtered);
        $result_filtered = mysqli_stmt_get_result($stmt_filtered);
        $row_filtered = mysqli_fetch_assoc($result_filtered);
        mysqli_stmt_close($stmt_filtered);
    } else {
        $result_filtered = mysqli_query($conexion, $query_filtered);
        $row_filtered = mysqli_fetch_assoc($result_filtered);
    }
    $recordsFiltered = (int) ($row_filtered['total'] ?? 0);

    $query = "
        SELECT
            ce.id_control_etiquetado,
            ce.fecha_etiquetado,
            ce.hora_etiquetado,
            ce.usuario_etiquetado,
            ce.sucursal_etiquetado,
            ce.envio_etiquetado,
            ce.total_etiquetas,
            ce.tipo_control_etiquetado,
            u.nombre_usuario,
            u.usuario,
            s.nombre_sucursal
        $fromJoin
        $whereClause
        ORDER BY ce.id_control_etiquetado DESC
        LIMIT ?, ?
    ";

    $allParams = array_merge($params, array($start, $length));
    $allTypes = $types . 'ii';

    $stmt = mysqli_prepare($conexion, $query);
    $mysqli_bind_params($stmt, $allTypes, $allParams);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);

    $data = array();
    while ($row = mysqli_fetch_assoc($result)) {
        $id = (int) ($row['id_control_etiquetado'] ?? 0);
        $fecha = $row['fecha_etiquetado'] ?? '';
        $fechaFmt = ($fecha !== '' && $fecha !== '0000-00-00') ? date('d/m/Y', strtotime($fecha)) : '-';

        $hora = $row['hora_etiquetado'] ?? '';
        $horaFmt = '-';
        if ($hora !== '' && $hora !== '00:00:00') {
            $horaFmt = date('H:i', strtotime($hora));
        }

        $nombreUsuario = trim((string) ($row['nombre_usuario'] ?? ''));
        $loginUsuario = trim((string) ($row['usuario'] ?? ''));
        if ($nombreUsuario !== '') {
            $usuarioTxt = $nombreUsuario;
        } elseif ($loginUsuario !== '') {
            $usuarioTxt = $loginUsuario;
        } else {
            $usuarioTxt = (string) (int) ($row['usuario_etiquetado'] ?? 0);
        }

        $envioId = (int) ($row['envio_etiquetado'] ?? 0);
        $envioHtml = $envioId > 0 ? (string) $envioId : '-';

        $tipo = (string) ($row['tipo_control_etiquetado'] ?? '');
        $tipoLower = strtolower($tipo);
        $tipoCls = 'secondary';
        $tipoLabel = $tipo !== '' ? ucfirst($tipo) : '-';
        switch ($tipoLower) {
            case 'envio':
                $tipoCls = 'info';
                $tipoLabel = 'Envío';
                break;
            case 'todo':
                $tipoCls = 'primary';
                $tipoLabel = 'Todo';
                break;
            case 'sucursal':
                $tipoCls = 'warning';
                $tipoLabel = 'Sucursal';
                break;
            case 'articulo':
                $tipoCls = 'success';
                $tipoLabel = 'Artículo';
                break;
            case 'false':
                $tipoCls = 'secondary';
                $tipoLabel = 'Sin tipo';
                break;
        }
        $tipoBadge = '<span class="badge bg-label-' . $tipoCls . ' rounded-pill">' . htmlspecialchars($tipoLabel) . '</span>';

        $totalEtiquetas = (int) ($row['total_etiquetas'] ?? 0);

        $data[] = array(
            '<span class="fw-semibold">' . $id . '</span>',
            htmlspecialchars($fechaFmt),
            htmlspecialchars($horaFmt),
            htmlspecialchars($usuarioTxt),
            htmlspecialchars($row['nombre_sucursal'] ?: ('#' . (int) ($row['sucursal_etiquetado'] ?? 0))),
            htmlspecialchars($envioHtml),
            '<span class="fw-semibold">' . $totalEtiquetas . '</span>',
            $tipoBadge,
            $id,
        );
    }

    mysqli_stmt_close($stmt);
    mysqli_close($conexion);

    echo json_encode(array(
        'draw' => $draw,
        'recordsTotal' => $recordsTotal,
        'recordsFiltered' => $recordsFiltered,
        'data' => $data,
    ));
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(array('error' => $e->getMessage()));
}
