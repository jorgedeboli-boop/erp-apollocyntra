<!-- JAVASCRIPT CUSTOM GASTOS FIJOS LISTAR -->
<?php
$vTablesDatatablesLoad = filemtime(__DIR__ . '/tables-datatables-load.js');
$vFlatpickrDatatable = filemtime(__DIR__ . '/../../universal/flatpickr-datatable.js');
?>
<?php
$vFiltrosListar = filemtime(__DIR__ . '/../../universal/filtros-listar.js');
?>
<script>
window.ListarFiltrosConfig = {
  filterIds: ['filtro_periodo', 'filtro_sucursal', 'filtro_estado'],
  containerId: 'collapse_filtros_gastos_fijos',
  readyClass: 'gastos-fijos-filtros-ready',
  initMarkerId: 'filtro_periodo'
};
</script>
<script src="parts/universal/filtros-listar.js?v=<?php echo $vFiltrosListar; ?>"></script>
<script src="parts/gastos_fijos/listar/tables-datatables-load.js?v=<?php echo $vTablesDatatablesLoad; ?>"></script>
<script src="parts/universal/flatpickr-datatable.js?v=<?php echo $vFlatpickrDatatable; ?>"></script>