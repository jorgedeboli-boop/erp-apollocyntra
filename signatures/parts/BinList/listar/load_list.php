<?php
require_once '../../../include/session.php';
require_once '../../../include/functions.php';

// Verificar que el usuario esté autenticado
if (!isset($_SESSION['usuario_id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'No autorizado']);
    exit;
}

// Parámetros de DataTables
$draw = isset($_POST['draw']) ? intval($_POST['draw']) : 1;
$start = isset($_POST['start']) ? intval($_POST['start']) : 0;
$length = isset($_POST['length']) ? intval($_POST['length']) : 10;
$search = isset($_POST['search']['value']) ? trim($_POST['search']['value']) : '';

try {
    $conexion = conectar_bd();
    
    if (!$conexion) {
        throw new Exception('Error de conexión a la base de datos');
    }
    
    // Construir la consulta base
    $query_base = "FROM BinList b
                   LEFT JOIN itemsSections its ON b.id_type_item_rel = its.id_type_Item
                   WHERE 1=1";
    
    $params = [];
    $types = '';
    
    // Búsqueda global
    if (!empty($search)) {
        $query_base .= " AND (b.itemId LIKE ? OR b.userDelete LIKE ? OR b.descriptionBin LIKE ? OR its.itemnameText LIKE ?)";
        $search_param = "%$search%";
        $params[] = $search_param;
        $params[] = $search_param;
        $params[] = $search_param;
        $params[] = $search_param;
        $types .= 'ssss';
    }
    
    // Contar total de registros
    $query_count = "SELECT COUNT(*) as total " . $query_base;
    $stmt_count = mysqli_prepare($conexion, $query_count);
    
    if (!empty($params)) {
        mysqli_stmt_bind_param($stmt_count, $types, ...$params);
    }
    
    mysqli_stmt_execute($stmt_count);
    $result_count = mysqli_stmt_get_result($stmt_count);
    $total_records = mysqli_fetch_assoc($result_count)['total'];
    mysqli_stmt_close($stmt_count);
    
    // Consulta principal con paginación
    $query_main = "SELECT 
                        b.idBinList,
                        b.itemId,
                        b.dateDeleted,
                        b.hour_delete,
                        b.userDelete,
                        b.descriptionBin,
                        b.id_type_item_rel,
                        its.itemnameText
                    " . $query_base . " 
                    ORDER BY b.idBinList DESC 
                    LIMIT ?, ?";
    
    // Agregar parámetros de paginación
    $params[] = $start;
    $params[] = $length;
    $types .= 'ii';
    
    $stmt_main = mysqli_prepare($conexion, $query_main);
    
    if (!empty($params)) {
        mysqli_stmt_bind_param($stmt_main, $types, ...$params);
    }
    
    mysqli_stmt_execute($stmt_main);
    $result_main = mysqli_stmt_get_result($stmt_main);
    
    $data = [];
    while ($row = mysqli_fetch_assoc($result_main)) {
        // Formatear fecha y hora en formato España
        $fecha_hora = '';
        if (!empty($row['dateDeleted']) && !empty($row['hour_delete'])) {
            $fecha = date('d/m/Y', strtotime($row['dateDeleted']));
            $hora = substr($row['hour_delete'], 0, 5); // HH:MM
            $fecha_hora = $fecha . ' - ' . $hora;
        }
        
        $data[] = [
            $row['idBinList'],
            $row['itemId'],
            $row['itemnameText'] ? $row['itemnameText'] : 'N/A',
            $fecha_hora,
            $row['userDelete'],
            $row['descriptionBin'],
            '', // Acciones se renderizan en el JavaScript
            $row['id_type_item_rel'] // ID del tipo de item
        ];
    }
    
    mysqli_stmt_close($stmt_main);
    
    // Respuesta para DataTables
    $response = [
        'draw' => $draw,
        'recordsTotal' => $total_records,
        'recordsFiltered' => $total_records,
        'data' => $data
    ];
    
    echo json_encode($response);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'error' => $e->getMessage(),
        'draw' => $draw,
        'recordsTotal' => 0,
        'recordsFiltered' => 0,
        'data' => []
    ]);
}

if (isset($conexion)) {
    mysqli_close($conexion);
}
?>
