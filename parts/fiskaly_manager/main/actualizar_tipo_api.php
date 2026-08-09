<?php
require_once '../../../include/session.php';
header('Content-Type: application/json');

try {
    // Verificar que sea una petición POST
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        http_response_code(405);
        echo json_encode([
            'success' => false,
            'message' => 'Método no permitido'
        ]);
        exit;
    }
    
    // Obtener datos del body JSON
    $json = file_get_contents('php://input');
    $data = json_decode($json, true);
    
    if (!$data) {
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'message' => 'Error al decodificar JSON'
        ]);
        exit;
    }
    
    // Validar que se hayan proporcionado los datos necesarios
    $id_empresa = isset($data['id_empresa']) ? (int)$data['id_empresa'] : 0;
    $nuevo_tipo = isset($data['nuevo_tipo']) ? trim($data['nuevo_tipo']) : '';
    $password = isset($data['password']) ? $data['password'] : '';
    
    if (!$id_empresa) {
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'message' => 'ID de empresa es requerido'
        ]);
        exit;
    }
    
    if (empty($nuevo_tipo) || !in_array($nuevo_tipo, ['test', 'produccion'])) {
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'message' => 'Tipo de API inválido. Debe ser "test" o "produccion"'
        ]);
        exit;
    }
    
    if (empty($password)) {
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'message' => 'Contraseña es requerida'
        ]);
        exit;
    }
    
    // Verificar que el usuario actual es root
    if (!isset($_SESSION['usuario_root']) || $_SESSION['usuario_root'] !== 'true') {
        http_response_code(403);
        echo json_encode([
            'success' => false,
            'message' => 'No tienes permisos para realizar esta acción'
        ]);
        exit;
    }
    
    // Verificar la contraseña del usuario root
    $usuario_actual = isset($_SESSION['usuario_nombre']) ? $_SESSION['usuario_nombre'] : '';
    if (empty($usuario_actual)) {
        http_response_code(403);
        echo json_encode([
            'success' => false,
            'message' => 'Usuario no identificado'
        ]);
        exit;
    }
    
    // Conectar a la base de datos y verificar la contraseña
    $conexion = conectar_bd();
    
    $query_usuario = "SELECT password FROM usuarios WHERE usuario = ? AND usuario_root = 'true' AND estado_usuario = 'true' LIMIT 1";
    $stmt_usuario = mysqli_prepare($conexion, $query_usuario);
    
    if (!$stmt_usuario) {
        throw new Exception('Error al preparar consulta de usuario: ' . mysqli_error($conexion));
    }
    
    mysqli_stmt_bind_param($stmt_usuario, 's', $usuario_actual);
    mysqli_stmt_execute($stmt_usuario);
    $result_usuario = mysqli_stmt_get_result($stmt_usuario);
    $row_usuario = mysqli_fetch_assoc($result_usuario);
    mysqli_stmt_close($stmt_usuario);
    
    if (!$row_usuario) {
        mysqli_close($conexion);
        http_response_code(403);
        echo json_encode([
            'success' => false,
            'message' => 'Usuario root no encontrado'
        ]);
        exit;
    }
    
    // Verificar la contraseña
    if (!password_verify($password, $row_usuario['password'])) {
        mysqli_close($conexion);
        http_response_code(403);
        echo json_encode([
            'success' => false,
            'message' => 'Contraseña incorrecta'
        ]);
        exit;
    }
    
    // Actualizar el tipo_api en la tabla empresas
    $query_update = "UPDATE empresas SET tipo_api = ? WHERE id_empresa = ?";
    $stmt_update = mysqli_prepare($conexion, $query_update);
    
    if (!$stmt_update) {
        mysqli_close($conexion);
        throw new Exception('Error al preparar consulta UPDATE: ' . mysqli_error($conexion));
    }
    
    mysqli_stmt_bind_param($stmt_update, 'si', $nuevo_tipo, $id_empresa);
    
    if (!mysqli_stmt_execute($stmt_update)) {
        $error_msg = mysqli_stmt_error($stmt_update);
        mysqli_stmt_close($stmt_update);
        mysqli_close($conexion);
        throw new Exception('Error al actualizar tipo_api: ' . $error_msg);
    }
   
    mysqli_stmt_close($stmt_update);
    mysqli_close($conexion);

    echo json_encode([
        'success' => true,
        'message' => 'Tipo de API actualizado correctamente',
        'nuevo_tipo' => $nuevo_tipo
    ]);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
?>
