<?php
/**
 * Crear presupuesto + líneas (JSON POST)
 */
ob_start();
require_once __DIR__ . '/../../../include/session.php';
require_once __DIR__ . '/../../../include/functions.php';
ob_end_clean();

header('Content-Type: application/json; charset=utf-8');

$raw = file_get_contents('php://input');
$data = json_decode($raw, true);

if (!is_array($data)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'JSON no válido']);
    exit;
}

$rel_id_empresa = obtener_rel_id_empresa_sesion();
$titulo = isset($data['titulo']) ? trim($data['titulo']) : '';
$id_cliente = isset($data['id_cliente']) ? (int)$data['id_cliente'] : 0;
$lineasIn = isset($data['lineas']) && is_array($data['lineas']) ? $data['lineas'] : [];

if ($rel_id_empresa <= 0 || $titulo === '' || $id_cliente <= 0 || empty($lineasIn)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Faltan datos obligatorios (empresa, título, cliente o líneas)']);
    exit;
}

$descripcion = isset($data['descripcion']) ? (string)$data['descripcion'] : '';
$notas_cliente = isset($data['notas_cliente']) ? (string)$data['notas_cliente'] : '';
$notas_internas = isset($data['notas_internas']) ? (string)$data['notas_internas'] : '';
$condiciones = isset($data['condiciones']) ? (string)$data['condiciones'] : '';
$fecha_validez = isset($data['fecha_validez']) ? trim($data['fecha_validez']) : '';
if ($fecha_validez === '') {
    $fecha_validez = date('Y-m-d', strtotime('+30 days'));
}
$porcentaje_iva_cab = isset($data['porcentaje_iva']) ? (float)$data['porcentaje_iva'] : 21.0;
$estado = isset($data['estado']) ? $data['estado'] : 'borrador';
$estadosOk = ['borrador', 'enviado', 'aceptado', 'rechazado', 'caducado', 'facturado', 'cancelado'];
if (!in_array($estado, $estadosOk, true)) {
    $estado = 'borrador';
}

$usuario_creador = isset($usuario_id) ? (int)$usuario_id : 0;

function calcularLineaPresupuesto($tipo, $cantidad, $precio_unitario, $porcentaje_dto, $porcentaje_iva)
{
    $tiposSinImporte = ['comentario'];
    if (in_array($tipo, $tiposSinImporte, true)) {
        return [
            'base_imponible' => 0.0,
            'importe_dto' => 0.0,
            'importe_iva' => 0.0,
            'total' => 0.0
        ];
    }
    $bruto = round($cantidad * $precio_unitario, 4);
    $importe_dto = round($bruto * ($porcentaje_dto / 100), 2);
    $base = round($bruto - $importe_dto, 2);
    $importe_iva = round($base * ($porcentaje_iva / 100), 2);
    $total = round($base + $importe_iva, 2);
    return [
        'base_imponible' => $base,
        'importe_dto' => $importe_dto,
        'importe_iva' => $importe_iva,
        'total' => $total
    ];
}

$lineasCalc = [];
$sumBase = 0.0;
$sumIva = 0.0;
$sumTotal = 0.0;

foreach ($lineasIn as $idx => $ln) {
    $tipo = isset($ln['tipo']) ? $ln['tipo'] : 'producto';
    $tiposOk = ['producto', 'servicio', 'comentario', 'subtotal'];
    if (!in_array($tipo, $tiposOk, true)) {
        $tipo = 'producto';
    }
    $descripcionLinea = isset($ln['descripcion']) ? trim($ln['descripcion']) : '';
    $cantidad = isset($ln['cantidad']) ? (float)$ln['cantidad'] : 0;
    $precio_unitario = isset($ln['precio_unitario']) ? (float)$ln['precio_unitario'] : 0;
    $porcentaje_dto = isset($ln['porcentaje_dto']) ? (float)$ln['porcentaje_dto'] : 0;
    $porcentaje_iva = isset($ln['porcentaje_iva']) ? (float)$ln['porcentaje_iva'] : $porcentaje_iva_cab;
    $orden = isset($ln['orden']) ? (int)$ln['orden'] : ($idx + 1);
    $unidad = isset($ln['unidad']) ? substr(trim($ln['unidad']), 0, 20) : 'ud';
    $id_articulo_line = isset($ln['id_articulo']) ? (int)$ln['id_articulo'] : 0;
    $referencia = isset($ln['referencia']) ? substr(trim($ln['referencia']), 0, 100) : '';

    $calc = calcularLineaPresupuesto($tipo, $cantidad, $precio_unitario, $porcentaje_dto, $porcentaje_iva);
    $sumBase += $calc['base_imponible'];
    $sumIva += $calc['importe_iva'];
    $sumTotal += $calc['total'];

    $lineasCalc[] = [
        'orden' => $orden,
        'tipo' => $tipo,
        'id_articulo' => $id_articulo_line,
        'referencia' => $referencia,
        'descripcion' => $descripcionLinea,
        'descripcion_larga' => '',
        'cantidad' => $cantidad,
        'unidad' => $unidad ?: 'ud',
        'precio_unitario' => $precio_unitario,
        'porcentaje_dto' => $porcentaje_dto,
        'importe_dto' => $calc['importe_dto'],
        'base_imponible' => $calc['base_imponible'],
        'porcentaje_iva' => $porcentaje_iva,
        'importe_iva' => $calc['importe_iva'],
        'total' => $calc['total']
    ];
}

