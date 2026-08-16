<?php
$id_presupuesto = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($id_presupuesto <= 0) {
    header('Location: presupuestos.php');
    exit;
}

$conexion = conectar_bd();
if (!$conexion) {
    header('Location: presupuestos.php?error=conexion');
    exit;
}

$stmt = mysqli_prepare($conexion, 'SELECT * FROM presupuestos WHERE id = ? LIMIT 1');
mysqli_stmt_bind_param($stmt, 'i', $id_presupuesto);
mysqli_stmt_execute($stmt);
$res = mysqli_stmt_get_result($stmt);
$pres = mysqli_fetch_assoc($res);
mysqli_stmt_close($stmt);

if (!$pres) {
    mysqli_close($conexion);
    header('Location: presupuestos.php?error=no_encontrado');
    exit;
}

$id_empresa_rel = (int)$pres['rel_id_empresa'];
$id_cliente = (int)$pres['id_cliente'];

$stmtL = mysqli_prepare(
    $conexion,
    'SELECT * FROM presupuestos_lineas WHERE id_presupuesto = ? ORDER BY orden ASC, id ASC'
);
mysqli_stmt_bind_param($stmtL, 'i', $id_presupuesto);
mysqli_stmt_execute($stmtL);
$resL = mysqli_stmt_get_result($stmtL);
$lineas_db = [];
while ($row = mysqli_fetch_assoc($resL)) {
    $lineas_db[] = $row;
}
mysqli_stmt_close($stmtL);

$id_sucursal = 0;
$nombre_sucursal = '';
$stEmp = mysqli_prepare($conexion, 'SELECT * FROM empresas WHERE id_empresa = ? LIMIT 1');
if ($stEmp) {
    mysqli_stmt_bind_param($stEmp, 'i', $id_empresa_rel);
    mysqli_stmt_execute($stEmp);
    $rsEmp = mysqli_stmt_get_result($stEmp);
    $empresa = $rsEmp ? mysqli_fetch_assoc($rsEmp) : null;
    mysqli_stmt_close($stEmp);
    if (is_array($empresa) && !empty($empresa['nombre_empresa'])) {
        $nombre_sucursal = (string) $empresa['nombre_empresa'];
    }
}

$cli = null;
if ($id_cliente > 0) {
    $st = mysqli_prepare($conexion, 'SELECT * FROM clientes WHERE id_cliente = ? LIMIT 1');
    mysqli_stmt_bind_param($st, 'i', $id_cliente);
    mysqli_stmt_execute($st);
    $rc = mysqli_stmt_get_result($st);
    $cli = mysqli_fetch_assoc($rc);
    mysqli_stmt_close($st);
}

mysqli_close($conexion);

$direccion = ($id_cliente > 0) ? obtenerDireccionCliente($id_cliente) : null;
$datos_cli_extra = ($id_cliente > 0) ? obtenerDatosCliente($id_cliente) : null;

$fv = $pres['fecha_validez'] ?? '';
if ($fv === '' || $fv === '0000-00-00' || strpos($fv, '0000') === 0) {
    $fv = date('Y-m-d', strtotime('+30 days'));
} else {
    $fv = date('Y-m-d', strtotime($fv));
}

$tipo_identificacion_id = $cli['tipo_identificacion_id'] ?? '';
$email_cli = '';
if (is_array($datos_cli_extra) && !empty($datos_cli_extra['email'])) {
    $email_cli = $datos_cli_extra['email'];
}

$nombre_cab = trim(($cli['nombre'] ?? '') . ' ' . ($cli['apellido'] ?? ''));
$pob_cab = '';
if ($direccion && !empty($direccion['c_poblacion'])) {
    $pob_cab = $direccion['c_poblacion'];
}
$cp_cab = $direccion['codigo_postal'] ?? '';

$val = [
    'id_cliente' => $id_cliente > 0 ? (string)$id_cliente : '',
    'insert_tipo_identificacion' => (string)$tipo_identificacion_id,
    'insert_identificacion' => (string)($cli['identificacion'] ?? ''),
    'insert_nombre' => (string)($cli['nombre'] ?? ''),
    'insert_apellido' => (string)($cli['apellido'] ?? ''),
    'insert_telefono' => (string)($cli['telefono'] ?? ''),
    'insert_email' => $email_cli,
    'insert_id_direccion' => $direccion ? (string)($direccion['id_direcciones'] ?? '') : '',
    'insert_pais' => $direccion ? (string)($direccion['rel_id_pais'] ?? '') : '',
    'insert_provincia' => $direccion ? (string)($direccion['rel_id_provincia'] ?? '') : '',
    'insert_poblacion' => $direccion ? (string)($direccion['rel_id_poblacion'] ?? '') : '',
    'insert_direccion' => $direccion ? (string)($direccion['direccion'] ?? '') : '',
    'insert_codigo_postal' => $direccion ? (string)($direccion['codigo_postal'] ?? '') : '',
    'nombre_cliente_cabecera' => $nombre_cab ?: 'Cliente',
    'tipo_identificacion_txt' => 'NIF',
    'direccion_cliente_cabecera' => $direccion ? (string)($direccion['direccion'] ?? '—') : '—',
    'poblacion_cliente_cabecera' => $pob_cab ?: '—',
    'codigo_postal_cliente_cabecera' => $cp_cab,
    'titulo' => (string)($pres['titulo'] ?? ''),
    'descripcion' => (string)($pres['descripcion'] ?? ''),
    'notas_cliente' => (string)($pres['notas_cliente'] ?? ''),
    'notas_internas' => (string)($pres['notas_internas'] ?? ''),
    'condiciones' => (string)($pres['condiciones'] ?? ''),
    'estado' => (string)($pres['estado'] ?? 'borrador'),
    'porcentaje_iva' => isset($pres['porcentaje_iva']) ? (string)(float)$pres['porcentaje_iva'] : '21',
    'fecha_validez' => $fv,
    'observaciones_venta' => '',
];

$lineas_boot = [];
foreach ($lineas_db as $ln) {
    if (($ln['tipo'] ?? '') === 'comentario') {
        continue;
    }
    $lineas_boot[] = [
        'tipo' => $ln['tipo'] ?? 'producto',
        'id_articulo' => (int)($ln['id_articulo'] ?? 0),
        'referencia' => (string)($ln['referencia'] ?? ''),
        'descripcion' => (string)($ln['descripcion'] ?? ''),
        'cantidad' => (float)($ln['cantidad'] ?? 1),
        'precio_unitario' => (float)($ln['precio_unitario'] ?? 0),
    ];
}

$bootstrap_edicion_json = json_encode(['lineas' => $lineas_boot], JSON_UNESCAPED_UNICODE);

$titulo_card = 'Editar presupuesto';
$es_edicion = true;
$numero_presupuesto = (string)($pres['numero'] ?? '');
$id_articulo_pre = 0;
$fecha_val_def = $fv;

require __DIR__ . '/../include/formulario_presupuesto.php';
