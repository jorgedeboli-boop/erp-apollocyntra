<!-- JAVASCRIPT CUSTOM CLIENTES LISTAR -->
<!-- Scripts personalizados de clientes -->
<?php
$vFiltrosClientes = filemtime(__DIR__ . '/filtros-clientes.js');
$vLoadStats = filemtime(__DIR__ . '/load_stats.js');
$vTablesDatatablesLoadSimple = filemtime(__DIR__ . '/tables-datatables-load-simple.js');
?>
<script src="parts/clientes/listar/filtros-clientes.js?v=<?php echo $vFiltrosClientes; ?>"></script>
<script src="parts/clientes/listar/load_stats.js?v=<?php echo $vLoadStats; ?>"></script>
<script src="parts/clientes/listar/tables-datatables-load-simple.js?v=<?php echo $vTablesDatatablesLoadSimple; ?>"></script>