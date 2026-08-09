<?php
/**
 * Datos para el modal de adelanto de capital (venta a plazos).
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
    if ($id_venta <= 0) {
        throw new Exception('ID de venta no válido');
    }

    $conexion = conectar_bd();
    if (!$conexion) {
        throw new Exception('Sin conexión');
    }

    $stmt = mysqli_prepare(
        $conexion,
        'SELECT id, estado, venta_plazos, numero_plazos, precio, id_venta_sucursal, id_sucursal
         FROM ventas WHERE id = ? LIMIT 1'
    );
    if (!$stmt) {
        throw new Exception(mysqli_error($conexion));
    }
    mysqli_stmt_bind_param($stmt, 'i', $id_venta);
    mysqli_stmt_execute($stmt);
    $res = mysqli_stmt_get_result($stmt);
    $v = $res ? mysqli_fetch_assoc($res) : null;
    mysqli_stmt_close($stmt);
    if (!$v) {
        throw new Exception('Venta no encontrada');
    }

    if (strtolower((string) ($v['venta_plazos'] ?? '')) !== 'si') {
        throw new Exception('La venta no es a plazos');
    }

    $est = strtolower((string) ($v['estado'] ?? ''));
    if (!in_array($est, ['enfecha', 'vencido'], true)) {
        throw new Exception('Solo se puede adelantar capital con la venta en fecha o vencida');
    }

    $numero_plazos = (int) ($v['numero_plazos'] ?? 0);
    if ($numero_plazos <= 0) {
        throw new Exception('Número de plazos no válido');
    }

    $id_sucursal = (int) ($v['id_sucursal'] ?? 0);
    if ($id_sucursal <= 0) {
        throw new Exception('Sucursal de la venta no válida');
    }

    $stmtS = mysqli_prepare(
        $conexion,
        'SELECT porcentaje_gastos_adelantos FROM sucursal WHERE id_sucursal = ? LIMIT 1'
    );
    if (!$stmtS) {
        throw new Exception(mysqli_error($conexion));
    }
    mysqli_stmt_bind_param($stmtS, 'i', $id_sucursal);
    mysqli_stmt_execute($stmtS);
    $rs = mysqli_stmt_get_result($stmtS);
    $rowS = $rs ? mysqli_fetch_assoc($rs) : null;
    mysqli_stmt_close($stmtS);
    if (!$rowS) {
        throw new Exception('Sucursal no encontrada');
    }
    $porcentaje_gastos = (float) ($rowS['porcentaje_gastos_adelantos'] ?? 0);

    $stmtPg = mysqli_prepare(
        $conexion,
        "SELECT COUNT(id) AS c, COALESCE(SUM(importe), 0) AS total_pagado
         FROM ventas_plazos WHERE id_venta = ? AND estado = 'Pagado'"
    );
    mysqli_stmt_bind_param($stmtPg, 'i', $id_venta);
    mysqli_stmt_execute($stmtPg);
    $rpg = mysqli_stmt_get_result($stmtPg);
    $rowPg = $rpg ? mysqli_fetch_assoc($rpg) : null;
    mysqli_stmt_close($stmtPg);
    $plazos_pagados = (int) ($rowPg['c'] ?? 0);
    $capital_actual = (float) ($rowPg['total_pagado'] ?? 0);
    $precio_venta = (float) ($v['precio'] ?? 0);
    $total_pendiente = max(0, $precio_venta - $capital_actual);

    $stmtUlt = mysqli_prepare(
        $conexion,
        'SELECT importe FROM ventas_plazos WHERE id_venta = ? ORDER BY id DESC LIMIT 1'
    );
    mysqli_stmt_bind_param($stmtUlt, 'i', $id_venta);
    mysqli_stmt_execute($stmtUlt);
    $rult = mysqli_stmt_get_result($stmtUlt);
    $rowUlt = $rult ? mysqli_fetch_assoc($rult) : null;
    mysqli_stmt_close($stmtUlt);
    $importe_ultimo_plazo = (float) ($rowUlt['importe'] ?? 0);

    $stmtVen = mysqli_prepare(
        $conexion,
        "SELECT COUNT(id) AS c FROM ventas_plazos WHERE id_venta = ? AND estado = 'Vencido'"
    );
    mysqli_stmt_bind_param($stmtVen, 'i', $id_venta);
    mysqli_stmt_execute($stmtVen);
    $rven = mysqli_stmt_get_result($stmtVen);
    $rowVen = $rven ? mysqli_fetch_assoc($rven) : null;
    mysqli_stmt_close($stmtVen);
    if ((int) ($rowVen['c'] ?? 0) > 0) {
        throw new Exception('No se puede adelantar capital con plazos vencidos');
    }

    $stmtPend = mysqli_prepare(
        $conexion,
        "SELECT COUNT(id) AS c FROM ventas_plazos WHERE id_venta = ? AND estado = 'Pendiente'"
    );
    mysqli_stmt_bind_param($stmtPend, 'i', $id_venta);
    mysqli_stmt_execute($stmtPend);
    $rpend = mysqli_stmt_get_result($stmtPend);
    $rowPend = $rpend ? mysqli_fetch_assoc($rpend) : null;
    mysqli_stmt_close($stmtPend);
    $plazos_pendientes = (int) ($rowPend['c'] ?? 0);
    if ($plazos_pendientes <= 0) {
        throw new Exception('No quedan plazos pendientes');
    }

    mysqli_close($conexion);

    echo json_encode([
        'success' => true,
        'capital_actual' => round($capital_actual, 2),
        'total_pendiente' => round($total_pendiente, 2),
        'plazos_pagados' => $plazos_pagados,
        'numero_plazos' => $numero_plazos,
        'plazos_pendientes' => $plazos_pendientes,
        'importe_plazo_antiguo' => round($importe_ultimo_plazo, 2),
        'porcentaje_gastos_adelantos' => round($porcentaje_gastos, 4),
        'id_venta_sucursal' => (int) ($v['id_venta_sucursal'] ?? 0),
        'id_sucursal' => (int) ($v['id_sucursal'] ?? 0),
    ], JSON_UNESCAPED_UNICODE);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => $e->getMessage()], JSON_UNESCAPED_UNICODE);
}
