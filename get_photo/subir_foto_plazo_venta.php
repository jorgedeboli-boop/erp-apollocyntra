<?php
/** @deprecated Usar camera/subir_foto.php con POST camera_type=plazo_venta */
$_POST['camera_type'] = 'plazo_venta';
require __DIR__ . '/../camera/subir_foto.php';
