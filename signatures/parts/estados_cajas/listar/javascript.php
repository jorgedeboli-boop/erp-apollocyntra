<!-- JAVASCRIPT CUSTOM ESTADOS CAJAS - LISTAR  -->

<!-- Scripts personalizados de estados_cajas -->
<?php
$vLoadStats = filemtime(__DIR__ . '/load_stats.js');
$vTablesDatatablesLoad = filemtime(__DIR__ . '/tables-datatables-load.js');
?>
<script>window.puede_acceder_edit = <?php echo !empty($puede_acceder_edit) ? 'true' : 'false'; ?>;</script>
<script src="parts/estados_cajas/listar/load_stats.js?v=<?php echo $vLoadStats; ?>"></script>
<script src="parts/estados_cajas/listar/tables-datatables-load.js?v=<?php echo $vTablesDatatablesLoad; ?>"></script>
