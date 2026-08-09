<?php
require_once '../../../include/session.php';
require_once '../../../include/functions.php';

$name = isset($_GET['name']) ? $_GET['name'] : 'rel_id_pais';
$selected_value = isset($_GET['selected']) ? $_GET['selected'] : '';
$placeholder = isset($_GET['placeholder']) ? $_GET['placeholder'] : 'Selecciona país';

echo generarSelectPaises($name, $selected_value, $placeholder);
?>
