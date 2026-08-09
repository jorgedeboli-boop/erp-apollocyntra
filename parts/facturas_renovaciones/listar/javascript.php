<?php
$vFiltrosDinamicos = filemtime(__DIR__ . '/../../universal/filtros-dinamicos-listar.js');
?>
<script>
window.FiltrosDinamicosConfig = {
  containerId: 'collapse_filtros_ren',
  readyClass: 'facturas-renovaciones-filtros-ready'
};
</script>
<script src="parts/universal/filtros-dinamicos-listar.js?v=<?php echo $vFiltrosDinamicos; ?>"></script>
<!-- JAVASCRIPT CUSTOM facturas simplificadas - listar  -->
<?php
$vTablesDatatablesSimplificadasLoad = filemtime(__DIR__ . '/tables-datatables-simplificadas-load.js');
$vFlatpickrDatatable = filemtime(__DIR__ . '/../../universal/flatpickr-datatable.js');
?>
<script src="parts/facturas_renovaciones/listar/tables-datatables-simplificadas-load.js?v=<?php echo $vTablesDatatablesSimplificadasLoad; ?>"></script>
<script src="parts/universal/flatpickr-datatable.js?v=<?php echo $vFlatpickrDatatable; ?>"></script>
