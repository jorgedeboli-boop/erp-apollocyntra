<?php
/**
 * Archivo de manejo de sesiones para TPV Quinta Gracia
 * Incluye variables de sesión y configuración básica
 */

// Incluir funciones necesarias
require_once 'functions.php';

// Verificar que el usuario esté autenticado
requerir_autenticacion();

// Verificar que la app esté activa
verificar_app_activa();

// Obtener información del usuario de la sesión (compatible con PHP 7.0)
$usuario_id = isset($_SESSION['usuario_id']) ? $_SESSION['usuario_id'] : '';
$usuario_nombre = isset($_SESSION['usuario_nombre']) ? $_SESSION['usuario_nombre'] : '';
$usuario_nombre_completo = isset($_SESSION['usuario_nombre_completo']) ? $_SESSION['usuario_nombre_completo'] : '';
$usuario_email = isset($_SESSION['usuario_email']) ? $_SESSION['usuario_email'] : '';
$usuario_estado = isset($_SESSION['usuario_estado']) ? $_SESSION['usuario_estado'] : '';
$usuario_telefono = isset($_SESSION['usuario_telefono']) ? $_SESSION['usuario_telefono'] : '';
$usuario_sucursal = isset($_SESSION['usuario_sucursal']) ? $_SESSION['usuario_sucursal'] : '';
$usuario_sucursal_nombre = isset($_SESSION['usuario_sucursal_nombre']) ? $_SESSION['usuario_sucursal_nombre'] : '';
$usuario_privilegio_id = isset($_SESSION['usuario_privilegio_id']) ? $_SESSION['usuario_privilegio_id'] : '';
$usuario_privilegio_nombre = isset($_SESSION['usuario_privilegio_nombre']) ? $_SESSION['usuario_privilegio_nombre'] : '';
$usuario_observaciones = isset($_SESSION['usuario_observaciones']) ? $_SESSION['usuario_observaciones'] : '';
$usuario_ultimo_acceso = isset($_SESSION['usuario_ultimo_acceso']) ? $_SESSION['usuario_ultimo_acceso'] : '';
$usuario_root = isset($_SESSION['usuario_root']) ? $_SESSION['usuario_root'] : '';
$usuario_super_administrador = isset($_SESSION['usuario_super_administrador']) ? $_SESSION['usuario_super_administrador'] : '';
$usuario_acceso_ia = isset($_SESSION['usuario_acceso_ia']) ? $_SESSION['usuario_acceso_ia'] : '';
$usuario = isset($_SESSION['usuario']) ? $_SESSION['usuario'] : '';
$sucursal_section = isset($_SESSION['sucursal_section']) ? $_SESSION['sucursal_section'] : '';
$bloquear_arqueo = isset($_SESSION['bloquear_arqueo']) ? $_SESSION['bloquear_arqueo'] : false;
$requiere_arqueo_caja_sucursal = ($sucursal_section === 'true') && requiere_arqueo_caja_sucursal((int) $usuario_sucursal);
// Variables de la app (idioma, país y nombre)
$app_lang_id = isset($_SESSION['app_lang_id']) ? $_SESSION['app_lang_id'] : '';
$app_country_id = isset($_SESSION['app_country_id']) ? $_SESSION['app_country_id'] : '';
$app_name = isset($_SESSION['app_name']) ? $_SESSION['app_name'] : '';
$app_cod_LP = isset($_SESSION['app_cod_LP']) ? $_SESSION['app_cod_LP'] : '';
$app_charset_html = isset($_SESSION['app_charset_html']) ? $_SESSION['app_charset_html'] : '';

$central_section = isset($_SESSION['central_section']) ? $_SESSION['central_section'] : '';
$recepcion_lotes_section = isset($_SESSION['recepcion_lotes_section']) ? $_SESSION['recepcion_lotes_section'] : '';
$auditoria_section = isset($_SESSION['auditoria_section']) ? $_SESSION['auditoria_section'] : '';

// Variables adicionales útiles para el dashboard
$usuario_es_admin = usuario_es_admin();
$fecha_actual = date('d/m/Y');
$hora_actual = date('H:i:s');

// Función de traducciones disponible globalmente: t('Lang_Key')
// Ejemplo: echo t('Lang_Rememberme'); // Retorna "Recordarme" según el idioma de la app
$timestamp_actual = time();
$datos_precio_oro_navbar = obtener_datos_precio_oro_navbar();
$precio_oro_update = $datos_precio_oro_navbar['precio'];
$precio_oro_vigencia_fmt = $datos_precio_oro_navbar['vigencia_fmt'];
$precio_oro_base = $datos_precio_oro_navbar['precio_base'];
$datos_precio_oro_proveedores_navbar = obtener_datos_precio_oro_proveedores_navbar();
$proveedores_precio_oro_navbar = isset($datos_precio_oro_proveedores_navbar['proveedores'])
    ? $datos_precio_oro_proveedores_navbar['proveedores']
    : [];
?>
