<?php
$id_articulo_pre = isset($_POST['id_articulo']) ? (int) $_POST['id_articulo'] : (isset($_GET['id_articulo']) ? (int) $_GET['id_articulo'] : 0);
$id_sucursal = 0;
$nombre_sucursal = '';
$id_empresa_rel = function_exists('obtener_rel_id_empresa_sesion') ? obtener_rel_id_empresa_sesion() : 0;

if ($id_empresa_rel <= 0) {
    header('Location: presupuestos.php?error=empresa');
    exit;
}

$empresa = function_exists('obtener_datos_empresa_sesion') ? obtener_datos_empresa_sesion() : null;
if (is_array($empresa) && !empty($empresa['nombre_empresa'])) {
    $nombre_sucursal = (string) $empresa['nombre_empresa'];
}

$fecha_val_def = date('Y-m-d', strtotime('+30 days'));
$titulo_card = 'Nuevo presupuesto';
$es_edicion = false;
$id_presupuesto = 0;
$numero_presupuesto = '';
$val = [];
$bootstrap_edicion_json = '';

require __DIR__ . '/../include/formulario_presupuesto.php';
