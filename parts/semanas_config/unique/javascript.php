<!-- JAVASCRIPT CUSTOM semanas_config - unique  -->
<?php
$vFlatpickrDatatable = filemtime(__DIR__ . '/../../universal/flatpickr-datatable.js');
$vTablesDatatablesLoad = filemtime(__DIR__ . '/tables-datatables-load.js');
?>
<script src="parts/universal/flatpickr-datatable.js?v=<?php echo $vFlatpickrDatatable; ?>"></script>
<script src="parts/semanas_config/unique/tables-datatables-load.js?v=<?php echo $vTablesDatatablesLoad; ?>"></script>
