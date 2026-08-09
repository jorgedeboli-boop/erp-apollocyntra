<?php
/**
 * Datos de una cuota para el modal de edición.
 */
require_once __DIR__ . '/../../../include/session.php';
require_once __DIR__ . '/../../../include/functions.php';

header('Content-Type: application/json; charset=utf-8');

try {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        http_response_code(405);
        echo json_encode(['success' => false, 'message' => 'Método no permitido']);
        exit;
    }

    $id_venta = isset($_POST['id_venta']) ? (int) $_POST['id_venta'] : 0;
    $id_plazo = isset($_POST['id_plazo']) ? (int) $_POST['id_plazo'] : 0;

    if ($id_venta <= 0 || $id_plazo <= 0) {
        throw new Exception('Datos no válidos');
    }

    $item_modulo = basename(dirname(__DIR__));
    if (!usuario_puede_acceder_crud_tipo($usuario_privilegio_id, crud_id_listar_modulo($item_modulo), 'editar')) {
        throw new Exception('No tiene permisos para esta acción');
    }

    $conexion = conectar_bd();
    if (!$conexion) {
        throw new Exception('Sin conexión');
    }

    $stmtV = mysqli_prepare(
        $conexion,
        'SELECT id, estado, venta_plazos, id_sucursal FROM ventas WHERE id = ? LIMIT 1'
    );
    if (!$stmtV) {
        throw new Exception(mysqli_error($conexion));
    }
    mysqli_stmt_bind_param($stmtV, 'i', $id_venta);
    mysqli_stmt_execute($stmtV);
    $rv = mysqli_stmt_get_result($stmtV);
    $venta = $rv ? mysqli_fetch_assoc($rv) : null;
    mysqli_stmt_close($stmtV);

    if (!$venta || strtolower((string) ($venta['venta_plazos'] ?? '')) !== 'si') {
        throw new Exception('Venta no encontrada o no es a plazos');
    }

    $estVenta = strtolower((string) ($venta['estado'] ?? ''));
    if ($estVenta === 'anulado' || $estVenta === 'anulada') {
        throw new Exception('La venta está anulada');
    }

    $stmtPl = mysqli_prepare(
        $conexion,
        "SELECT vp.id, vp.estado, vp.importe, vp.metodo_pago, vp.fecha_cobrado, vp.fecha_vencimiento, vp.fecha_vencido,
                (SELECT COUNT(*) FROM ventas_plazos v2 WHERE v2.id_venta = vp.id_venta AND v2.id <= vp.id) AS numero_cuota
         FROM ventas_plazos vp
         WHERE vp.id = ? AND vp.id_venta = ?
         LIMIT 1"
    );
    if (!$stmtPl) {
        throw new Exception(mysqli_error($conexion));
    }
    mysqli_stmt_bind_param($stmtPl, 'ii', $id_plazo, $id_venta);
    mysqli_stmt_execute($stmtPl);
    $rp = mysqli_stmt_get_result($stmtPl);
    $plazo = $rp ? mysqli_fetch_assoc($rp) : null;
    mysqli_stmt_close($stmtPl);

    if (!$plazo) {
        throw new Exception('Plazo no encontrado');
    }

    $estPl = (string) ($plazo['estado'] ?? '');
    if (!in_array($estPl, ['Pagado', 'Pendiente', 'Vencido'], true)) {
        throw new Exception('Este plazo no se puede editar');
    }

    mysqli_close($conexion);

    echo json_encode([
        'success' => true,
        'plazo' => [
            'id' => (int) ($plazo['id'] ?? 0),
            'numero_cuota' => max(1, (int) ($plazo['numero_cuota'] ?? 0)),
            'estado' => $estPl,
            'importe' => round((float) ($plazo['importe'] ?? 0), 2),
            'metodo_pago' => (string) ($plazo['metodo_pago'] ?? ''),
            'fecha_cobrado' => (string) ($plazo['fecha_cobrado'] ?? ''),
            'fecha_vencimiento' => (string) ($plazo['fecha_vencimiento'] ?? ''),
            'fecha_vencido' => (string) ($plazo['fecha_vencido'] ?? ''),
        ],
    ], JSON_UNESCAPED_UNICODE);
} catch (Exception $e) {
    if (isset($conexion) && $conexion) {
        mysqli_close($conexion);
    }
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => $e->getMessage()], JSON_UNESCAPED_UNICODE);
}
