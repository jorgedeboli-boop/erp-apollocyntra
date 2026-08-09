<?php
/**
 * Inyecta assets del tutorial solo en la app de formación (APP_ID === '444' y FORMACION_WIZARD_ENABLED).
 * Requiere haber cargado antes session.php (variables de usuario en scope).
 */
require_once __DIR__ . '/../../include/formacion_wizard.php';
if (!formacion_wizard_activo()) {
    return;
}

$fw_base = rtrim(APP_URL, '/');
$saludo_nombre = '';
if (!empty($usuario_nombre_completo)) {
    $partes = preg_split('/\s+/', trim($usuario_nombre_completo), 2, PREG_SPLIT_NO_EMPTY);
    $saludo_nombre = isset($partes[0]) ? $partes[0] : '';
}
if ($saludo_nombre === '' && !empty($usuario)) {
    $saludo_nombre = $usuario;
}

$fw_config = array(
    'apiBase' => $fw_base . '/wizard/api/',
    'saludoNombre' => $saludo_nombre,
    'codigos' => array(
        'menuClientes' => 'formacion_menu_clientes',
        'buscarCliente' => 'formacion_clientes_buscar_campo',
        'ejemploBusqueda' => 'formacion_clientes_ejemplo_busqueda',
        'abrirFicha' => 'formacion_clientes_abrir_ficha',
        'fichaPerfil' => 'formacion_ficha_perfil',
        'fichaLotesTab' => 'formacion_ficha_lotes_tab',
        'fichaLotesBuscador' => 'formacion_ficha_lotes_buscador',
        'fichaEmpenosTab' => 'formacion_ficha_empenos_tab',
        'fichaEmpenosBuscador' => 'formacion_ficha_empenos_buscador',
        'fichaVentasTab' => 'formacion_ficha_ventas_tab',
        'fichaVentasCompletado' => 'formacion_ficha_ventas_completado',
        'fichaEditarClienteLink' => 'formacion_ficha_editar_cliente_link',
        'editarCamposObligatorios' => 'formacion_editar_cliente_campos_obligatorios',
        'editarGuardarInfo' => 'formacion_editar_cliente_guardar_info',
    ),
);
?>
<link rel="stylesheet" href="<?php echo htmlspecialchars($fw_base . '/wizard/wizard.css', ENT_QUOTES, 'UTF-8'); ?>" />
<script>
window.FormacionWizard = <?php echo json_encode($fw_config, JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT); ?>;
</script>
<script src="<?php echo htmlspecialchars($fw_base . '/wizard/wizard.js', ENT_QUOTES, 'UTF-8'); ?>" defer></script>
