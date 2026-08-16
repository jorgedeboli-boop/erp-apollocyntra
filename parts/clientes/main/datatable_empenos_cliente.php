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

    $where = "WHERE l.cliente = ? AND l.compra_opcion = 'si'";
    $params = [$idCliente];
    $types = 'i';

    if ($searchValue !== '') {
        $where .= " AND (l.id_lote LIKE ? OR l.identificador LIKE ? OR l.tipo_de_lote LIKE ? OR l.estado_lote LIKE ?)";
        $like = '%' . $searchValue . '%';
        $params = array_merge($params, [$like, $like, $like, $like]);
        $types .= 'ssss';
    }

    $sqlTotal = "SELECT COUNT(*) AS total FROM lotes_joyeria l {$where}";
    $stTotal = mysqli_prepare($conexion, $sqlTotal);
    mysqli_stmt_bind_param($stTotal, $types, ...$params);
    mysqli_stmt_execute($stTotal);
    $rTotal = mysqli_stmt_get_result($stTotal);
    $recordsFiltered = (int) (mysqli_fetch_assoc($rTotal)['total'] ?? 0);
    mysqli_stmt_close($stTotal);

    $sqlTotalAll = "SELECT COUNT(*) AS total FROM lotes_joyeria l WHERE l.cliente = ? AND l.compra_opcion = 'si'";
    $stAll = mysqli_prepare($conexion, $sqlTotalAll);
    mysqli_stmt_bind_param($stAll, 'i', $idCliente);
    mysqli_stmt_execute($stAll);
    $rAll = mysqli_stmt_get_result($stAll);
    $recordsTotal = (int) (mysqli_fetch_assoc($rAll)['total'] ?? 0);
    mysqli_stmt_close($stAll);

    $sql = "
        SELECT
            l.id_lote,
            l.identificador,
            l.tipo_de_lote,
            l.precio_compra,
            l.fecha_compra,
            l.fecha_vencimiento,
            l.estado_lote
        FROM lotes_joyeria l
        {$where}
        ORDER BY l.fecha_compra DESC
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
        $idLote = (int) ($row['id_lote'] ?? 0);
        $identificador = (string) ($row['identificador'] ?? '');

        $linkId = $identificador !== ''
            ? '<a href="lote.php?id=' . htmlspecialchars($identificador) . '" target="_blank" rel="noopener noreferrer">' . $idLote . '</a>'
            : (string)$idLote;

        $data[] = [
            $linkId,
            htmlspecialchars($identificador),
            htmlspecialchars((string) ($row['tipo_de_lote'] ?? '')),
            number_format((float) ($row['precio_compra'] ?? 0), 0, ',', '.') . ' €',
            !empty($row['fecha_compra']) ? date('d/m/Y H:i', strtotime((string) $row['fecha_compra'])) : 'N/A',
            !empty($row['fecha_vencimiento']) ? date('d/m/Y', strtotime((string) $row['fecha_vencimiento'])) : 'N/A',
            htmlspecialchars((string) ($row['estado_lote'] ?? ''))
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

