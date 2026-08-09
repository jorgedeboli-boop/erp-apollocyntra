<?php
require_once '../../../include/session.php';
require_once '../../../include/functions.php';

header('Content-Type: application/json');

try {
    $conexion = conectar_bd();
    @mysqli_set_charset($conexion, 'utf8');

    $draw = isset($_POST['draw']) ? (int) $_POST['draw'] : 1;
    $start = isset($_POST['start']) ? (int) $_POST['start'] : 0;
    $length = isset($_POST['length']) ? (int) $_POST['length'] : 10;
    $searchValue = isset($_POST['search']['value']) ? trim((string) $_POST['search']['value']) : '';
    $idCliente = isset($_POST['id_cliente']) ? (int) $_POST['id_cliente'] : 0;

    if ($idCliente <= 0) {
        echo json_encode([
            'draw' => $draw,
            'recordsTotal' => 0,
            'recordsFiltered' => 0,
            'data' => [],
            'error' => 'ID de cliente no válido'
        ]);
        exit;
    }

    if ($start < 0) $start = 0;
    if ($length < 1 || $length > 100) $length = 10;

    $where = "WHERE av.cliente = ?";
    $params = [$idCliente];
    $types = 'i';

    if ($searchValue !== '') {
        $where .= " AND (av.id_venta_sucursal LIKE ? OR s.nombre_sucursal LIKE ? OR av.estado LIKE ? OR av.tipo_pago LIKE ?)";
        $like = '%' . $searchValue . '%';
        $params = array_merge($params, [$like, $like, $like, $like]);
        $types .= 'ssss';
    }

    $sqlTotalAll = "SELECT COUNT(*) AS total FROM ventas av WHERE av.cliente = ?";
    $stAll = mysqli_prepare($conexion, $sqlTotalAll);
    mysqli_stmt_bind_param($stAll, 'i', $idCliente);
    mysqli_stmt_execute($stAll);
    $rAll = mysqli_stmt_get_result($stAll);
    $recordsTotal = (int) (mysqli_fetch_assoc($rAll)['total'] ?? 0);
    mysqli_stmt_close($stAll);

    $sqlTotal = "
        SELECT COUNT(*) AS total
        FROM ventas av
        LEFT JOIN sucursal s ON av.id_sucursal = s.id_sucursal
        {$where}
    ";
    $stTotal = mysqli_prepare($conexion, $sqlTotal);
    mysqli_stmt_bind_param($stTotal, $types, ...$params);
    mysqli_stmt_execute($stTotal);
    $rTotal = mysqli_stmt_get_result($stTotal);
    $recordsFiltered = (int) (mysqli_fetch_assoc($rTotal)['total'] ?? 0);
    mysqli_stmt_close($stTotal);

    $sql = "
        SELECT
            av.id AS identificador_venta,
            av.id_venta_sucursal,
            av.precio,
            av.fecha,
            av.estado,
            av.venta_plazos,
            av.tipo_pago,
            s.nombre_sucursal
        FROM ventas av
        LEFT JOIN sucursal s ON av.id_sucursal = s.id_sucursal
        {$where}
        ORDER BY av.fecha DESC
        LIMIT ? OFFSET ?
    ";

    $params2 = array_merge($params, [$length, $start]);
    $types2 = $types . 'ii';
    $st = mysqli_prepare($conexion, $sql);
    mysqli_stmt_bind_param($st, $types2, ...$params2);
    mysqli_stmt_execute($st);
    $rs = mysqli_stmt_get_result($st);

    $data = [];
    while ($row = mysqli_fetch_assoc($rs)) {
        $idVenta = (int) ($row['identificador_venta'] ?? 0);
        $nVenta = htmlspecialchars((string) ($row['id_venta_sucursal'] ?? ''));

        $linkFicha = $idVenta > 0
            ? '<a href="venta.php?id=' . $idVenta . '" target="_blank" rel="noopener noreferrer">' . $nVenta . '</a>'
            : $nVenta;

        $plazos = (($row['venta_plazos'] ?? '') === 'si')
            ? '<span class="badge bg-label-success">Sí</span>'
            : '<span class="badge bg-label-secondary">No</span>';

        $pago = !empty($row['tipo_pago'])
            ? '<span class="badge bg-label-primary">' . htmlspecialchars((string) $row['tipo_pago']) . '</span>'
            : '<span class="text-muted">-</span>';

        $estado = !empty($row['estado'])
            ? '<span class="badge bg-label-info">' . htmlspecialchars((string) $row['estado']) . '</span>'
            : '<span class="text-muted">-</span>';

        $data[] = [
            $linkFicha,
            number_format((float) ($row['precio'] ?? 0), 0, ',', '.') . ' €',
            !empty($row['fecha']) ? date('d/m/Y H:i', strtotime((string) $row['fecha'])) : 'N/A',
            htmlspecialchars((string) ($row['nombre_sucursal'] ?? '')),
            $estado,
            $plazos,
            $pago,
            $idVenta
        ];
    }

    mysqli_stmt_close($st);
    mysqli_close($conexion);

    echo json_encode([
        'draw' => $draw,
        'recordsTotal' => $recordsTotal,
        'recordsFiltered' => $recordsFiltered,
        'data' => $data
    ]);
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}

