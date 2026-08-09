<?php
require_once '../../../include/session.php';
require_once '../../../include/functions.php';

// Iniciar buffer de salida para evitar espacios en blanco
ob_start();

// Configuración básica
error_reporting(0);
ini_set('display_errors', 0);

// Headers necesarios
header('Content-Type: application/json');
header('Cache-Control: no-cache, must-revalidate');

try {
    // Verificar que sea una petición GET
    if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
        http_response_code(405);
        throw new Exception('Método no permitido');
    }
    
    // Obtener ID del usuario
    $id_usuario = isset($_GET['id']) ? (int)$_GET['id'] : 0;
    
    if (!$id_usuario) {
        http_response_code(400);
        throw new Exception('ID de usuario no válido');
    }
    
    // Conectar BD
    $conexion = conectar_bd();
    
    // Consulta para obtener el estado de conexión del usuario
    $query_conexion = "
        SELECT 
            uc.state_connection
        FROM usersConexions uc
        WHERE uc.userId = ?
        ORDER BY uc.idUserConexion DESC
        LIMIT 1
    ";
    
    $stmt_conexion = mysqli_prepare($conexion, $query_conexion);
    if (!$stmt_conexion) {
        throw new Exception('Error en la preparación de la consulta: ' . mysqli_error($conexion));
    }
    
    mysqli_stmt_bind_param($stmt_conexion, 'i', $id_usuario);
    mysqli_stmt_execute($stmt_conexion);
    $result_conexion = mysqli_stmt_get_result($stmt_conexion);
    
    if (!$result_conexion) {
        throw new Exception('Error en la ejecución de la consulta: ' . mysqli_stmt_error($stmt_conexion));
    }
    
    $estado_conexion = 'false';
    if (mysqli_num_rows($result_conexion) > 0) {
        $conexion_data = mysqli_fetch_assoc($result_conexion);
        $estado_conexion = $conexion_data['state_connection'];
    } else {
        // Si no hay resultados, el usuario está desconectado
        $estado_conexion = 'false';
    }
    
    mysqli_stmt_close($stmt_conexion);
    mysqli_close($conexion);
    
    // Limpiar cualquier salida previa
    ob_clean();
    
    // Respuesta de éxito
    $response = array(
        'success' => true,
        'estado_conexion' => $estado_conexion,
        'estado_texto' => ($estado_conexion == 'true') ? 'Conectado' : 'Desconectado'
    );
    
    echo json_encode($response);
    
} catch (Exception $e) {
    // Limpiar cualquier salida previa
    ob_clean();
    
    http_response_code(500);
    echo json_encode(array(
        'success' => false,
        'error' => $e->getMessage()
    ));
}

// Asegurar que no haya más salida
exit;
?>
