<?php
/**
 * Archivo de manejo avanzado de sesiones
 * Incluye verificación automática y redirección
 * Compatible con PHP 7.0
 */

// Verificar si ya se ha incluido
if (defined('SESSION_HANDLER_LOADED')) {
    return;
}

define('SESSION_HANDLER_LOADED', true);

// Incluir archivos necesarios
require_once 'session.php';
require_once 'functions.php';

// Función para manejar la expiración de sesión
function manejar_expiracion_sesion() {
    // Verificar si la sesión ha expirado
    if (!usuario_autenticado()) {
        // Si es una petición AJAX, devolver JSON
        if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && 
            strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') {
            
            header('Content-Type: application/json');
            echo json_encode(array(
                'session_expired' => true,
                'redirect_url' => 'login.php',
                'message' => 'Sesión expirada'
            ));
            exit;
        }
        
        // Si es una petición normal, redirigir
        header('Location: login.php');
        exit;
    }
    
    // Verificar si la sesión está por expirar (30 minutos antes)
    $tiempo_transcurrido = time() - $_SESSION['usuario_login_time'];
    $tiempo_restante = SESSION_LIFETIME - $tiempo_transcurrido;
    
    if ($tiempo_restante <= (30 * 60)) { // 30 minutos o menos
        // Agregar clase CSS para indicar sesión por expirar
        if (!defined('SESSION_WARNING_ADDED')) {
            define('SESSION_WARNING_ADDED', true);
            echo '<style>
                .session-warning {
                    position: fixed;
                    top: 20px;
                    right: 20px;
                    background: #ffc107;
                    color: #000;
                    padding: 15px;
                    border-radius: 5px;
                    box-shadow: 0 4px 6px rgba(0,0,0,0.1);
                    z-index: 9999;
                    font-weight: bold;
                }
                .session-warning .countdown {
                    font-size: 1.2em;
                    margin: 10px 0;
                }
                .session-warning .actions {
                    display: flex;
                    gap: 10px;
                    margin-top: 10px;
                }
                .session-warning button {
                    padding: 8px 16px;
                    border: none;
                    border-radius: 4px;
                    cursor: pointer;
                    font-size: 14px;
                }
                .session-warning .extend-btn {
                    background: #28a745;
                    color: white;
                }
                .session-warning .logout-btn {
                    background: #dc3545;
                    color: white;
                }
            </style>';
            
            // Mostrar advertencia visual
            $minutos = floor($tiempo_restante / 60);
            $segundos = $tiempo_restante % 60;
            
            echo '<div class="session-warning" id="sessionWarning">
                <div>⚠️ Sesión por expirar</div>
                <div class="countdown">Tiempo restante: <span id="sessionCountdown">' . $minutos . ':' . str_pad($segundos, 2, '0', STR_PAD_LEFT) . '</span></div>
                <div class="actions">
                    <button class="extend-btn" onclick="extendSession()">Extender sesión</button>
                    <button class="logout-btn" onclick="logoutNow()">Cerrar sesión</button>
                </div>
            </div>';
            
            // Script para el countdown
            echo '<script>
                let sessionCountdown = ' . $tiempo_restante . ';
                const countdownElement = document.getElementById("sessionCountdown");
                const warningElement = document.getElementById("sessionWarning");
                
                const countdownTimer = setInterval(() => {
                    sessionCountdown--;
                    const minutes = Math.floor(sessionCountdown / 60);
                    const seconds = sessionCountdown % 60;
                    countdownElement.textContent = minutes + ":" + (seconds < 10 ? "0" : "") + seconds;
                    
                    if (sessionCountdown <= 0) {
                        clearInterval(countdownTimer);
                        window.location.href = "login.php";
                    }
                }, 1000);
                
                function extendSession() {
                    fetch("include/extend_session.php", {
                        method: "POST",
                        headers: { "Content-Type": "application/json" }
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            warningElement.style.display = "none";
                            clearInterval(countdownTimer);
                            // Recargar la página para actualizar el estado
                            window.location.reload();
                        }
                    })
                    .catch(error => {
                        console.error("Error al extender sesión:", error);
                    });
                }
                
                function logoutNow() {
                    clearInterval(countdownTimer);
                    window.location.href = "logout.php";
                }
            </script>';
        }
    }
}

// Función para verificar sesión en peticiones AJAX
function verificar_sesion_ajax() {
    if (!usuario_autenticado()) {
        header('Content-Type: application/json');
        http_response_code(401);
        echo json_encode(array(
            'error' => 'Sesión expirada',
            'redirect_url' => 'login.php'
        ));
        exit;
    }
}

// Función para obtener información de la sesión
function obtener_info_sesion() {
    return array(
        'usuario_id' => $_SESSION['usuario_id'] ?? null,
        'usuario_nombre' => $_SESSION['usuario_nombre'] ?? null,
        'usuario_sucursal' => $_SESSION['usuario_sucursal'] ?? null,
        'usuario_privilegio' => $_SESSION['usuario_privilegio_id'] ?? null,
        'login_time' => $_SESSION['usuario_login_time'] ?? null,
        'tiempo_restante' => SESSION_LIFETIME - (time() - ($_SESSION['usuario_login_time'] ?? time())),
        'sesion_por_expirar' => (SESSION_LIFETIME - (time() - ($_SESSION['usuario_login_time'] ?? time()))) <= (30 * 60)
    );
}

// Ejecutar verificación automática si no es una petición AJAX
if (empty($_SERVER['HTTP_X_REQUESTED_WITH']) || 
    strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) != 'xmlhttprequest') {
    manejar_expiracion_sesion();
}
?>
