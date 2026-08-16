<?php
require_once '../../../include/session.php';
require_once '../../../include/functions.php';

// Verificar autenticación
if (!usuario_autenticado()) {
    http_response_code(401);
    echo json_encode(['error' => 'No autorizado']);
    exit;
}

header('Content-Type: application/json');

try {
    // Conectar BD
    $conexion = conectar_bd();
    
    // Obtener parámetros (búsqueda global + filtros de columnas)
    $searchValue = isset($_POST['search']) ? trim($_POST['search']) : '';
    $filtroEstado = isset($_POST['filtro_estado']) ? trim($_POST['filtro_estado']) : '';
    $filtroTipoIdentificacion = isset($_POST['filtro_tipo_identificacion']) ? trim($_POST['filtro_tipo_identificacion']) : '';
    $filtroProvincia = isset($_POST['filtro_provincia']) ? trim($_POST['filtro_provincia']) : '';
    
    $joinDireccion = "
        LEFT JOIN (
            SELECT rel_id_item,
                   MAX(c_provincia) AS c_provincia,
                   MAX(rel_id_provincia) AS rel_id_provincia
            FROM direcciones
            WHERE type_direccion = 'clientes'
            GROUP BY rel_id_item
        ) d ON d.rel_id_item = c.id_cliente
    ";

    // Construir condiciones de búsqueda
    $whereConditions = [];
    $searchParams = [];
    
    if (!empty($searchValue)) {
        $whereConditions[] = "(c.id_cliente = ? OR c.nombre LIKE ? OR c.apellido LIKE ? OR CONCAT(c.nombre, ' ', c.apellido) LIKE ? OR c.tipo_identificacion LIKE ? OR c.identificacion LIKE ? OR c.nacionalidad LIKE ? OR c.telefono LIKE ? OR d.c_provincia LIKE ?)";
        $searchTerm = "%$searchValue%";
        $searchParams = [$searchValue, $searchTerm, $searchTerm, $searchTerm, $searchTerm, $searchTerm, $searchTerm, $searchTerm, $searchTerm];
    }
    
    if (!empty($filtroEstado)) {
        if ($filtroEstado === 'Habilitado') {
            $whereConditions[] = "c.estado = 'habilitado'";
        } elseif ($filtroEstado === 'Deshabilitado') {
            $whereConditions[] = "c.estado = 'deshabilitado'";
        }
    }

    if (!empty($filtroTipoIdentificacion)) {
        $filtroTipoUpper = strtoupper($filtroTipoIdentificacion);
        $whereConditions[] = "(
            UPPER(TRIM(c.tipo_identificacion)) = ?
            OR (
                (c.tipo_identificacion IS NULL OR TRIM(c.tipo_identificacion) = '')
                AND c.tipo_identificacion_id IN (
                    SELECT id_tipo_identificacion FROM tipo_identificacion WHERE UPPER(nombre_identificacion) = ?
                )
            )
        )";
        $searchParams[] = $filtroTipoUpper;
        $searchParams[] = $filtroTipoUpper;
    }

    if (!empty($filtroProvincia)) {
        $filtroProvinciaId = (int)$filtroProvincia;
        if ($filtroProvinciaId > 0) {
            $whereConditions[] = "(
                d.rel_id_provincia = ?
                OR UPPER(TRIM(d.c_provincia)) = (
                    SELECT UPPER(nombreProvince) FROM provincias WHERE id_province = ? LIMIT 1
                )
            )";
            $searchParams[] = $filtroProvinciaId;
            $searchParams[] = $filtroProvinciaId;
        }
    }
    
    $whereConditions[] = "c.delete_state = 'false'";
    $whereClause = !empty($whereConditions) ? 'WHERE ' . implode(' AND ', $whereConditions) : '';
    
    // Consulta SIN LIMIT para obtener TODOS los registros
    $query = "
        SELECT 
            c.id_cliente, 
            c.nombre, 
            c.apellido, 
            c.tipo_identificacion,
            c.tipo_identificacion_id,
            c.identificacion,
            c.nacionalidad,
            c.nacionalidad_id,
            c.telefono, 
            c.estado, 
            c.f_alta,
            d.c_provincia,
            d.rel_id_provincia
        FROM clientes c
        $joinDireccion
        $whereClause
        ORDER BY c.f_alta DESC
    ";
    
    if (!empty($searchParams)) {
        $stmt = mysqli_prepare($conexion, $query);
        if ($stmt) {
            $types = str_repeat('s', count($searchParams));
            mysqli_stmt_bind_param($stmt, $types, ...$searchParams);
            mysqli_stmt_execute($stmt);
            $result = mysqli_stmt_get_result($stmt);
        } else {
            throw new Exception("Error en preparación de consulta: " . mysqli_error($conexion));
        }
    } else {
        $result = mysqli_query($conexion, $query);
    }
    
    if (!$result) {
        throw new Exception("Error en consulta: " . mysqli_error($conexion));
    }
    
    // Obtener TODOS los datos
    $data = [];
    while ($row = mysqli_fetch_assoc($result)) {
        $tipoIdentificacionTexto = !empty($row['tipo_identificacion'])
            ? $row['tipo_identificacion']
            : '';
        if ($tipoIdentificacionTexto === '' && !empty($row['tipo_identificacion_id'])) {
            $tipoIdentificacionTexto = obtenerTextoTipoIdentificacion($conexion, (int)$row['tipo_identificacion_id']);
        }
        if ($tipoIdentificacionTexto === '') {
            $tipoIdentificacionTexto = 'Sin tipo';
        }
        $tipoIdentificacionTexto = strtoupper($tipoIdentificacionTexto);

        $nacionalidadTexto = !empty($row['nacionalidad']) ? $row['nacionalidad'] : '';
        if ($nacionalidadTexto === '' && !empty($row['nacionalidad_id'])) {
            $nombreNacionalidad = obtener_nombre_nacionalidad((int)$row['nacionalidad_id']);
            $nacionalidadTexto = $nombreNacionalidad !== false ? $nombreNacionalidad : '';
        }
        if ($nacionalidadTexto === '') {
            $nacionalidadTexto = 'Sin nacionalidad';
        }

        $provinciaTexto = !empty($row['c_provincia']) ? trim($row['c_provincia']) : '';
        if ($provinciaTexto === '' && !empty($row['rel_id_provincia'])) {
            $provinciaTexto = obtenerTextoProvincia($conexion, (int)$row['rel_id_provincia']);
        }
        if ($provinciaTexto === '') {
            $provinciaTexto = 'Sin provincia';
        }

        $data[] = [
            $row['id_cliente'],
            $row['nombre'] . ' ' . $row['apellido'],
            $tipoIdentificacionTexto,
            isset($row['identificacion']) && $row['identificacion'] ? $row['identificacion'] : 'Sin número',
            $nacionalidadTexto,
            isset($row['telefono']) && $row['telefono'] ? $row['telefono'] : 'Sin teléfono',
            $provinciaTexto,
            $row['estado'] === 'habilitado' ? 'Habilitado' : 'Deshabilitado',
            date('d/m/Y', strtotime($row['f_alta']))
        ];
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
?>
