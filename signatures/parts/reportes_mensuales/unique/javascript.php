<!-- JAVASCRIPT CUSTOM reportes_mensuales - unique  -->
<?php
$vTablesDatatablesLoad = filemtime(__DIR__ . '/tables-datatables-load.js');
?>
<?php
$vFiltrosListar = filemtime(__DIR__ . '/../../universal/filtros-listar.js');
?>
<script>
window.ListarFiltrosConfig = {
  filterIds: ['filtro_sucursal', 'filtro_anio', 'filtro_mes'],
  containerId: 'collapse_filtros_reportes_mensuales',
  readyClass: 'reportes-mensuales-filtros-ready',
  initMarkerId: 'filtro_sucursal',
  select2OptionsById: {
    filtro_sucursal: { allowClear: true }
  }
};
</script>
<script src="parts/universal/filtros-listar.js?v=<?php echo $vFiltrosListar; ?>"></script>
<script src="parts/reportes_mensuales/unique/tables-datatables-load.js?v=<?php echo $vTablesDatatablesLoad; ?>"></script>
