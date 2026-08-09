<?php
/** @deprecated Usar camera/subir_foto.php con POST camera_type=articulo */
$_POST['camera_type'] = 'articulo';
require __DIR__ . '/../camera/subir_foto.php';
