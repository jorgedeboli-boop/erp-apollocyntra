<?php
// Verificar que el usuario esté autenticado
require_once '../../../include/session.php';

// Verificar que sea una petición POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Método no permitido']);
    exit();
}

// Incluir funciones de base de datos
require_once '../../../include/functions.php';

// Función para validar datos
function validarDatos($datos) {
    $errores = [];
    
    // Validar campos requeridos
    $campos_requeridos = [
        'nombre_usuario', 'apellido_usuario', 'email_usuario', 
        'usuario_login', 'password_usuario', 'confirmar_password',
        'privilegio_usuario', 'sucursal_usuario', 'estado_usuario'
    ];
    
    foreach ($campos_requeridos as $campo) {
        if (empty(trim(isset($datos[$campo]) ? $datos[$campo] : ''))) {
            $errores[] = "El campo " . str_replace('_', ' ', $campo) . " es requerido";
        }
    }
    
    // Validar email
    if (!empty($datos['email_usuario']) && !filter_var($datos['email_usuario'], FILTER_VALIDATE_EMAIL)) {
        $errores[] = "El email no tiene un formato válido";
    }
    
    // Validar contraseñas
    if (!empty($datos['password_usuario']) && strlen($datos['password_usuario']) < 8) {
        $errores[] = "La contraseña debe tener al menos 8 caracteres";
    }
    
    if ($datos['password_usuario'] !== $datos['confirmar_password']) {
        $errores[] = "Las contraseñas no coinciden";
    }
    
    // Validar usuario único
    if (!empty($datos['usuario_login']) && strlen($datos['usuario_login']) < 3) {
        $errores[] = "El usuario debe tener al menos 3 caracteres";
    }
    
    return $errores;
}

