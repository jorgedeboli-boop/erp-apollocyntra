<?php
$vTablesDatatablesLoad = filemtime(__DIR__ . '/tables-datatables-load.js');
?>
<script src="parts/fiskaly_invoices/listar/tables-datatables-load.js?v=<?php echo $vTablesDatatablesLoad; ?>"></script>