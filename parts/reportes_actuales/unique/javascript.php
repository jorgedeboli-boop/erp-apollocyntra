<!-- JAVASCRIPT CUSTOM reportes_actuales - unique  -->
<?php
$vTablesDatatables = filemtime(__DIR__ . '/tables-datatables-load.js');
$vFlatpickrDatatable = filemtime(__DIR__ . '/../../universal/flatpickr-datatable.js');
$vFiltrosListar = filemtime(__DIR__ . '/../../universal/filtros-listar.js');
?>
<script>
window.ListarFiltrosConfig = {
  filterIds: ['filtro_sucursal'],
  containerId: 'collapse_filtros_reportes_actuales',
  readyClass: 'reportes-actuales-filtros-ready',
  initMarkerId: 'filtro_sucursal',
  select2OptionsById: {
    filtro_sucursal: { allowClear: true }
  }
};
</script>
<script src="parts/universal/filtros-listar.js?v=<?php echo $vFiltrosListar; ?>"></script>
<script src="parts/reportes_actuales/unique/tables-datatables-load.js?v=<?php echo $vTablesDatatables; ?>"></script>
<script src="parts/universal/flatpickr-datatable.js?v=<?php echo $vFlatpickrDatatable; ?>"></script>
