<?php
$vFiltrosListar = filemtime(__DIR__ . '/../../universal/filtros-listar.js');
$vTablesDatatablesLoad = filemtime(__DIR__ . '/tables-datatables-load.js');
?>
<script>
window.ListarFiltrosConfig = {
  filterIds: ['filtro_sucursal'],
  containerId: 'collapse_filtros',
  readyClass: 'empenos-filtros-ready',
  sucursalSelectId: 'filtro_sucursal',
  initMarkerId: 'filtro_sucursal'
};
</script>
<script src="parts/universal/filtros-listar.js?v=<?php echo $vFiltrosListar; ?>"></script>
<script src="parts/devoluciones/listar/tables-datatables-load.js?v=<?php echo $vTablesDatatablesLoad; ?>"></script>