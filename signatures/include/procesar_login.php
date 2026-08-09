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

    if( $usuario_data['sucursal_section'] == 'true'){
        if (controlarNewSistemaCaja($usuario_data['sucursal_usuario'])) {
            // Verificar si la caja está cerrada
            if (controlarCajaCerrada($usuario_data['sucursal_usuario'])) {
                if (fechaCajaCerrada($usuario_data['sucursal_usuario'])) {
                    header('Location: ../login.php?cajaCerrada=1');
                    exit();
                }
            }
        }
    }
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
    
    // Redirigir al dashboard (formación APP_ID 444: reanudar último paso del wizard si aplica)
    if ($usuario_data['sucursal_section'] == 'true') {

        $destino = '../dashboard_sucursal.php';
        require_once __DIR__ . '/formacion_wizard.php';
        if (formacion_wizard_activo()) {
            require_once __DIR__ . '/formacion_wizard_login.php';
            $wiz = formacion_wizard_url_tras_login((int) ($_SESSION['usuario_id'] ?? 0));
            if ($wiz !== null && $wiz !== '') {
                $destino = '../' . $wiz;
            }
        }
        header('Location: ' . $destino);

    }elseif ($usuario_data['central_section'] == 'true') {

        $destino = '../dashboard.php';
        header('Location: ' . $destino);

    }elseif ($usuario_data['recepcion_lotes_section'] == 'true') {

        $destino = '../dashboard_recepcion_lotes.php';
        header('Location: ' . $destino);

    }elseif ($usuario_data['auditoria_section'] == 'true') {

        $destino = '../dashboard_auditorias.php';
        header('Location: ' . $destino);
        
    }
    /*
    if ($usuario_data['sucursal_section'] == 'true') {
        $destino = '../dashboard_sucursal.php';
        require_once __DIR__ . '/formacion_wizard.php';
        if (formacion_wizard_activo()) {
            require_once __DIR__ . '/formacion_wizard_login.php';
            $wiz = formacion_wizard_url_tras_login((int) ($_SESSION['usuario_id'] ?? 0));
            if ($wiz !== null && $wiz !== '') {
                $destino = '../' . $wiz;
            }
        }
        header('Location: ' . $destino);
    } else {
        if($usuario_data['privilegio_usuario'] == 8){
            header('Location: ../dashboard_auditorias.php');
        }elseif($usuario_data['privilegio_usuario'] == 17){
            header('Location: ../dashboard_recepcion_lotes.php');
        }else{
            header('Location: ../dashboard.php');
        }
    }
    */
    exit();
} else {
    // Credenciales incorrectas
    header('Location: ../login.php?error=13');
    exit();
}
?>