// Función para verificar si el usuario ya existe
function usuarioExiste($usuario_login, $email) {
    $conexion = conectar_bd();
    
    $stmt = mysqli_prepare($conexion, "
        SELECT id_usuario FROM usuarios 
        WHERE usuario = ? OR email = ?
    ");
    
    mysqli_stmt_bind_param($stmt, "ss", $usuario_login, $email);
    mysqli_stmt_execute($stmt);
    
    // Compatible con PHP 7.0 y versiones anteriores de MySQL
    mysqli_stmt_store_result($stmt);
    $existe = mysqli_stmt_num_rows($stmt) > 0;
    
    mysqli_stmt_close($stmt);
    mysqli_close($conexion);
    
    return $existe;
}

// Función para crear el usuario
function crearUsuario($datos) {
    $conexion = conectar_bd();
    
    // Hash de la contraseña
    $password_hash = password_hash($datos['password_usuario'], PASSWORD_DEFAULT);
    
    // Log para debug
    error_log("Datos a insertar: " . print_r($datos, true));
    
    $stmt = mysqli_prepare($conexion, "
        INSERT INTO usuarios (
            usuario, password, nombre_usuario, apellido_usuario, 
            email, estado_usuario, telefono_usuario, sucursal_usuario, 
            privilegio_usuario, observaciones_usuario, fecAlta
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())
    ");
    
    if (!$stmt) {
        error_log("Error en prepare: " . mysqli_error($conexion));
        return false;
    }
    
    // Preparar valores para bind_param (compatible con PHP 7.0)
    $telefono = isset($datos['telefono_usuario']) ? $datos['telefono_usuario'] : '';
    $observaciones = isset($datos['observaciones_usuario']) ? $datos['observaciones_usuario'] : '';
    
    mysqli_stmt_bind_param($stmt, "ssssssssss", 
        $datos['usuario_login'],
        $password_hash,
        $datos['nombre_usuario'],
        $datos['apellido_usuario'],
        $datos['email_usuario'],
        $datos['estado_usuario'],
        $telefono,
        $datos['sucursal_usuario'],
        $datos['privilegio_usuario'],
        $observaciones
    );
    
    $resultado = mysqli_stmt_execute($stmt);
    
    if (!$resultado) {
        error_log("Error en execute: " . mysqli_stmt_error($stmt));
        mysqli_stmt_close($stmt);
        mysqli_close($conexion);
        return false;
    }
    
    $usuario_id = mysqli_insert_id($conexion);
    
    mysqli_stmt_close($stmt);
    mysqli_close($conexion);
    
    return $resultado ? $usuario_id : false;
}

// Función para enviar email con credenciales
function enviarCredencialesEmail($datos, $password_original) {
    // Aquí implementarías el envío de email
    // Por ahora solo retornamos true
    return true;
}

// Procesar el formulario
try {
    // Obtener y limpiar datos
    $datos = [
        'nombre_usuario' => trim(isset($_POST['nombre_usuario']) ? $_POST['nombre_usuario'] : ''),
        'apellido_usuario' => trim(isset($_POST['apellido_usuario']) ? $_POST['apellido_usuario'] : ''),
        'email_usuario' => trim(isset($_POST['email_usuario']) ? $_POST['email_usuario'] : ''),
        'telefono_usuario' => trim(isset($_POST['telefono_usuario']) ? $_POST['telefono_usuario'] : ''),
        'usuario_login' => trim(isset($_POST['usuario_login']) ? $_POST['usuario_login'] : ''),
        'password_usuario' => isset($_POST['password_usuario']) ? $_POST['password_usuario'] : '',
        'confirmar_password' => isset($_POST['confirmar_password']) ? $_POST['confirmar_password'] : '',
        'privilegio_usuario' => trim(isset($_POST['privilegio_usuario']) ? $_POST['privilegio_usuario'] : ''),
        'sucursal_usuario' => trim(isset($_POST['sucursal_usuario']) ? $_POST['sucursal_usuario'] : ''),
        'estado_usuario' => trim(isset($_POST['estado_usuario']) ? $_POST['estado_usuario'] : ''),
        'observaciones_usuario' => trim(isset($_POST['observaciones_usuario']) ? $_POST['observaciones_usuario'] : ''),
        'enviar_credenciales' => isset($_POST['enviar_credenciales']),
        'forzar_cambio_password' => isset($_POST['forzar_cambio_password'])
    ];
    
    // Validar datos
    $errores = validarDatos($datos);
    
    if (!empty($errores)) {
        echo json_encode([
            'success' => false, 
            'message' => 'Errores de validación: ' . implode(', ', $errores)
        ]);
        exit();
    }
    
    // Verificar si el usuario ya existe
    if (usuarioExiste($datos['usuario_login'], $datos['email_usuario'])) {
        echo json_encode([
            'success' => false, 
            'message' => 'El usuario o email ya existe en el sistema'
        ]);
        exit();
    }
    
    // Crear el usuario
    $usuario_id = crearUsuario($datos);
    
    if (!$usuario_id) {
        echo json_encode([
            'success' => false, 
            'message' => 'Error al crear el usuario en la base de datos'
        ]);
        exit();
    }
    
    // Enviar credenciales por email si se solicitó
    if ($datos['enviar_credenciales']) {
        enviarCredencialesEmail($datos, $datos['password_usuario']);
    }
    
    // Registrar la acción en el log
    $texto_action_user = "$usuario creó el usuario Nº '$usuario_id'";
    $id_action_user = "34";
    $relItemAction = $_SESSION['relItemAction'];
    registrar_accion_usuario($usuario_id, $id_action_user, $texto_action_user, $usuario_sucursal, $relItemAction, '../../../usuarios.php');
    $_SESSION['relItemAction'] = "false";
    
    // Respuesta exitosa
    echo json_encode([
        'success' => true,
        'message' => 'Usuario creado exitosamente',
        'usuario_id' => $usuario_id,
        'redirect' => '../../../usuarios.php'
    ]);
    
} catch (Exception $e) {
    // Log del error
    error_log("Error al crear usuario: " . $e->getMessage());
    
    echo json_encode([
        'success' => false, 
        'message' => 'Error interno del servidor. Contacte al administrador.'
    ]);
}

?>
