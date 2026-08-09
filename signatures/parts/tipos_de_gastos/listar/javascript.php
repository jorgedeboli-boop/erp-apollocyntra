<!-- JAVASCRIPT CUSTOM TIPOS DE GASTOS - LISTAR  -->

<!-- Scripts personalizados de tipos de gastos -->
<?php
$vLoadStats = filemtime(__DIR__ . '/load_stats.js');
$vTablesDatatablesLoad = filemtime(__DIR__ . '/tables-datatables-load.js');
?>
<script src="parts/tipos_de_gastos/listar/load_stats.js?v=<?php echo $vLoadStats; ?>"></script>
<script src="parts/tipos_de_gastos/listar/tables-datatables-load.js?v=<?php echo $vTablesDatatablesLoad; ?>"></script>