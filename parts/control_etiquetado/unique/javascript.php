<?php
$vFiltrosListar = filemtime(__DIR__ . '/../../universal/filtros-listar.js');
$vTablesDatatablesLoad = filemtime(__DIR__ . '/tables-datatables-load.js');
$vFlatpickrDatatable = filemtime(__DIR__ . '/../../universal/flatpickr-datatable.js');
?>
<script>
window.ListarFiltrosConfig = {
  filterIds: ['filtro_tipo_control_etiquetado'],
  containerId: 'control_etiquetado_filtros_container',
  readyClass: 'control-etiquetado-filtros-ready',
  initMarkerId: 'filtro_tipo_control_etiquetado'
};
</script>
<script src="parts/universal/filtros-listar.js?v=<?php echo $vFiltrosListar; ?>"></script>
<script src="parts/control_etiquetado/unique/tables-datatables-load.js?v=<?php echo $vTablesDatatablesLoad; ?>"></script>
<script src="parts/universal/flatpickr-datatable.js?v=<?php echo $vFlatpickrDatatable; ?>"></script>