$sumBase = round($sumBase, 2);
$sumIva = round($sumIva, 2);
$sumTotal = round($sumTotal, 2);

try {
    $conexion = conectar_bd();
    mysqli_begin_transaction($conexion);

    $sqlIns = "INSERT INTO presupuestos (
        rel_id_empresa, numero, version, id_cliente, id_contacto,
        id_usuario_creador, id_usuario_comercial, id_usuario_modificador,
        titulo, descripcion, notas_cliente, notas_internas, condiciones,
        porcentaje_dto, importe_dto, base_imponible, porcentaje_iva, importe_iva, total,
        estado, motivo_rechazo,
        fecha_envio, fecha_validez, fecha_respuesta, fecha_facturacion,
        id_presupuesto_origen, id_pedido, id_factura,
        moneda, idioma, plantilla
    ) VALUES (
        ?, '', 1, ?, 0,
        ?, 0, ?,
        ?, ?, ?, ?, ?,
        0, 0, ?, ?, ?, ?,
        ?, '',
        '0000-00-00', ?, '0000-00-00', '0000-00-00',
        0, 0, 0,
        'EUR', 'es', ''
    )";

    $stmt = mysqli_prepare($conexion, $sqlIns);
    mysqli_stmt_bind_param(
        $stmt,
        'iiiisssssddddss',
        $rel_id_empresa,
        $id_cliente,
        $usuario_creador,
        $usuario_creador,
        $titulo,
        $descripcion,
        $notas_cliente,
        $notas_internas,
        $condiciones,
        $sumBase,
        $porcentaje_iva_cab,
        $sumIva,
        $sumTotal,
        $estado,
        $fecha_validez
    );

    if (!mysqli_stmt_execute($stmt)) {
        throw new Exception(mysqli_error($conexion));
    }
    mysqli_stmt_close($stmt);

    $id_presupuesto = (int)mysqli_insert_id($conexion);
    if ($id_presupuesto <= 0) {
        throw new Exception('No se obtuvo id del presupuesto');
    }

    $numero = 'P-' . date('Y') . '-' . str_pad((string)$id_presupuesto, 5, '0', STR_PAD_LEFT);
    $stmtU = mysqli_prepare($conexion, 'UPDATE presupuestos SET numero = ? WHERE id = ?');
    mysqli_stmt_bind_param($stmtU, 'si', $numero, $id_presupuesto);
    mysqli_stmt_execute($stmtU);
    mysqli_stmt_close($stmtU);

    $sqlLinea = "INSERT INTO presupuestos_lineas (
        rel_id_empresa, id_presupuesto, orden, tipo, id_articulo,
        referencia, descripcion, descripcion_larga,
        cantidad, unidad, precio_unitario, porcentaje_dto, importe_dto,
        base_imponible, porcentaje_iva, importe_iva, total
    ) VALUES (
        ?, ?, ?, ?, ?,
        ?, ?, '',
        ?, ?, ?, ?, ?,
        ?, ?, ?, ?
    )";

    foreach ($lineasCalc as $lc) {
        $ord = (int)$lc['orden'];
        $tipoL = $lc['tipo'];
        $idArt = (int)$lc['id_articulo'];
        $refL = $lc['referencia'];
        $descL = $lc['descripcion'];
        $cantL = (float)$lc['cantidad'];
        $uniL = $lc['unidad'];
        $puL = (float)$lc['precio_unitario'];
        $pdtoL = (float)$lc['porcentaje_dto'];
        $impDtoL = (float)$lc['importe_dto'];
        $baseL = (float)$lc['base_imponible'];
        $pivaL = (float)$lc['porcentaje_iva'];
        $impIvaL = (float)$lc['importe_iva'];
        $totL = (float)$lc['total'];

        $stL = mysqli_prepare($conexion, $sqlLinea);
        mysqli_stmt_bind_param(
            $stL,
            'iiisissdsddddddd',
            $rel_id_empresa,
            $id_presupuesto,
            $ord,
            $tipoL,
            $idArt,
            $refL,
            $descL,
            $cantL,
            $uniL,
            $puL,
            $pdtoL,
            $impDtoL,
            $baseL,
            $pivaL,
            $impIvaL,
            $totL
        );
        if (!mysqli_stmt_execute($stL)) {
            throw new Exception(mysqli_error($conexion));
        }
        mysqli_stmt_close($stL);
    }

    mysqli_commit($conexion);
    mysqli_close($conexion);

    echo json_encode([
        'success' => true,
        'id' => $id_presupuesto,
        'numero' => $numero
    ]);
} catch (Exception $e) {
    if (isset($conexion)) {
        mysqli_rollback($conexion);
        mysqli_close($conexion);
    }
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
