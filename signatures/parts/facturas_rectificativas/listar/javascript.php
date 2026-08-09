<?php
$vFiltrosDinamicos = filemtime(__DIR__ . '/../../universal/filtros-dinamicos-listar.js');
?>
<script>
window.FiltrosDinamicosConfig = {
  containerId: 'collapse_filtros',
  readyClass: 'facturas-rectificativas-filtros-ready'
};
</script>
<script src="parts/universal/filtros-dinamicos-listar.js?v=<?php echo $vFiltrosDinamicos; ?>"></script>
<!-- JAVASCRIPT CUSTOM facturas_rectificativas - listar  -->
<?php
$vTablesDatatablesLoad = filemtime(__DIR__ . '/tables-datatables-load.js');
?>
<script src="parts/facturas_rectificativas/listar/tables-datatables-load.js?v=<?php echo $vTablesDatatablesLoad; ?>"></script>