<?php
/**
 * Versión simplificada de load_list.php para pruebas
 */

require_once '../../../include/session.php';
require_once '../../../include/functions.php';

// Verificar que el usuario tenga permisos para ver usuarios
if (!puede_acceder_a('usuarios')) {
    http_response_code(403);
    echo json_encode(['error' => 'Acceso denegado']);
    exit;
}

// Configurar headers para AJAX
header('Content-Type: application/json');

try {
    // Conectar a la base de datos
    $conexion = conectar_bd();
    
    // Consulta simple para obtener usuarios
    $query = "
        SELECT 
            u.id_usuario,
            u.usuario,
            u.nombre_usuario,
            u.apellido_usuario,
            u.email,
            u.estado_usuario,
            u.telefono_usuario,
            u.sucursal_usuario,
            u.privilegio_usuario,
            u.ultimo_acceso,
            COALESCE(p.nombre_privilegio, 'Sin privilegio') as nombre_privilegio
        FROM usuarios u
        LEFT JOIN privilegios_usuarios p ON u.privilegio_usuario = p.id_privilegios
        ORDER BY u.id_usuario ASC
        LIMIT 10
    ";
    
    $result = mysqli_query($conexion, $query);
    
    if (!$result) {
        throw new Exception("Error en consulta: " . mysqli_error($conexion));
    }
    
    // Preparar datos para DataTables
    $data = [];
    while ($row = mysqli_fetch_assoc($result)) {
        // Generar avatar con iniciales
        $iniciales = strtoupper(substr($row['nombre_usuario'], 0, 1) . substr($row['apellido_usuario'], 0, 1));
        $estado_num = $row['estado_usuario'] === 'true' ? 2 : 3; // 2=Active, 3=Inactive
        
        $data[] = [
            '', // Columna de control (vacía)
            $row['id_usuario'],
            [
                'full_name' => $row['nombre_usuario'] . ' ' . $row['apellido_usuario'],
                'email' => $row['email'],
                'avatar' => null,
                'initials' => $iniciales
            ],
            $row['nombre_privilegio'],
            'Sucursal ' . $row['sucursal_usuario'],
            $estado_num,
            $row['ultimo_acceso'] ? date('d/m/Y H:i', strtotime($row['ultimo_acceso'])) : 'Nunca',
            [
                'id' => $row['id_usuario'],
                'full_name' => $row['nombre_usuario'] . ' ' . $row['apellido_usuario'],
                'email' => $row['email'],
                'role' => $row['nombre_privilegio'],
                'status' => $row['estado_usuario']
            ]
        ];
    }
    
    // Contar total de registros
    $count_query = "SELECT COUNT(*) as total FROM usuarios";
    $count_result = mysqli_query($conexion, $count_query);
    $total_records = mysqli_fetch_assoc($count_result)['total'];
    
    // Respuesta para DataTables
    $response = [
        'draw' => isset($_POST['draw']) ? (int)$_POST['draw'] : 1,
        'recordsTotal' => $total_records,
        'recordsFiltered' => $total_records,
        'data' => $data
    ];
    
    // Debug: Log de información
    error_log("load_list_simple.php - Total registros: $total_records, Datos encontrados: " . count($data));
    
    echo json_encode($response);
    
} catch (Exception $e) {
    // Log del error
    error_log("Error en load_list_simple.php: " . $e->getMessage());
    
    // Respuesta de error
    http_response_code(500);
    echo json_encode([
        'error' => 'Error interno del servidor: ' . $e->getMessage(),
        'draw' => isset($_POST['draw']) ? (int)$_POST['draw'] : 1,
        'recordsTotal' => 0,
        'recordsFiltered' => 0,
        'data' => []
    ]);
}

mysqli_close($conexion);
?>
