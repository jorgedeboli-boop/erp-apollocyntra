<?php
/** @deprecated Usar camera/subir_foto.php con POST camera_type=renovacion */
$_POST['camera_type'] = 'renovacion';
require __DIR__ . '/../camera/subir_foto.php';
