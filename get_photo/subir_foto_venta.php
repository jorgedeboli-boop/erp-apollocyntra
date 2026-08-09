<?php
/** @deprecated Usar camera/subir_foto.php con POST camera_type=venta */
$_POST['camera_type'] = 'venta';
require __DIR__ . '/../camera/subir_foto.php';
