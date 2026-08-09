<?php
$vTablesDatatablesLoad = filemtime(__DIR__ . '/tables-datatables-load.js');
$vFlatpickrDatatable = filemtime(__DIR__ . '/../../universal/flatpickr-datatable.js');
?>
<script src="parts/presupuestos/listar/tables-datatables-load.js?v=<?php echo $vTablesDatatablesLoad; ?>"></script>
<script src="parts/universal/flatpickr-datatable.js?v=<?php echo $vFlatpickrDatatable; ?>"></script>
