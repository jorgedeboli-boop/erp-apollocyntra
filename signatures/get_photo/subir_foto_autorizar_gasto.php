<?php
/** @deprecated Usar camera/subir_foto.php con POST camera_type=autorizar_gasto */
$_POST['camera_type'] = 'autorizar_gasto';
require __DIR__ . '/../camera/subir_foto.php';
