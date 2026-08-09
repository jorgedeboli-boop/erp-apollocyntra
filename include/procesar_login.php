<?php
require_once 'functions.php';

// Verificar que la petición sea POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ../login.php');
    exit();
}

// Obtener y limpiar los datos del formulario (compatible con PHP 7.0)
$usuario = isset($_POST['usuario']) ? trim($_POST['usuario']) : '';
$password = isset($_POST['password']) ? $_POST['password'] : '';
$remember_me = isset($_POST['remember_me']);

// Validar que los campos no estén vacíos
if (empty($usuario) || empty($password)) {
    header('Location: ../login.php?error=12');
    exit();
}

$cookie_path = '/';
$cookie_lifetime = 30 * 24 * 60 * 60;
if ($remember_me) {
    setcookie('tpv_remember_usuario', $usuario, time() + $cookie_lifetime, $cookie_path, '', false, true);
} else {
    setcookie('tpv_remember_usuario', '', time() - 3600, $cookie_path, '', false, true);
}

// Verificar las credenciales del usuario
$usuario_data = verificar_usuario($usuario, $password);

if ($usuario_data) {

    // Credenciales correctas, iniciar sesión
    iniciar_sesion($usuario_data);
    
    // Si marcó "recordarme", extender la duración de la sesión
    if ($remember_me) {
        // Extender la sesión a 30 días (compatible con PHP 7.0)
        $lifetime = 30 * 24 * 60 * 60; // 30 días en segundos
        if (function_exists('session_set_cookie_params')) {
            session_set_cookie_params($lifetime);
        }
        $_SESSION['usuario_login_time'] = time();
        $_SESSION['remember_me'] = true;
    }
    
    header('Location: ../dashboard.php');
    exit();
} else {
    // Credenciales incorrectas
    header('Location: ../login.php?error=13');
    exit();
}
?>
