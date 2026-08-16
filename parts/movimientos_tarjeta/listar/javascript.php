<?php
$vFiltrosDinamicos = filemtime(__DIR__ . '/../../universal/filtros-dinamicos-listar.js');
?>
<script>
window.FiltrosDinamicosConfig = {
  containerId: 'collapse_filtros',
  readyClass: 'movimientos-tarjeta-filtros-ready'
};
</script>
<script src="parts/universal/filtros-dinamicos-listar.js?v=<?php echo $vFiltrosDinamicos; ?>"></script>
<!-- JAVASCRIPT CUSTOM MOVIMIENTOS TARJETA LISTAR -->
<!-- Scripts personalizados de movimientos tarjeta -->
<?php
$vLoadStats = filemtime(__DIR__ . '/load_stats.js');
$vTablesDatatablesLoad = filemtime(__DIR__ . '/tables-datatables-load.js');
$vFlatpickrDatatable = filemtime(__DIR__ . '/../../universal/flatpickr-datatable.js');
$vActualizarTituloFiltros = filemtime(__DIR__ . '/../../universal/actualizar-titulo-filtros.js');
$vExportDatatableTodos = filemtime(__DIR__ . '/../../universal/export-datatable-todos.js');
?>
<script>window.puede_acceder_edit = <?php echo !empty($puede_acceder_edit) ? 'true' : 'false'; ?>;</script>
<script src="parts/movimientos_tarjeta/listar/load_stats.js?v=<?php echo $vLoadStats; ?>"></script>
<script src="parts/universal/export-datatable-todos.js?v=<?php echo $vExportDatatableTodos; ?>"></script>
<script src="parts/movimientos_tarjeta/listar/tables-datatables-load.js?v=<?php echo $vTablesDatatablesLoad; ?>"></script>
<!-- Script universal de Flatpickr para DataTables -->
<script src="parts/universal/flatpickr-datatable.js?v=<?php echo $vFlatpickrDatatable; ?>"></script>
<!-- Script universal para actualizar título con filtros -->
<script src="parts/universal/actualizar-titulo-filtros.js?v=<?php echo $vActualizarTituloFiltros; ?>"></script>
