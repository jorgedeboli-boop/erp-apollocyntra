<?php
// OBTENGO LA IP DEL VISITANTE MEDIANTE DIFERENTES CONSTANTES 
function getRealIP()
{
    if (isset($_SERVER["HTTP_CLIENT_IP"]))
    {
        return $_SERVER["HTTP_CLIENT_IP"];
    }
    elseif (isset($_SERVER["HTTP_X_FORWARDED_FOR"]))
    {
        return $_SERVER["HTTP_X_FORWARDED_FOR"];
    }
    elseif (isset($_SERVER["HTTP_X_FORWARDED"]))
    {
        return $_SERVER["HTTP_X_FORWARDED"];
    }
    elseif (isset($_SERVER["HTTP_FORWARDED_FOR"]))
    {
        return $_SERVER["HTTP_FORWARDED_FOR"];
    }
    elseif (isset($_SERVER["HTTP_FORWARDED"]))
    {
        return $_SERVER["HTTP_FORWARDED"];
    }
    else
    {
        return $_SERVER["REMOTE_ADDR"];
    }
}
$ipvVisitante = getRealIP();

define("ipNumberUser", $_SERVER['REMOTE_ADDR']);
define("timeSesionOpen", "300000"); // ES EL VALOR DEL TIEMPO ABIERTO DE LA SESION EN MILISEGUNDOS A 30 MINUTOS
$ipNumberUser = ipNumberUser;
define("userAgent", $_SERVER['HTTP_USER_AGENT']);
$userAgent = userAgent;

$url = 'https://' . $_SERVER['HTTP_HOST'];

$url_completa = 'https://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];

$url_actual = $_SERVER['REQUEST_URI'];

function soloUri($uri) {
    $path = parse_url($uri, PHP_URL_PATH);
    
    // Si parse_url falla, usar la URI completa
    if ($path === false || $path === null) {
        $path = $uri;
    }
    
    return pathinfo($path, PATHINFO_FILENAME);
}

$uri_actual_limpia =  soloUri($url_actual); 

define('ENVIRONMENT', 'development');

/**
 * Interruptor global de los scripts en CRON/.
 * true: ejecución normal. false: los crons salen sin ejecutar nada.
 */
$crons_state = false;

function environment_is_production()
{
    return defined('ENVIRONMENT') && ENVIRONMENT === 'production';
}

define('FORMACION_WIZARD_ENABLED', false);

// Configuración de la base de datos
define('DB_HOST', 'vl24696.dinaserver.com');
define('DB_NAME', 'erp_apoll_app');
define('DB_USER', 'apoll_78923');
define('DB_PASS', 'Soul@7891');

// Configuración de la aplicación
define('APP_URL', $url);
define('APP_ID', '222');

// APP_NAME: obtener_datos_app() está en functions.php; si aún no está cargado, se define al final de ese archivo.
if (function_exists('obtener_datos_app') && !defined('APP_NAME')) {
    $app_data = obtener_datos_app(APP_ID);
    $app_name = ($app_data && !empty($app_data['name_app'])) ? $app_data['name_app'] : 'ERP Apollo Cyntra';
    define('APP_NAME', $app_name);
}

// Configuración de sesión (compatible con PHP 7.0)
define('SESSION_NAME', 'erp_apollo_cyntra_session');
define('SESSION_LIFETIME', 8 * 60 * 60); // 8 horas en segundos

// Timezone (compatible con PHP 7.0)
if (function_exists('date_default_timezone_set')) {
    date_default_timezone_set('Europe/Madrid');
}

// CONECTO CON bbdd fiskaly TEST
/*
define('HOSTFISKALY', 'vl24696.dinaserver.com');
define('USERFISKALY', 'quint_fisklyusr');
define('PASSWORDFISKALY', 'Soul@7891');
define('DATABASEFISKALY', 'quint_fisklay');
$mysqli_fiskalyapp_test = new mysqli(HOSTFISKALY, USERFISKALY, PASSWORDFISKALY, DATABASEFISKALY);
$acentosFISKALY_fiskalyapp_test = $mysqli_fiskalyapp_test->query("SET NAMES 'utf8'");

$url_api_fiskaly_test = 'https://test.es.sign.fiskaly.com/api/v1/';
/*
// CONECTO CON bbdd fiskaly PRODUCCION — solo en production
define('HOSTFISKALYPROD', 'vl24696.dinaserver.com');
define('USERFISKALYPROD', 'quint_fis_prod');
define('PASSWORDFISKALYPROD', 'Soul@7891');
define('DATABASEFISKALYPROD', 'quint_fisklay_production');
$mysqli_fiskalyapp_production = null;
$url_api_fiskaly_production = 'https://live.es.sign.fiskaly.com/api/v1/';
if (environment_is_production()) {
    $mysqli_fiskalyapp_production = new mysqli(HOSTFISKALYPROD, USERFISKALYPROD, PASSWORDFISKALYPROD, DATABASEFISKALYPROD);
    if ($mysqli_fiskalyapp_production->connect_errno) {
        $mysqli_fiskalyapp_production = null;
    } else {
        $mysqli_fiskalyapp_production->query("SET NAMES 'utf8'");
    }
}
*/


/**
 * Conexión Fiskaly producción (mysqli). null si ENVIRONMENT !== 'production' o falla la conexión.
 */
function get_mysqli_fiskalyapp_production()
{
    global $mysqli_fiskalyapp_production;
    if (!environment_is_production()) {
        return null;
    }
    if (!($mysqli_fiskalyapp_production instanceof mysqli) || $mysqli_fiskalyapp_production->connect_errno) {
        return null;
    }
    return $mysqli_fiskalyapp_production;
}


// DEFINO LOS NUMEROS DE ERRORES POR INICIO DE SESIÓN FALLIDO
$groupIdusersConexions_Empresa_sin_acceso = 58;
$groupIdusersConexions_Cierre_de_sesion = 57;
$groupIdusersConexions_Usuario_eliminado = 56;
$groupIdusersConexions_Usuario_no_jerarquia = 88;
$groupIdusersConexions_Desconectado = 55;
$groupIdusersConexions_Usuario_bloqueado = 54;
$groupIdusersConexions_login_correcto = 52;
$groupIdusersConexions_Login_Fallido = 53;
$groupIdusersConexions_recupero_pass_ok = 62;
$groupIdusersConexions_recupero_pass_bad = 63;
$groupIdusersConexions_recupero_pass_user_block = 64;
$groupIdusersConexions_recupero_pass_user_inexist = 65;

/**
 * Remitente para PHP mail(): dominio autorizado (SPF/DKIM). No usar subdominio de la app (p. ej. tpv.*).
 * El envío real (SMTPS/STARTTLS hacia apollocyntra-app.correoseguro.dinaserver.com, puertos 465/587)
 * se configura en el panel del hosting / sendmail del servidor; aquí solo se usa mail().
 */
$email_envios = 'noreply@apollocyntra.app';

if (!defined('MAIL_FROM_ADDRESS')) {
    define('MAIL_FROM_ADDRESS', $email_envios);
}

$server_photos = $url."/";

define('FTP_HOST', 'apollocyntra-com.espacioseguro.com');
define('FTP_USER', 'filestemperp');
define('FTP_PASS', 'Soul@7891');
define('FTP_REMOTE_DIR', '/');
define('FTP_PORT', 21);

// Claves API (no subir a Git; configurar en servidor vía FTP)
define('GEMINI_API_KEY', 'AQ.Ab8RN6J0icexlLvFEGxS5lvOhaEF_8jsYK_yQVlOzUpVYkiQcA');
define('ANTHROPIC_API_KEY', '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');
?>