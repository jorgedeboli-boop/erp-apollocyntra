<?php
/** @deprecated Usar camera/subir_foto.php con POST camera_type=cliente */
$_POST['camera_type'] = 'cliente';
require __DIR__ . '/subir_foto.php';
