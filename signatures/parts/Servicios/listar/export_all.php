<?php
require_once '../../../include/session.php';
require_once '../../../include/functions.php';

header('Content-Type: application/json');

function servicios_label_unidad_export($v)
{
    $map = ['hora' => 'Hora', 'media_hora' => 'Media hora', 'dia' => 'Día', 'sesion' => 'Sesión'];
    return $map[$v] ?? $v;
}

function servicios_label_tipo_fact_export($v)
{
    $map = ['por_hora' => 'Por hora', 'precio_fijo' => 'Precio fijo', 'por_sesion' => 'Por sesión'];
    return $map[$v] ?? $v;
}

try {
    $conexion = conectar_bd();
    $searchValue = isset($_POST['search']) ? trim($_POST['search']) : '';
    $filtro_empresa = isset($_POST['filtro_empresa']) ? trim($_POST['filtro_empresa']) : '';
    $filtro_activo = isset($_POST['filtro_activo']) ? trim($_POST['filtro_activo']) : '';
    $filtro_tipo_fact = isset($_POST['filtro_tipo_fact']) ? trim($_POST['filtro_tipo_fact']) : '';

    $whereConditions = [];
    $params = [];
    $types = '';

    if ($filtro_empresa !== '') {
        $whereConditions[] = 's.rel_id_empresa = ?';
        $params[] = (int)$filtro_empresa;
        $types .= 'i';
    }
    if ($filtro_activo !== '') {
        $whereConditions[] = 's.activo = ?';
        $params[] = (int)$filtro_activo;
        $types .= 'i';
    }
    if ($filtro_tipo_fact !== '') {
        $whereConditions[] = 's.tipo_facturacion = ?';
        $params[] = $filtro_tipo_fact;
        $types .= 's';
    }
    if (!empty($searchValue)) {
        $whereConditions[] = '(
            CAST(s.id AS CHAR) LIKE ? OR
            s.codigo LIKE ? OR
            s.nombre LIKE ? OR
            s.descripcion LIKE ? OR
            e.nombre_empresa LIKE ?
        )';
        $searchParam = '%' . $searchValue . '%';
        for ($i = 0; $i < 5; $i++) {
            $params[] = $searchParam;
        }
        $types .= 'sssss';
    }

    $whereClause = count($whereConditions) ? 'WHERE ' . implode(' AND ', $whereConditions) : '';

    $query = "
        SELECT
            s.id,
            s.codigo,
            s.nombre,
            e.nombre_empresa,
            c.nombre_categoria,
            s.activo,
            s.tipo_facturacion,
            s.precio_hora,
            s.precio_fijo,
            s.porcentaje_iva,
            s.unidad_tiempo,
            s.fecha_modificacion
        FROM servicios s
        LEFT JOIN empresas e ON s.rel_id_empresa = e.id_empresa
        LEFT JOIN categorias c ON s.id_categoria = c.id_categoria
        $whereClause
        ORDER BY s.id DESC
    ";

    if (!empty($types)) {
        $stmt = mysqli_prepare($conexion, $query);
        mysqli_stmt_bind_param($stmt, $types, ...$params);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
    } else {
        $result = mysqli_query($conexion, $query);
    }

    $data = [];
    while ($row = mysqli_fetch_assoc($result)) {
        $data[] = [
            $row['id'],
            $row['codigo'],
            $row['nombre'],
            $row['nombre_empresa'] ?? '',
            $row['nombre_categoria'] ?? '',
            ((int)$row['activo'] === 1) ? 'Sí' : 'No',
            servicios_label_tipo_fact_export($row['tipo_facturacion'] ?? ''),
            number_format((float)$row['precio_hora'], 2, ',', '.') . ' €',
            number_format((float)$row['precio_fijo'], 2, ',', '.') . ' €',
            number_format((float)$row['porcentaje_iva'], 2, ',', '.') . ' %',
            servicios_label_unidad_export($row['unidad_tiempo'] ?? ''),
            !empty($row['fecha_modificacion']) ? date('d/m/Y H:i', strtotime($row['fecha_modificacion'])) : '',
        ];
    }

    if (!empty($types)) {
        mysqli_stmt_close($stmt);
    }
    mysqli_close($conexion);

    echo json_encode(['success' => true, 'data' => $data]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
