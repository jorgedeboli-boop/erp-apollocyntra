<?php
$vFiltrosDinamicos = filemtime(__DIR__ . '/../../universal/filtros-dinamicos-listar.js');
$vTablesDatatablesLoad = filemtime(__DIR__ . '/tables-datatables-load.js');
?>
<script>
window.FiltrosDinamicosConfig = {
  readyClass: 'correccion-cajas-filtros-ready'
};
</script>
<script src="parts/universal/filtros-dinamicos-listar.js?v=<?php echo $vFiltrosDinamicos; ?>"></script>
<script src="parts/correccion_de_cajas/unique/tables-datatables-load.js?v=<?php echo $vTablesDatatablesLoad; ?>"></script>
