<?php
$vFiltrosArticulos = filemtime(__DIR__ . '/../../universal/filtros-articulos.js');
$vPedirColumnasExportacion = filemtime(__DIR__ . '/../../universal/pedir-columnas-exportacion.js');
$vTablesDatatablesLoad = filemtime(__DIR__ . '/tables-datatables-load.js');
$vLoadStats = filemtime(__DIR__ . '/load_stats.js');
$vFlatpickrDatatable = filemtime(__DIR__ . '/../../universal/flatpickr-datatable.js');
?>
<script>
window.ArticulosFiltrosConfig = {
  containerId: 'stock_filtros_container',
  readyClass: 'stock-filtros-ready',
  filterIds: ['filtro_sucursal_articulo', 'filtro_tipo', 'filtro_origen'],
  sucursalSelectId: 'filtro_sucursal_articulo',
  sucursalValueField: 'id_sucursal'
};
</script>
<script src="parts/universal/filtros-articulos.js?v=<?php echo $vFiltrosArticulos; ?>"></script>
<script src="parts/universal/pedir-columnas-exportacion.js?v=<?php echo $vPedirColumnasExportacion; ?>"></script>
<script src="parts/stock/unique/tables-datatables-load.js?v=<?php echo $vTablesDatatablesLoad; ?>"></script>
<script src="parts/stock/unique/load_stats.js?v=<?php echo $vLoadStats; ?>"></script>
<script src="parts/universal/flatpickr-datatable.js?v=<?php echo $vFlatpickrDatatable; ?>"></script>
