<?php
require_once __DIR__ . '/_wizard_api_bootstrap.php';

$codigos_permitidos = array(
    'formacion_menu_clientes',
    'formacion_clientes_buscar_campo',
    'formacion_clientes_ejemplo_busqueda',
    'formacion_clientes_abrir_ficha',
    'formacion_ficha_perfil',
    'formacion_ficha_lotes_tab',
    'formacion_ficha_lotes_buscador',
    'formacion_ficha_empenos_tab',
    'formacion_ficha_empenos_buscador',
    'formacion_ficha_ventas_tab',
    'formacion_ficha_ventas_completado',
    'formacion_ficha_editar_cliente_link',
    'formacion_editar_cliente_campos_obligatorios',
    'formacion_editar_cliente_guardar_info',
);

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(array('ok' => false, 'error' => 'metodo'));
    exit;
}

$codigo = isset($_POST['codigo_paso']) ? trim((string) $_POST['codigo_paso']) : '';
if ($codigo === '' || !in_array($codigo, $codigos_permitidos, true)) {
    http_response_code(400);
    echo json_encode(array('ok' => false, 'error' => 'codigo_invalido'));
    exit;
}

$id_usuario = (int) ($_SESSION['usuario_id'] ?? 0);
$id_sucursal = (int) ($_SESSION['usuario_sucursal'] ?? 0);
if ($id_usuario < 1) {
    http_response_code(400);
    echo json_encode(array('ok' => false, 'error' => 'usuario'));
    exit;
}

$id_ctx = 0;
if (isset($_POST['id_cliente_context']) && (string) $_POST['id_cliente_context'] !== '') {
    $id_ctx = (int) $_POST['id_cliente_context'];
}

$conexion = conectar_bd();

$sql_con_ctx = 'INSERT INTO formacion_wizard_pasos (id_usuario, id_sucursal, codigo_paso, fecha_completado, id_cliente_context)
        VALUES (?, ?, ?, NOW(), NULLIF(?, 0))
        ON DUPLICATE KEY UPDATE id_sucursal = VALUES(id_sucursal), fecha_completado = VALUES(fecha_completado),
        id_cliente_context = COALESCE(NULLIF(VALUES(id_cliente_context), 0), id_cliente_context)';

$sql_sin_ctx = 'INSERT INTO formacion_wizard_pasos (id_usuario, id_sucursal, codigo_paso, fecha_completado)
        VALUES (?, ?, ?, NOW())
        ON DUPLICATE KEY UPDATE id_sucursal = VALUES(id_sucursal), fecha_completado = VALUES(fecha_completado)';

$stmt = mysqli_prepare($conexion, $sql_con_ctx);
$err = mysqli_error($conexion);
if (!$stmt && stripos($err, 'id_cliente_context') !== false) {
    $stmt = mysqli_prepare($conexion, $sql_sin_ctx);
    $err = mysqli_error($conexion);
}

if (!$stmt) {
    $detalle = mysqli_error($conexion);
    mysqli_close($conexion);
    http_response_code(500);
    echo json_encode(array('ok' => false, 'error' => 'bd', 'detalle' => $detalle));
    exit;
}

$nparams = mysqli_stmt_param_count($stmt);
if ($nparams === 4) {
    mysqli_stmt_bind_param($stmt, 'iisi', $id_usuario, $id_sucursal, $codigo, $id_ctx);
} else {
    mysqli_stmt_bind_param($stmt, 'iis', $id_usuario, $id_sucursal, $codigo);
}
$ok = mysqli_stmt_execute($stmt);
$detalleEjec = $ok ? '' : mysqli_stmt_error($stmt);
mysqli_stmt_close($stmt);
mysqli_close($conexion);

if (!$ok) {
    http_response_code(500);
    echo json_encode(array('ok' => false, 'error' => 'insert', 'detalle' => $detalleEjec));
    exit;
}

echo json_encode(array('ok' => true, 'codigo_paso' => $codigo));
