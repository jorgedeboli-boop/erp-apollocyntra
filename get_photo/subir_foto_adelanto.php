<?php
/** @deprecated Usar camera/subir_foto.php con POST camera_type=adelanto */
$_POST['camera_type'] = 'adelanto';
require __DIR__ . '/../camera/subir_foto.php';
