<?php
/**
 * Bootstrap mínimo para APIs del wizard (sin session.php: evita Location relativos rotos en fetch).
 *
 * Importante: include/functions.php hace require_once 'config.php' (ruta relativa sin __DIR__).
 * Si el script de entrada es wizard/api/*.php, el CWD suele ser wizard/api/ y ese require falla.
 * Por eso hacemos chdir temporal al directorio include/ antes de cargar functions.php.
 */
$includeDir = realpath(__DIR__ . '/../../include');
if ($includeDir === false || !is_dir($includeDir)) {
    header('Content-Type: application/json; charset=utf-8');
    http_response_code(500);
    echo json_encode(array('ok' => false, 'error' => 'init_include'));
    exit;
}

$wizardPrevCwd = @getcwd();
@chdir($includeDir);
require_once 'functions.php';
if ($wizardPrevCwd !== false) {
    @chdir($wizardPrevCwd);
}

header('Content-Type: application/json; charset=utf-8');

if (!usuario_autenticado()) {
    http_response_code(401);
    echo json_encode(array('ok' => false, 'error' => 'no_autenticado'));
    exit;
}

require_once __DIR__ . '/../../include/formacion_wizard.php';
if (!formacion_wizard_activo()) {
    http_response_code(403);
    echo json_encode(array('ok' => false, 'error' => 'wizard_desactivado'));
    exit;
}
