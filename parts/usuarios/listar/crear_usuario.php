<?php
/**
 * Archivo para crear nuevos usuarios via AJAX
 */

require_once '../../../../include/session.php';
require_once '../../../../include/functions.php';

// Verificar que el usuario tenga permisos para crear usuarios
if (!puede_acceder_a('usuarios')) {
    http_response_code(403);
    echo json_encode(['success' => false, 'error' => 'Acceso denegado']);
    exit;
}

// Verificar que sea una petición POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Método no permitido']);
    exit;
}

// Configurar headers para AJAX
header('Content-Type: application/json');

try {
    // Obtener y validar datos del formulario
    $username = isset($_POST['username']) ? trim($_POST['username']) : '';
    $nombre = isset($_POST['nombre']) ? trim($_POST['nombre']) : '';
    $apellido = isset($_POST['apellido']) ? trim($_POST['apellido']) : '';
    $email = isset($_POST['email']) ? trim($_POST['email']) : '';
    $password = isset($_POST['password']) ? $_POST['password'] : '';
    $telefono = isset($_POST['telefono']) ? trim($_POST['telefono']) : '';
    $sucursal = isset($_POST['sucursal']) ? (int)$_POST['sucursal'] : 1;
    $privilegio = isset($_POST['privilegio']) ? (int)$_POST['privilegio'] : 0;
    
    // Validar campos requeridos
    if (empty($username) || empty($nombre) || empty($apellido) || 
        empty($email) || empty($password) || empty($privilegio)) {
        echo json_encode(['success' => false, 'error' => 'Todos los campos son requeridos']);
        exit;
    }
    
    // Validar formato de email
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        echo json_encode(['success' => false, 'error' => 'Formato de email inválido']);
        exit;
    }
    
    // Validar longitud de contraseña
    if (strlen($password) < 6) {
        echo json_encode(['success' => false, 'error' => 'La contraseña debe tener al menos 6 caracteres']);
        exit;
    }
    
    // Conectar a la base de datos
    $conexion = conectar_bd();
    
    // Verificar si el username ya existe
    $stmt_check = mysqli_prepare($conexion, "SELECT id_usuario FROM usuarios WHERE usuario = ?");
    mysqli_stmt_bind_param($stmt_check, 's', $username);
    mysqli_stmt_execute($stmt_check);
    $result_check = mysqli_stmt_get_result($stmt_check);
    
    if (mysqli_num_rows($result_check) > 0) {
        echo json_encode(['success' => false, 'error' => 'El nombre de usuario ya existe']);
        exit;
    }
    
    // Verificar si el email ya existe
    $stmt_check_email = mysqli_prepare($conexion, "SELECT id_usuario FROM usuarios WHERE email = ?");
    mysqli_stmt_bind_param($stmt_check_email, 's', $email);
    mysqli_stmt_execute($stmt_check_email);
    $result_check_email = mysqli_stmt_get_result($stmt_check_email);
    
    if (mysqli_num_rows($result_check_email) > 0) {
        echo json_encode(['success' => false, 'error' => 'El email ya está registrado']);
        exit;
    }
    
    // Hash de la contraseña
    $password_hash = password_hash($password, PASSWORD_DEFAULT);
    
    // Insertar nuevo usuario
    $stmt_insert = mysqli_prepare($conexion, "
        INSERT INTO usuarios (
            usuario, 
            password, 
            nombre_usuario, 
            apellido_usuario, 
            email, 
            telefono_usuario, 
            sucursal_usuario, 
            privilegio_usuario, 
            estado_usuario, 
            fecha_creacion
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'true', NOW())
    ");
    
    mysqli_stmt_bind_param($stmt_insert, 'ssssssii', 
        $username, 
        $password_hash, 
        $nombre, 
        $apellido, 
        $email, 
        $telefono, 
        $sucursal, 
        $privilegio
    );
    
    if (mysqli_stmt_execute($stmt_insert)) {
        $nuevo_id = mysqli_insert_id($conexion);
        
        // Respuesta de éxito
        echo json_encode([
            'success' => true, 
            'message' => 'Usuario creado exitosamente',
            'user_id' => $nuevo_id
        ]);
        
        // Log de la acción (opcional)
        error_log("Usuario creado: $username por usuario ID: " . $_SESSION['usuario_id']);
        
    } else {
        echo json_encode(['success' => false, 'error' => 'Error al crear usuario en la base de datos']);
    }
    
} catch (Exception $e) {
    // Log del error
    error_log("Error en crear_usuario.php: " . $e->getMessage());
    
    // Respuesta de error
    echo json_encode(['success' => false, 'error' => 'Error interno del servidor']);
}

mysqli_close($conexion);
?>
