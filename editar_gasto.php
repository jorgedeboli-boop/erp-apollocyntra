<?php
require_once 'include/session.php';

// Obtener el ID del gasto
$id_gasto = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if (!$id_gasto) {
    header('Location: gastos.php');
    exit;
}

// Redirigir al módulo de editar gastos
header('Location: parts/gastos/editar/index.php?id=' . $id_gasto);
exit;
?>