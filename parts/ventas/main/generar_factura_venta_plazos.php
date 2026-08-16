<?php
/**
 * Genera factura (completa o simplificada) al cerrar una venta a plazos.
 */
require_once __DIR__ . '/../../../include/session.php';
require_once __DIR__ . '/../../../include/functions.php';
require_once __DIR__ . '/venta_plazos_factura_lib.php';

header('Content-Type: application/json; charset=utf-8');

$conexion = conectar_bd();

try {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        http_response_code(405);
        echo json_encode(['success' => false, 'message' => 'Método no permitido']);
        exit;
    }

    $id_venta = isset($_POST['id_venta']) ? (int) $_POST['id_venta'] : 0;
    $tipo_factura = isset($_POST['tipo_factura']) ? strtolower(trim((string) $_POST['tipo_factura'])) : '';
    $uid = isset($usuario_id) ? (int) $usuario_id : 0;

    if ($id_venta <= 0 || $uid <= 0) {
        throw new Exception('Datos no válidos');
    }
    if (!in_array($tipo_factura, ['completa', 'simplificada'], true)) {
        throw new Exception('Tipo de factura no válido');
    }

    if (!$conexion) {
        throw new Exception('Sin conexión a la base de datos');
    }

    $stmtV = mysqli_prepare(
        $conexion,
        'SELECT id, precio, estado, rel_id_empresa, cliente, tipo_pago FROM ventas WHERE id = ? LIMIT 1'
    );
    if (!$stmtV) {
        throw new Exception(mysqli_error($conexion));
    }
    mysqli_stmt_bind_param($stmtV, 'i', $id_venta);
    mysqli_stmt_execute($stmtV);
    $rv = mysqli_stmt_get_result($stmtV);
    $venta = $rv ? mysqli_fetch_assoc($rv) : null;
    mysqli_stmt_close($stmtV);

    if (!$venta) {
        throw new Exception('Venta no encontrada');
    }

    $stPlazos = mysqli_prepare($conexion, 'SELECT id FROM ventas_plazos WHERE id_venta = ? LIMIT 1');
    $es_plazos = false;
    if ($stPlazos) {
        mysqli_stmt_bind_param($stPlazos, 'i', $id_venta);
        mysqli_stmt_execute($stPlazos);
        $rp = mysqli_stmt_get_result($stPlazos);
        $es_plazos = (bool) ($rp && mysqli_fetch_assoc($rp));
        mysqli_stmt_close($stPlazos);
    }
    if (!$es_plazos) {
        throw new Exception('La venta no es a plazos');
    }

    $estVenta = strtolower((string) ($venta['estado'] ?? ''));
    if ($estVenta !== 'vendido') {
        throw new Exception('La venta no está cerrada');
    }

    $id_sucursal = 0;
    $precio = (float) ($venta['precio'] ?? 0);
    $id_cliente = (int) ($venta['cliente'] ?? 0);
    $tipo_pago = trim((string) ($venta['tipo_pago'] ?? ''));
    if ($tipo_pago === '') {
        $tipo_pago = 'contado';
    }
    $tipo_factura_items = gfv_tipo_factura_items_desde_venta($conexion, $id_venta, $id_sucursal);

    if (venta_plazos_tiene_factura_generada($conexion, $id_venta, $id_sucursal)) {
        throw new Exception('La venta ya tiene factura generada');
    }

    if ($tipo_factura === 'completa' && $id_cliente <= 0) {
        throw new Exception('La factura completa requiere cliente en la venta');
    }

    $id_factura = 0;
    $id_factura_simplificada = 0;

    if ($tipo_factura === 'completa') {
        $id_factura = gfv_generar_factura_completa(
            $conexion,
            $id_venta,
            $id_sucursal,
            $id_cliente,
            $precio,
            $tipo_pago,
            $uid,
            $tipo_factura_items
        );
    } else {
        $id_factura_simplificada = gfv_generar_factura_simplificada(
            $conexion,
            $id_venta,
            $id_sucursal,
            $precio,
            $tipo_pago,
            $uid,
            $tipo_factura_items
        );
    }

    mysqli_close($conexion);

    echo json_encode([
        'success' => true,
        'message' => 'Factura generada correctamente',
        'tipo_factura' => $tipo_factura,
        'id_factura' => $id_factura,
        'id_factura_simplificada' => $id_factura_simplificada,
    ], JSON_UNESCAPED_UNICODE);
} catch (Throwable $e) {
    if ($conexion) {
        mysqli_close($conexion);
    }
    insertErrorLog('generar_factura_venta_plazos: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => $e->getMessage()], JSON_UNESCAPED_UNICODE);
}
