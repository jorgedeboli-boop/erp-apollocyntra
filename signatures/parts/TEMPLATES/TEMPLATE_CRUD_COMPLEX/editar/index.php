<?php
require_once '../../../include/session.php';
require_once '../../../parts/universal/main_files.php';

// Obtener el ID del gasto
$id_gasto = isset($_GET['id']) ? (int)$_GET['id'] : 0;

// Incluir el contenido principal del formulario de edición
include 'content.php';

// Incluir CSS y JS específicos del módulo
include 'css.php';
include 'javascript.php';
?>
