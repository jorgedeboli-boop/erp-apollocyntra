<!-- JAVASCRIPT CUSTOM HISTÓRICO DE CIERRES -->
<!-- Scripts personalizados de histórico de cierres -->
<?php
$vTablesDatatablesLoad = filemtime(__DIR__ . '/tables-datatables-load.js');
$vFlatpickrDatatable = filemtime(__DIR__ . '/../../universal/flatpickr-datatable.js');
?>
<script src="parts/historico_de_cierres/unique/tables-datatables-load.js?v=<?php echo $vTablesDatatablesLoad; ?>"></script>
<!-- Script universal de Flatpickr para DataTables -->
<script src="parts/universal/flatpickr-datatable.js?v=<?php echo $vFlatpickrDatatable; ?>"></script>
