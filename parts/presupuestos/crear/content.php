<?php
$id_sucursal = isset($_POST['id_sucursal']) ? (int)$_POST['id_sucursal'] : 0;
$id_articulo_pre = isset($_POST['id_articulo']) ? (int)$_POST['id_articulo'] : 0;

if (!$id_sucursal) {
    header('Location: presupuestos.php');
    exit;
}

$nombre_sucursal = '';
$id_empresa_rel = 0;
$conexion = conectar_bd();
if ($conexion) {
    $q = 'SELECT nombre_sucursal, empresa_id FROM sucursal WHERE id_sucursal = ? LIMIT 1';
    $st = mysqli_prepare($conexion, $q);
    if ($st) {
        mysqli_stmt_bind_param($st, 'i', $id_sucursal);
        mysqli_stmt_execute($st);
        $res = mysqli_stmt_get_result($st);
        if ($row = mysqli_fetch_assoc($res)) {
            $nombre_sucursal = $row['nombre_sucursal'];
            $id_empresa_rel = (int)$row['empresa_id'];
        }
        mysqli_stmt_close($st);
    }
    mysqli_close($conexion);
}

$fecha_val_def = date('Y-m-d', strtotime('+30 days'));
$titulo_card = 'Nuevo presupuesto';
$es_edicion = false;
$id_presupuesto = 0;
$numero_presupuesto = '';
$val = [];
$bootstrap_edicion_json = '';

require __DIR__ . '/../include/formulario_presupuesto.php';
