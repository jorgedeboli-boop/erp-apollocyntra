<!-- JAVASCRIPT CUSTOM autorizar_descuentos - listar  -->

<?php
$vFiltrosListar = filemtime(__DIR__ . '/../../universal/filtros-listar.js');
$vAutorizacionesPollEstados = filemtime(__DIR__ . '/../../universal/js/autorizaciones-poll-estados.js');
$vTablesDatatablesLoad = filemtime(__DIR__ . '/tables-datatables-load.js');
?>
<script>window.puede_acceder_edit = <?php echo !empty($puede_acceder_edit) ? 'true' : 'false'; ?>;</script>
<script>
window.ListarFiltrosConfig = {
  containerId: 'autorizar_filtros_container',
  readyClass: 'autorizar-filtros-ready',
  filterIds: ['FiltroSucursalDescuento', 'FiltroEstadoDescuento'],
  sucursalSelectId: 'FiltroSucursalDescuento',
  sucursalValueField: 'nombre_sucursal'
};
</script>
<script src="parts/universal/filtros-listar.js?v=<?php echo $vFiltrosListar; ?>"></script>
<script src="parts/universal/js/autorizaciones-poll-estados.js?v=<?php echo $vAutorizacionesPollEstados; ?>"></script>
<script src="parts/autorizar_descuentos/listar/tables-datatables-load.js?v=<?php echo $vTablesDatatablesLoad; ?>"></script>
