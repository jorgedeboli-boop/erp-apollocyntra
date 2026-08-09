<?php
/**
 * Redirección post-login (APP_ID 444, sucursal): reanudar el wizard en la primera pantalla incompleta.
 * La columna id_cliente_context es opcional: si no existe, la reanudación tras login no podrá abrir ficha/edición por id hasta que ejecutes el ALTER.
 */
require_once __DIR__ . '/formacion_wizard.php';

/**
 * @param int $id_usuario
 * @return string|null Ruta relativa al raíz de la app (p.ej. clientes_sucursal.php), sin ../
 */
function formacion_wizard_url_tras_login($id_usuario)
{
    if (!formacion_wizard_activo()) {
        return null;
    }
    $id_usuario = (int) $id_usuario;
    if ($id_usuario < 1) {
        return null;
    }

    $orden = array(
        'formacion_menu_clientes',
        'formacion_clientes_buscar_campo',
        'formacion_clientes_ejemplo_busqueda',
        'formacion_clientes_abrir_ficha',
        'formacion_ficha_perfil',
        'formacion_ficha_lotes_tab',
        'formacion_ficha_lotes_buscador',
        'formacion_ficha_empenos_tab',
        'formacion_ficha_empenos_buscador',
        'formacion_ficha_ventas_tab',
        'formacion_ficha_ventas_completado',
        'formacion_ficha_editar_cliente_link',
        'formacion_editar_cliente_campos_obligatorios',
        'formacion_editar_cliente_guardar_info',
    );

    $conexion = @conectar_bd();
    if (!$conexion) {
        return null;
    }

    $stmt = @mysqli_prepare(
        $conexion,
        'SELECT codigo_paso FROM formacion_wizard_pasos WHERE id_usuario = ?'
    );
    if (!$stmt) {
        mysqli_close($conexion);
        return null;
    }

    mysqli_stmt_bind_param($stmt, 'i', $id_usuario);
    mysqli_stmt_execute($stmt);
    $res = mysqli_stmt_get_result($stmt);
    $hecho = array();
    if ($res) {
        while ($row = mysqli_fetch_assoc($res)) {
            $hecho[$row['codigo_paso']] = true;
        }
    }
    mysqli_stmt_close($stmt);

    $idCliente = 0;
    $stmt2 = @mysqli_prepare(
        $conexion,
        'SELECT id_cliente_context FROM formacion_wizard_pasos
         WHERE id_usuario = ? AND id_cliente_context IS NOT NULL AND id_cliente_context > 0
         ORDER BY fecha_completado DESC LIMIT 1'
    );
    if ($stmt2) {
        mysqli_stmt_bind_param($stmt2, 'i', $id_usuario);
        mysqli_stmt_execute($stmt2);
        $r2 = mysqli_stmt_get_result($stmt2);
        if ($r2 && ($row2 = mysqli_fetch_assoc($r2))) {
            $idCliente = (int) $row2['id_cliente_context'];
        }
        mysqli_stmt_close($stmt2);
    }
    mysqli_close($conexion);

    foreach ($orden as $codigo) {
        if (empty($hecho[$codigo])) {
            return formacion_wizard_url_para_paso_incompleto($codigo, $idCliente);
        }
    }

    return null;
}

/**
 * @param string $codigo Primer paso no completado
 * @param int    $idCliente Último id_cliente conocido (0 si no hay)
 * @return string
 */
function formacion_wizard_url_para_paso_incompleto($codigo, $idCliente)
{
    switch ($codigo) {
        case 'formacion_menu_clientes':
            return 'dashboard_sucursal.php';
        case 'formacion_clientes_buscar_campo':
        case 'formacion_clientes_ejemplo_busqueda':
        case 'formacion_clientes_abrir_ficha':
            return 'clientes_sucursal.php';
        case 'formacion_ficha_perfil':
        case 'formacion_ficha_lotes_tab':
        case 'formacion_ficha_lotes_buscador':
        case 'formacion_ficha_empenos_tab':
        case 'formacion_ficha_empenos_buscador':
        case 'formacion_ficha_ventas_tab':
        case 'formacion_ficha_ventas_completado':
        case 'formacion_ficha_editar_cliente_link':
            if ($idCliente > 0) {
                return 'cliente_sucursal.php?id=' . $idCliente;
            }
            return 'clientes_sucursal.php';
        case 'formacion_editar_cliente_campos_obligatorios':
        case 'formacion_editar_cliente_guardar_info':
            if ($idCliente > 0) {
                return 'editar_clientes_sucursal.php?id=' . $idCliente;
            }
            return 'clientes_sucursal.php';
        default:
            return 'dashboard_sucursal.php';
    }
}
