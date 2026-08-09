<?php
// JavaScript dependencies are loaded globally in universal/js-dependencies.php
// This file only loads module-specific scripts
?>

<?php
$vTablesDatatablesLoad = filemtime(__DIR__ . '/tables-datatables-load.js');
?>
<script src="parts/grupo_apuntes/listar/tables-datatables-load.js?v=<?php echo $vTablesDatatablesLoad; ?>"></script>