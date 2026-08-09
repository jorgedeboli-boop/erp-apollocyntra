<?php
$vTablesDatatables = filemtime(__DIR__ . '/tables-datatables-load.js');
$vScriptsLoad = filemtime(__DIR__ . '/scripts-load.js');
?>
<script src="parts/<?php echo $itemname; ?>/<?php echo $type; ?>/tables-datatables-load.js?v=<?php echo $vTablesDatatables; ?>"></script>
<script src="parts/<?php echo $itemname; ?>/<?php echo $type; ?>/scripts-load.js?v=<?php echo $vScriptsLoad; ?>"></script>
<!--<?php
$vAppUserList = filemtime(__DIR__ . '/../../../assets/js/app-user-list.js');
?>
<script src="assets/js/app-user-list.js?v=<?php echo $vAppUserList; ?>"></script>-->