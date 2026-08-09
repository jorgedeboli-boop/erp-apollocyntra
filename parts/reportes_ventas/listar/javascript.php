<!-- JAVASCRIPT CUSTOM reportes_ventas - listar  -->
<?php
$vTablesDatatablesLoad = filemtime(__DIR__ . '/tables-datatables-load.js');
$vFlatpickrDatatable = filemtime(__DIR__ . '/../../universal/flatpickr-datatable.js');
?>
<?php
$vFiltrosListar = filemtime(__DIR__ . '/../../universal/filtros-listar.js');
?>
<script>
window.ListarFiltrosConfig = {
  filterIds: ['filtro_sucursal', 'filtro_tipo', 'filtro_plazos', 'filtro_plazos_pendientes', 'filtro_tipo_pago'],
  containerId: 'collapse_filtros_reportes_ventas',
  readyClass: 'reportes-ventas-filtros-ready',
  initMarkerId: 'filtro_sucursal',
  select2OptionsById: {
    filtro_sucursal: { allowClear: true }
  }
};
</script>
<script src="parts/universal/filtros-listar.js?v=<?php echo $vFiltrosListar; ?>"></script>
<script src="parts/reportes_ventas/listar/tables-datatables-load.js?v=<?php echo $vTablesDatatablesLoad; ?>"></script>
<script src="parts/universal/flatpickr-datatable.js?v=<?php echo $vFlatpickrDatatable; ?>"></script>
