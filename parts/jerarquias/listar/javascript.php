<!--<script src="parts/<?php echo $itemname; ?>/<?php echo $type; ?>/app-access-roles.js"></script>
<script src="parts/<?php echo $itemname; ?>/<?php echo $type; ?>/modal-add-role.js"></script>-->
<!-- Script para cargar privilegios -->
<?php
$vLoadPrivilegios = filemtime(__DIR__ . '/load_privilegios.js');
?>
<script src="parts/jerarquias/listar/load_privilegios.js?v=<?php echo $vLoadPrivilegios; ?>"></script>