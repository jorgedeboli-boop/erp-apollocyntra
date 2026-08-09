<?php
// Versión minimal de load_list.php
ob_clean();

require_once '../../../include/session.php';
require_once '../../../include/functions.php';

header('Content-Type: application/json');

try {
    // Verificar permisos
    if (!puede_acceder_a('usuarios')) {
        http_response_code(403);
        echo json_encode(['error' => 'Acceso denegado']);
        exit;
    }
    
    // Conectar BD
    $conexion = conectar_bd();
    
    // Consulta simple
    $query = "SELECT id_usuario, nombre_usuario, apellido_usuario, email, estado_usuario, privilegio_usuario FROM usuarios ORDER BY id_usuario ASC LIMIT 10";
    $result = mysqli_query($conexion, $query);
    
    if (!$result) {
        throw new Exception("Error en consulta: " . mysqli_error($conexion));
    }
    
    // Datos simples
    $data = [];
    while ($row = mysqli_fetch_assoc($result)) {
        $estado_num = $row['estado_usuario'] === 'true' ? 2 : 3;
        
        $data[] = [
            '', // Control
            $row['id_usuario'], // ID
            $row['nombre_usuario'] . ' ' . $row['apellido_usuario'], // Nombre
            $row['email'], // Email
            'Privilegio ' . $row['privilegio_usuario'], // Privilegio
            'Basic', // Plan
            $estado_num, // Estado
            'Acciones' // Acciones
        ];
    }
    
    // Respuesta
    $response = [
        'draw' => 1,
        'recordsTotal' => count($data),
        'recordsFiltered' => count($data),
        'data' => $data
    ];
    
    echo json_encode($response);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'error' => $e->getMessage(),
        'draw' => 1,
        'recordsTotal' => 0,
        'recordsFiltered' => 0,
        'data' => []
    ]);
}

mysqli_close($conexion);
?>
