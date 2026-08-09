<!-- JAVASCRIPT CUSTOM modulo_cliente_form - unique -->
<?php
/**
 * Incluir desde la página padre definiendo antes:
 *   $modulo_cliente_form_modo = 'crear';  // o 'editar'
 */
$modulo_cliente_form_modo = isset($modulo_cliente_form_modo) ? $modulo_cliente_form_modo : 'crear';
?>
<?php if (isset($app_country_id) && (int) $app_country_id === 68) { ?>
<?php
$vComprobarIdentificacionSpain = filemtime(__DIR__ . '/../../universal/js/comprobar_identificacion_spain.js');
$vJavascriptDirecciones = filemtime(__DIR__ . '/../../universal/js/javascript_direcciones.js');
?>
<script src="parts/universal/js/comprobar_identificacion_spain.js?v=<?php echo $vComprobarIdentificacionSpain; ?>"></script>
<?php } ?>
<script src="parts/universal/js/javascript_direcciones.js?v=<?php echo $vJavascriptDirecciones; ?>"></script>
<?php if ($modulo_cliente_form_modo === 'editar') { ?>
<script src="parts/modulo_cliente_form/unique/modulo_cliente_editar.js?v=<?php echo time(); ?>"></script>
<?php } else { ?>
<script src="parts/modulo_cliente_form/unique/modulo_cliente_crear.js?v=<?php echo time(); ?>"></script>
<?php } ?>
