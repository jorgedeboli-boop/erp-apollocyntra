<?php
/**
 * Cargar formulario de dirección del módulo cliente.
 * tipo=insert → formulario_direccion_insert.php
 * tipo=edit   → formulario_direccion_edit.php
 */

$tipo = $_GET['tipo'] ?? 'insert';

if ($tipo === 'edit') {
    require_once __DIR__ . '/formulario_direccion_edit.php';
} else {
    require_once __DIR__ . '/formulario_direccion_insert.php';
}
