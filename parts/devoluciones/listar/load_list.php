<?php
/**
 * Server-side processing para DataTable de devoluciones
 */

require_once '../../../include/session.php';
require_once '../../../include/functions.php';

header('Content-Type: application/json; charset=utf-8');

$draw = isset($_POST['draw']) ? (int) $_POST['draw'] : 1;

function devoluciones_json($payload)
{
    $flags = JSON_UNESCAPED_UNICODE;
    if (defined('JSON_INVALID_UTF8_SUBSTITUTE')) {
        $flags |= JSON_INVALID_UTF8_SUBSTITUTE;
    }
    echo json_encode($payload, $flags);
}

try {
    $conexion = conectar_bd();

    $start = isset($_POST['start']) ? (int) $_POST['start'] : 0;
    $length = isset($_POST['length']) ? (int) $_POST['length'] : 10;
    if ($length < 1) {
        $length = 25;
    }
    if ($start < 0) {
        $start = 0;
    }
    $searchValue = isset($_POST['search']['value']) ? trim($_POST['search']['value']) : '';

    $whereConditions = array();
    $params = array();
    $types = '';

    if ($searchValue !== '') {
        $whereConditions[] = "(
            CAST(av.id_devolucion AS CHAR) LIKE ? OR
            CAST(av.id_venta_original AS CHAR) LIKE ? OR
            CAST(av.fecha_devolucion AS CHAR) LIKE ? OR
            CAST(av.cliente_devolucion AS CHAR) LIKE ? OR
            av.motivo_devolucion LIKE ? OR
            CAST(av.importe_devolucion AS CHAR) LIKE ? OR
            av.forma_de_pago_devolucion LIKE ? OR
            CONCAT(IFNULL(clientes.nombre, ''), ' ', IFNULL(clientes.apellido, '')) LIKE ? OR
            CAST(av.articulo_devolucion AS CHAR) LIKE ?
        )";
        $searchParam = '%' . $searchValue . '%';
        for ($i = 0; $i < 9; $i++) {
            $params[] = $searchParam;
        }
        $types .= 'sssssssss';
    }

    $whereClause = count($whereConditions) > 0 ? 'WHERE ' . implode(' AND ', $whereConditions) : '';

    $queryBase = "
        FROM devoluciones AS av
        LEFT JOIN clientes ON av.cliente_devolucion = clientes.id_cliente
        $whereClause
    ";

    $query_total = 'SELECT COUNT(*) as total FROM devoluciones';
    $result_total = mysqli_query($conexion, $query_total);
    if (!$result_total) {
        throw new Exception('Error al contar devoluciones: ' . mysqli_error($conexion));
    }
    $row_total = mysqli_fetch_assoc($result_total);
    $recordsTotal = isset($row_total['total']) ? (int) $row_total['total'] : 0;

    $query_filtered = 'SELECT COUNT(*) as total ' . $queryBase;
    if ($types !== '') {
        $stmt_filtered = mysqli_prepare($conexion, $query_filtered);
        if (!$stmt_filtered) {
            throw new Exception('Error al preparar el conteo: ' . mysqli_error($conexion));
        }
        mysqli_stmt_bind_param($stmt_filtered, $types, ...$params);
        mysqli_stmt_execute($stmt_filtered);
        $result_filtered = mysqli_stmt_get_result($stmt_filtered);
        if (!$result_filtered) {
            mysqli_stmt_close($stmt_filtered);
            throw new Exception('Error al contar filtrados: ' . mysqli_error($conexion));
        }
        $row_filtered = mysqli_fetch_assoc($result_filtered);
        $recordsFiltered = isset($row_filtered['total']) ? (int) $row_filtered['total'] : 0;
        mysqli_stmt_close($stmt_filtered);
    } else {
        $result_filtered = mysqli_query($conexion, $query_filtered);
        if (!$result_filtered) {
            throw new Exception('Error al contar filtrados: ' . mysqli_error($conexion));
        }
        $row_filtered = mysqli_fetch_assoc($result_filtered);
        $recordsFiltered = isset($row_filtered['total']) ? (int) $row_filtered['total'] : 0;
    }

    $orderColumn = isset($_POST['order'][0]['column']) ? (int) $_POST['order'][0]['column'] : 2;
    $orderDir = isset($_POST['order'][0]['dir']) ? $_POST['order'][0]['dir'] : 'desc';

    $columnMap = [
        0 => 'av.id_devolucion',
        1 => 'av.id_venta_original',
        2 => 'av.fecha_devolucion',
        3 => 'CLIENTEDATA',
        4 => 'av.motivo_devolucion',
        5 => 'av.articulo_devolucion',
        6 => 'av.articulo_devolucion',
        7 => 'av.importe_devolucion',
        8 => 'av.forma_de_pago_devolucion',
        9 => 'av.devolucion_web'
    ];
    $allowedColumns = array_values($columnMap);
    $orderBy = isset($columnMap[$orderColumn]) ? $columnMap[$orderColumn] : 'av.fecha_devolucion';
    if (!in_array($orderBy, $allowedColumns, true)) {
        $orderBy = 'av.fecha_devolucion';
    }
    $orderDir = strtoupper($orderDir) === 'ASC' ? 'ASC' : 'DESC';

    $query = "SELECT
                av.id_devolucion,
                av.id_venta_original,
                av.fecha_devolucion,
                av.cliente_devolucion,
                av.motivo_devolucion,
                av.articulo_devolucion,
                av.importe_devolucion,
                av.forma_de_pago_devolucion,
                av.devolucion_web,
                CONCAT(IFNULL(clientes.nombre, ''), ' ', IFNULL(clientes.apellido, '')) AS CLIENTEDATA,
                av.articulo_devolucion AS SKUARTICULO
              " . $queryBase . "
              ORDER BY $orderBy $orderDir
              LIMIT ? OFFSET ?";

    $params[] = $length;
    $params[] = $start;
    $types .= 'ii';

    $stmt = mysqli_prepare($conexion, $query);
    if (!$stmt) {
        throw new Exception('Error al preparar la consulta: ' . mysqli_error($conexion));
    }

    mysqli_stmt_bind_param($stmt, $types, ...$params);

    if (!mysqli_stmt_execute($stmt)) {
        $err = mysqli_stmt_error($stmt);
        mysqli_stmt_close($stmt);
        throw new Exception('Error al ejecutar la consulta: ' . $err);
    }

    $result = mysqli_stmt_get_result($stmt);
    if (!$result) {
        mysqli_stmt_close($stmt);
        throw new Exception('Error al obtener el resultado: ' . mysqli_error($conexion));
    }

    $data = array();
    while ($row = mysqli_fetch_assoc($result)) {
        $importe = isset($row['importe_devolucion']) ? (float) $row['importe_devolucion'] : 0;
        $cliente = trim((string) ($row['CLIENTEDATA'] ?? ''));
        $data[] = [
            $row['id_devolucion'],
            $row['id_venta_original'],
            $row['fecha_devolucion'],
            $cliente !== '' ? $cliente : '-',
            $row['motivo_devolucion'] ?: '-',
            $row['SKUARTICULO'] ?: '-',
            '-',
            number_format($importe, 2, ',', '.') . ' €',
            $row['forma_de_pago_devolucion'] ?: '-',
            $row['devolucion_web'] ?: '-'
        ];
    }
    mysqli_stmt_close($stmt);

    devoluciones_json([
        'draw' => $draw,
        'recordsTotal' => $recordsTotal,
        'recordsFiltered' => $recordsFiltered,
        'data' => $data
    ]);
} catch (Throwable $e) {
    if (ob_get_length()) {
        ob_clean();
    }
    header('Content-Type: application/json; charset=utf-8');
    devoluciones_json([
        'draw' => $draw,
        'recordsTotal' => 0,
        'recordsFiltered' => 0,
        'data' => [],
        'error' => 'Error al cargar los datos: ' . $e->getMessage()
    ]);
}
