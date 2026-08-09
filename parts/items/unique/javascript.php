<!--<script src="parts/<?php echo $itemname; ?>/<?php echo $type; ?>/app-access-roles.js"></script>
<script src="parts/<?php echo $itemname; ?>/<?php echo $type; ?>/modal-add-role.js"></script>-->
<!-- Script para cargar items -->
<?php
$vLoadItems = filemtime(__DIR__ . '/load_items.js');
$vSearchItems = filemtime(__DIR__ . '/search_items.js');
?>
<script src="parts/items/unique/load_items.js?v=<?php echo $vLoadItems; ?>"></script>
<!-- Script para búsqueda de items -->
<script src="parts/items/unique/search_items.js?v=<?php echo $vSearchItems; ?>"></script>