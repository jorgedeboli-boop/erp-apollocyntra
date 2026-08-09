<?php
require_once '../../../include/session.php';

header('Content-Type: application/json');

// Verificar conexión a la base de datos
$conexion = conectar_bd();

if (!$conexion) {
    echo json_encode([
        'success' => false,
        'error' => 'Error de conexión a la base de datos'
    ]);
    exit();
}

// Verificar que lleguen id_sucursal y id_lote
if (!isset($_POST['sucursal_lote']) || empty($_POST['sucursal_lote'])) {
    mysqli_close($conexion);
    echo json_encode([
        'success' => false,
        'error' => 'No se recibió el ID de sucursal'
    ]);
    exit();
}

if (!isset($_POST['id_lote']) || empty($_POST['id_lote'])) {
    mysqli_close($conexion);
    echo json_encode([
        'success' => false,
        'error' => 'No se recibió el ID de lote'
    ]);
    exit();
}

// Procesar cliente (INSERT o UPDATE)
require_once 'procesar_cliente_lote.php';

// Verificar resultado del procesamiento del cliente
if (!$resultado_cliente['success']) {
    mysqli_close($conexion);
    echo json_encode($resultado_cliente);
    exit();
}

// AQUI PROCESAREMOS EL LOTE.
require_once 'procesar_lote.php';

// Verificar resultado del procesamiento del lote
if (!$resultado_lote['success']) {
    mysqli_close($conexion);
    echo json_encode($resultado_lote);
    exit();
}

// TODO: Aquí se agregarán más procesos (artículos, trazabilidad, etc.)
$id_lote = $resultado_lote['id_lote'];
$id_sucursal = $resultado_lote['id_sucursal'];
$opcion_compra = $resultado_lote['opcion_compra'];
$precio_compra = $resultado_lote['precio_compra'];
$precio_recompra = $resultado_lote['precio_recompra'];
$fecha_vencimiento = $resultado_lote['fecha_vencimiento'];
$metodo_pago = $resultado_lote['metodo_pago'];
$cantidad_articulos = $resultado_lote['cantidad_articulos'];
$peso_neto = $resultado_lote['peso_neto'];
$peso_bruto = $resultado_lote['peso_bruto'];
$merma = $resultado_lote['merma'];
$intereses_lote = $resultado_lote['intereses_lote'];
$tipo_lote = $resultado_lote['tipo_lote'];
$fecha_liberacion = $resultado_lote['fecha_liberacion'];

$datos_lote = "Peso lote: ".$peso_neto." grs.<br>Cantidad articulos: ".$cantidad_articulos." grs.<br>Peso bruto: ".$peso_bruto." grs.<br>Precio compra: ".$precio_compra." €.<br>Merma: ".$merma." grs.<br>Tipo de lote: ".$tipo_lote;

if($opcion_compra == "si"){
    require_once 'procesar_renovaciones.php';
    $concepto_caja = "Empeño lote Nº " . $id_lote;
    $grupos_caja = "Empeño de lotes";
    $accion_trazabilidad = "empeno";
    $comentarios_accion = "Empeño creado. Datos del lote: ".$datos_lote." Precio de recompra: ".$precio_recompra;
    $message = "Empeño creado correctamente";
}else{
    $concepto_caja = "Compra lote Nº " . $id_lote;
    $grupos_caja = "Compra de lotes";
    $accion_trazabilidad = "compra";
    $comentarios_accion = "Compra creada. Datos del lote: ".$datos_lote;
    $message = "Lote creado correctamente";
}

if($metodo_pago == "efectivo"){
    insertar_movimiento_caja($grupos_caja, $concepto_caja, 0, $precio_compra, $usuario_id, $id_sucursal);
} else if($metodo_pago == "transferencia"){
    insertar_movimiento_transferencia($id_sucursal, $id_lote, 0, $concepto_caja, 0, $precio_compra, $usuario_id, $grupos_caja);
}

registrar_trazabilidad_lote($id_lote, $usuario_id, $accion_trazabilidad, $comentarios_accion, $id_sucursal);

// AQUI CONSULTAMOS EL ID DE lotes_joyeria PARA REDIRECCIONAR A LA PAGINA DE LOTES
$query_lote = "SELECT identificador FROM lotes_joyeria WHERE id_lote = ? AND sucursal = ? ";
$stmt_lote = mysqli_prepare($conexion, $query_lote);
mysqli_stmt_bind_param($stmt_lote, 'is', $id_lote, $id_sucursal);
mysqli_stmt_execute($stmt_lote);
$result_lote = mysqli_stmt_get_result($stmt_lote);
$row_lote = mysqli_fetch_assoc($result_lote);
$id_lote_joyeria = $row_lote['identificador'];

if( $id_lote_joyeria ){
    $url_lote = "lote.php?id=$id_lote_joyeria";
} else {
    $url_lote = "lote.php?id=$id_lote&sucursal=$id_sucursal";
}

// Cerrar conexión
mysqli_close($conexion);

$firma_digital_resp = verificarFirmaActiva($id_sucursal);
if ($firma_digital_resp === null || $firma_digital_resp === '') {
    $firma_digital_resp = 'false';
}

// Respuesta final
echo json_encode([
    'success' => true,
    'message' => $message,
    'id_cliente' => $resultado_cliente['id_cliente_procesado'],
    'id_lote' => $resultado_lote['id_lote'],
    'url' => $url_lote,
    'firma_digital' => (string) $firma_digital_resp,
    'precio_compra' => $precio_compra,
    'id_sucursal' => $id_sucursal,
]);

