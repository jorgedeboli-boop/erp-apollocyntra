<?php
/** @deprecated Usar camera/subir_foto.php con POST camera_type=lote */
$_POST['camera_type'] = 'lote';
require __DIR__ . '/../camera/subir_foto.php';
