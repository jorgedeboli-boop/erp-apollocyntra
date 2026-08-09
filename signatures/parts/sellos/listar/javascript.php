<?php
$vLoadStats = filemtime(__DIR__ . '/load_stats.js');
$vTablesDatatablesLoad = filemtime(__DIR__ . '/tables-datatables-load.js');
?>
<script src="parts/sellos/listar/load_stats.js?v=<?php echo $vLoadStats; ?>"></script>
<script src="parts/sellos/listar/tables-datatables-load.js?v=<?php echo $vTablesDatatablesLoad; ?>"></script>
