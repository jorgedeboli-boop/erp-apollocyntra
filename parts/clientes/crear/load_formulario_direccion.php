<?php
/**
 * Cargar formulario de dirección según si el cliente existe o no
 * Si tipo=insert → formulario_direccion_insert.php
 * Si tipo=edit → formulario_direccion_edit.php
 */

$tipo = $_GET['tipo'] ?? 'insert';

if ($tipo === 'edit') {
    require_once 'formulario_direccion_edit.php';
} else {
    require_once 'formulario_direccion_insert.php';
}
?>

