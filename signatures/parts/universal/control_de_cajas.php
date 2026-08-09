<?php
/**
 * Redirecciones y comprobaciones de caja (sucursal).
 * Requiere variables definidas por include/session.php: $sucursal_section, $usuario_sucursal.
 */
if ($sucursal_section == 'true') {
    if (controlarNewSistemaCaja($usuario_sucursal)) {
        if (controlarCajaCerrada($usuario_sucursal)) {
            if (fechaCajaCerrada($usuario_sucursal)) {
                cerrar_sesion();
                header('Location: login.php?cajaCerrada=1');
                exit;
            }
        } else {
            if (requiere_arqueo_caja_sucursal($usuario_sucursal)) {
                $script_sesion = basename(isset($_SERVER['SCRIPT_NAME']) ? $_SERVER['SCRIPT_NAME'] : '');
                if ($script_sesion !== 'arqueos_cajas_sucursal.php') {
                    $_SESSION['bloquear_arqueo'] = true;
                    header('location: arqueos_cajas_sucursal.php');
                    exit();
                }
            }
        }
    }
}
