<?php
/** @deprecated Usar camera/subir_foto.php con POST camera_type=articulo_venta */
$_POST['camera_type'] = 'articulo_venta';
require __DIR__ . '/../camera/subir_foto.php';
