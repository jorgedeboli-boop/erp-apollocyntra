<?php
/**
 * Control del wizard de formación (solo APP_ID 444).
 *
 * En include/config.php:
 *   define('FORMACION_WIZARD_ENABLED', false);
 *
 * Si no defines la constante, el wizard queda activo en la app de formación.
 */
function formacion_wizard_activo()
{
    if (!defined('APP_ID') || (string) APP_ID !== '444') {
        return false;
    }
    if (defined('FORMACION_WIZARD_ENABLED')) {
        return (bool) FORMACION_WIZARD_ENABLED;
    }
    return true;
}
