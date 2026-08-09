<?php
/**
 * Server-side processing — etiquetas_control_etiquetado por id de control.
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

    $id_control = isset($_POST['id_control_etiquetado']) ? (int) $_POST['id_control_etiquetado'] : 0;
    if ($id_control <= 0) {
        echo json_encode(array(
            'draw' => $draw,
            'recordsTotal' => 0,
            'recordsFiltered' => 0,
            'data' => array(),
            'error' => 'Control de etiquetado no válido',
        ));
        exit;
    }

    $whereConditions = array('ec.rel_id_control_etiquetado = ?');
    $params = array($id_control);
    $types = 'i';

    if ($searchValue !== '') {
        $whereConditions[] = '(
            ec.id_etiqueta LIKE ? OR
            ec.rel_sku_etiquetado LIKE ? OR
            ec.descripcion_sku LIKE ? OR
            ec.tipo_control_etiquetado LIKE ?
        )';
        $searchParam = '%' . $searchValue . '%';
        $params[] = $searchParam;
        $params[] = $searchParam;
        $params[] = $searchParam;
        $params[] = $searchParam;
        $types .= 'ssss';
    }

    $whereClause = 'WHERE ' . implode(' AND ', $whereConditions);
    $from = 'FROM etiquetas_control_etiquetado ec';

    $stmt_total = mysqli_prepare($conexion, "SELECT COUNT(*) AS total $from WHERE ec.rel_id_control_etiquetado = ?");
    mysqli_stmt_bind_param($stmt_total, 'i', $id_control);
    mysqli_stmt_execute($stmt_total);
    $row_total = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt_total));
    $recordsTotal = (int) ($row_total['total'] ?? 0);
    mysqli_stmt_close($stmt_total);

    $query_filtered = "SELECT COUNT(*) AS total $from $whereClause";
    $stmt_filtered = mysqli_prepare($conexion, $query_filtered);
    $mysqli_bind_params($stmt_filtered, $types, $params);
    mysqli_stmt_execute($stmt_filtered);
    $row_filtered = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt_filtered));
    $recordsFiltered = (int) ($row_filtered['total'] ?? 0);
    mysqli_stmt_close($stmt_filtered);

    $query = "
        SELECT
            ec.id_etiqueta,
            ec.rel_id_control_etiquetado,
            ec.rel_sku_etiquetado,
            ec.precio_sku,
            ec.descripcion_sku,
            ec.fecha_control,
            ec.tipo_control_etiquetado
        $from
        $whereClause
        ORDER BY ec.id_etiqueta ASC
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
        $idEtiqueta = (int) ($row['id_etiqueta'] ?? 0);
        $sku = (int) ($row['rel_sku_etiquetado'] ?? 0);

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

        $fecha = $row['fecha_control'] ?? '';
        $fechaFmt = ($fecha !== '' && $fecha !== '0000-00-00') ? date('d/m/Y', strtotime($fecha)) : '-';

        $hrefPrint = 'Impresiones/repetir_impresion.php?id_etiqueta=' . $idEtiqueta;
        $btnPrint = '<a class="btn btn-sm btn-success waves-effect etiqueta-repetir-print-link" target="_blank" data-sku="' . $sku . '" href="'
            . htmlspecialchars($hrefPrint, ENT_QUOTES, 'UTF-8') . '">'
            . '<i class="icon-base ri ri-printer-line me-1"></i>Imprimir</a>';

        $data[] = array(
            (string) $sku,
            htmlspecialchars($row['descripcion_sku'] ?? ''),
            number_format((float) ($row['precio_sku'] ?? 0), 0, ',', '.') . ' €',
            htmlspecialchars($fechaFmt),
            $tipoBadge,
            $btnPrint,
            $idEtiqueta,
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
