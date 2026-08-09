<?php
// Incluir archivos necesarios
require_once '../../../include/session.php';
require_once '../../../include/functions.php';

// Verificar que el usuario esté autenticado
if (!isset($_SESSION['usuario_id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'No autorizado']);
    exit;
}

// Verificar que sea una petición AJAX
if (!isset($_SERVER['HTTP_X_REQUESTED_WITH']) || strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) != 'xmlhttprequest') {
    http_response_code(400);
    echo json_encode(['error' => 'Petición inválida']);
    exit;
}

try {
    // Conectar a la base de datos
    $conexion = conectar_bd();
    
    if (!$conexion) {
        throw new Exception('Error de conexión a la base de datos');
    }
    
    // Consulta para obtener privilegios con cantidad de usuarios
    $query = "
        SELECT
            p.id_privilegios,
            p.nombre_privilegio,
            COUNT(u.id_usuario) AS total_usuarios
        FROM privilegios_usuarios p
        LEFT JOIN usuarios u ON u.privilegio_usuario = p.id_privilegios
            AND COALESCE(u.usuario_root, 'false') <> 'true'
        GROUP BY p.id_privilegios, p.nombre_privilegio
        ORDER BY p.nombre_privilegio ASC
    ";
    $resultado = mysqli_query($conexion, $query);
    
    if (!$resultado) {
        throw new Exception('Error en la consulta: ' . mysqli_error($conexion));
    }
    
    $privilegios = [];
    
    while ($fila = mysqli_fetch_assoc($resultado)) {
        $privilegios[] = [
            'id_privilegios' => (int) $fila['id_privilegios'],
            'nombre_privilegio' => $fila['nombre_privilegio'],
            'total_usuarios' => (int) ($fila['total_usuarios'] ?? 0),
        ];
    }
    
    // Cerrar conexión
    mysqli_close($conexion);
    
    // Devolver respuesta JSON
    header('Content-Type: application/json');
    echo json_encode([
        'success' => true,
        'data' => $privilegios,
        'total' => count($privilegios)
    ]);
    
} catch (Exception $e) {
    // En caso de error
    if (isset($conexion)) {
        mysqli_close($conexion);
    }
    
    http_response_code(500);
    echo json_encode([
        'error' => 'Error del servidor: ' . $e->getMessage()
    ]);
}
?>
