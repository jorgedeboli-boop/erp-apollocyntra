<?php
require_once 'functions.php';

// Cerrar la sesión
cerrar_sesion();

// Redirigir al login con mensaje de éxito
header('Location: ../login.php?logout=1');
exit();
?>
