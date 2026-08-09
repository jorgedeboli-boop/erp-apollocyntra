<?php
require_once '../../../include/session.php';
//require_once '../../../include/functions.php';
/**
 * Archivo para cargar la lista de clientes via AJAX
 * Versión optimizada que funciona correctamente
 * Compatible con PHP 7.0+
 */

// Verificar versión de PHP
if (version_compare(PHP_VERSION, '7.0.0', '<')) {
    http_response_code(500);
    echo json_encode(['error' => 'Se requiere PHP 7.0 o superior']);
    exit;
}

// Asegurar que no haya salida antes del JSON
ob_clean();






header('Content-Type: application/json');

try {
    // Conectar BD
    $conexion = conectar_bd();
    
    // Obtener parámetros de DataTables
    $start = isset($_POST['start']) ? (int)$_POST['start'] : 0;
    $length = isset($_POST['length']) ? (int)$_POST['length'] : 25;
    $searchValue = isset($_POST['search']['value']) ? trim($_POST['search']['value']) : '';
    
    // Parámetros de ordenamiento
    $orderColumn = isset($_POST['order'][0]['column']) ? (int)$_POST['order'][0]['column'] : 9; // Por defecto ordenar por fecha
    $orderDir = isset($_POST['order'][0]['dir']) ? $_POST['order'][0]['dir'] : 'desc';
    
    // Filtros personalizados de columnas
    $filtroSucursal = isset($_POST['filtro_sucursal']) ? trim($_POST['filtro_sucursal']) : '';
    $filtroEstado = isset($_POST['filtro_estado']) ? trim($_POST['filtro_estado']) : '';
    $filtroTipoIdentificacion = isset($_POST['filtro_tipo_identificacion']) ? trim($_POST['filtro_tipo_identificacion']) : '';
    $filtroProvincia = isset($_POST['filtro_provincia']) ? trim($_POST['filtro_provincia']) : '';
    

    
    // Validar parámetros
    if ($start < 0) $start = 0;
    if ($length < 1 || $length > 100) $length = 25;
    
    // Mapeo de columnas para ordenamiento
    $columnMap = [
        0 => 'c.id_cliente',
        1 => 'c.nombre',
        2 => 'c.tipo_identificacion',
        3 => 'c.identificacion',
        4 => 'c.nacionalidad',
        5 => 'c.telefono',
        6 => 'd.c_provincia',
        7 => 's.nombre_sucursal',
        8 => 'c.estado',
        9 => 'c.f_alta'
    ];
    
    // Validar columna de ordenamiento
    if (!isset($columnMap[$orderColumn])) {
        $orderColumn = 9; // Por defecto ordenar por fecha
    }
    
    $orderBy = $columnMap[$orderColumn];
    $orderDirection = strtoupper($orderDir) === 'ASC' ? 'ASC' : 'DESC';
    
    // Construir condiciones de búsqueda
    $whereConditions = [];
    $searchParams = [];
    
    if (!empty($searchValue)) {
        $whereConditions[] = "(c.id_cliente = ? OR c.nombre LIKE ? OR c.apellido LIKE ? OR CONCAT(c.nombre, ' ', c.apellido) LIKE ? OR c.tipo_identificacion LIKE ? OR c.identificacion LIKE ? OR c.nacionalidad LIKE ? OR c.telefono LIKE ? OR d.c_provincia LIKE ? OR s.nombre_sucursal LIKE ?)";
        $searchTerm = "%$searchValue%";
        $searchParams = [$searchValue, $searchTerm, $searchTerm, $searchTerm, $searchTerm, $searchTerm, $searchTerm, $searchTerm, $searchTerm, $searchTerm];
    }
    
    // Agregar filtros de columna personalizados
    if (!empty($filtroSucursal) && $filtroSucursal !== 'Sin sucursal') {
        $whereConditions[] = "s.nombre_sucursal = ?";
        $searchParams[] = $filtroSucursal;
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

    $joinDireccion = "
        LEFT JOIN sucursal s ON c.sucursal = s.id_sucursal
        LEFT JOIN (
            SELECT rel_id_item,
                   MAX(c_provincia) AS c_provincia,
                   MAX(rel_id_provincia) AS rel_id_provincia
            FROM direcciones
            WHERE type_direccion = 'clientes'
            GROUP BY rel_id_item
        ) d ON d.rel_id_item = c.id_cliente
    ";
    $selectProvincia = "d.c_provincia, d.rel_id_provincia";

    $whereConditions[] = "c.delete_state = 'false'";
    $whereClause = !empty($whereConditions) ? 'WHERE ' . implode(' AND ', $whereConditions) : '';

    // Consulta para contar total de registros filtrados
    $queryCount = "SELECT COUNT(*) as total FROM clientes c $joinDireccion $whereClause";
    
    // Variable para almacenar el total de registros filtrados
    $totalFiltrados = 0;
    
    if (!empty($searchParams)) {
        $stmtCount = mysqli_prepare($conexion, $queryCount);
        if ($stmtCount) {
            $types = str_repeat('s', count($searchParams));
            mysqli_stmt_bind_param($stmtCount, $types, ...$searchParams);
            mysqli_stmt_execute($stmtCount);
            $resultCount = mysqli_stmt_get_result($stmtCount);
        } else {
            throw new Exception("Error en preparación de consulta de conteo: " . mysqli_error($conexion));
        }
    } else {
        $resultCount = mysqli_query($conexion, $queryCount);
    }
    
    if (!$resultCount) {
        throw new Exception("Error en consulta de conteo: " . mysqli_error($conexion));
    }
    $rowCount = mysqli_fetch_assoc($resultCount);
    $totalRegistros = (int)$rowCount['total'];
    $totalFiltrados = $totalRegistros; // Total de registros que coinciden con los filtros
    

    
    // Consulta principal con paginación y filtros
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
            c.sucursal,
            $selectProvincia,
            COALESCE(s.nombre_sucursal, 'Sin sucursal') as nombre_sucursal
        FROM clientes c
        $joinDireccion
        $whereClause
        ORDER BY $orderBy $orderDirection
        LIMIT $start, $length
    ";
    

    
    if (!empty($searchParams)) {
        $stmt = mysqli_prepare($conexion, $query);
        if ($stmt) {
            $types = str_repeat('s', count($searchParams));
            mysqli_stmt_bind_param($stmt, $types, ...$searchParams);
            mysqli_stmt_execute($stmt);
            $result = mysqli_stmt_get_result($stmt);
        } else {
            throw new Exception("Error en preparación de consulta principal: " . mysqli_error($conexion));
        }
    } else {
        $result = mysqli_query($conexion, $query);
    }
    
    if (!$result) {
        throw new Exception("Error en consulta: " . mysqli_error($conexion));
    }
    

    
    // Datos simples
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
        } elseif ($provinciaTexto !== 'Sin provincia') {
            $provinciaTexto = mb_strtoupper(mb_substr($provinciaTexto, 0, 1, 'UTF-8'), 'UTF-8')
                . mb_strtolower(mb_substr($provinciaTexto, 1, null, 'UTF-8'), 'UTF-8');
        }

        $data[] = [
            $row['id_cliente'], // ID
            [
                'full_name' => $row['nombre'] . ' ' . $row['apellido'],
                'id_cliente' => $row['id_cliente']
            ], // Nombre completo + ID
            $tipoIdentificacionTexto, // Tipo Identificación
            isset($row['identificacion']) && $row['identificacion'] ? $row['identificacion'] : 'Sin número', // Número Identificación
            $nacionalidadTexto, // Nacionalidad
            isset($row['telefono']) && $row['telefono'] ? $row['telefono'] : 'Sin teléfono', // Teléfono
            $provinciaTexto, // Provincia
            $row['nombre_sucursal'], // Sucursal
            $row['estado'] === 'habilitado' ? 'Habilitado' : 'Deshabilitado', // Estado formateado
            date('d/m/Y', strtotime($row['f_alta'])) // Fecha de alta
        ];
    }
    
    // Respuesta para serverSide
    $response = [
        'draw' => isset($_POST['draw']) ? (int)$_POST['draw'] : 1,
        'recordsTotal' => $totalRegistros, // Total de registros en la tabla
        'recordsFiltered' => $totalFiltrados, // Total de registros después de filtros (sin paginación)
        'data' => $data
    ];
    
    // Debug: Log de la respuesta
    error_log("Respuesta enviada - draw: {$response['draw']}, total: {$response['recordsTotal']}, filtrados: {$response['recordsFiltered']}, datos: " . count($response['data']));
    
    echo json_encode($response);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'error' => $e->getMessage(),
        'draw' => isset($_POST['draw']) ? (int)$_POST['draw'] : 1,
        'recordsTotal' => 0,
        'recordsFiltered' => 0,
        'data' => []
    ]);
}

mysqli_close($conexion);
?>
